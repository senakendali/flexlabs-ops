<?php

namespace App\Services\Assessment;

use App\Models\Certificate;
use App\Models\ReportCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CertificateService
{
    public function __construct(
        protected CertificateQrService $qrService
    ) {
    }

    public function issue(ReportCard $reportCard, ?int $issuedBy = null, array $extraData = []): Certificate
    {
        return DB::transaction(function () use ($reportCard, $issuedBy, $extraData) {
            $reportCard->loadMissing([
                'student',
                'batch',
                'program',
                'template',
            ]);

            if (! $reportCard->is_certificate_eligible) {
                throw new RuntimeException('This report card is not eligible for certificate issuance.');
            }

            if ($reportCard->status === 'cancelled') {
                throw new RuntimeException('Cannot issue certificate for a cancelled report card.');
            }

            $certificateType = $extraData['type'] ?? $this->resolveCertificateType($reportCard);
            $certificateTitle = $extraData['title'] ?? $this->resolveCertificateTitle($certificateType);

            $certificate = Certificate::query()->firstOrNew([
                'student_id' => $reportCard->student_id,
                'batch_id' => $reportCard->batch_id,
                'program_id' => $reportCard->program_id,
                'type' => $certificateType,
            ]);

            if (! $certificate->exists) {
                $certificate->certificate_no = $this->generateCertificateNo($reportCard, $certificateType);
                $certificate->public_token = $this->generatePublicToken();
            }

            $certificate->report_card_id = $reportCard->id;
            $certificate->title = $certificateTitle;

            $certificate->issued_date = $extraData['issued_date'] ?? now()->toDateString();
            $certificate->completed_date = $extraData['completed_date']
                ?? $this->resolveCompletedDate($reportCard);

            $certificate->final_score = $reportCard->final_score;
            $certificate->grade = $reportCard->grade;

            $certificate->verification_url = $extraData['verification_url']
                ?? $this->buildVerificationUrl($certificate->public_token);

            $certificate->qr_code_path = $extraData['qr_code_path'] ?? $certificate->qr_code_path;
            $certificate->pdf_path = $extraData['pdf_path'] ?? $certificate->pdf_path;

            $certificate->status = 'issued';
            $certificate->revocation_reason = null;
            $certificate->revoked_at = null;

            $certificate->issued_by = $issuedBy;
            $certificate->issued_at = now();

           $certificate->save();

            $certificate = $this->qrService->generate($certificate);

            return $certificate->fresh([
                'reportCard',
                'student',
                'batch',
                'program',
                'issuer',
            ]);
        });
    }

    public function issueByReportCardId(int $reportCardId, ?int $issuedBy = null, array $extraData = []): Certificate
    {
        $reportCard = ReportCard::query()->findOrFail($reportCardId);

        return $this->issue(
            reportCard: $reportCard,
            issuedBy: $issuedBy,
            extraData: $extraData
        );
    }

    public function revoke(Certificate $certificate, string $reason, ?int $revokedBy = null): Certificate
    {
        return DB::transaction(function () use ($certificate, $reason, $revokedBy) {
            $certificate->status = 'revoked';
            $certificate->revocation_reason = $reason;
            $certificate->revoked_at = now();

            /*
             * Migration kita belum punya revoked_by.
             * Kalau nanti mau audit lebih lengkap, bisa tambah field revoked_by.
             */
            $certificate->save();

            return $certificate->fresh([
                'reportCard',
                'student',
                'batch',
                'program',
                'issuer',
            ]);
        });
    }

    public function revokeById(int $certificateId, string $reason, ?int $revokedBy = null): Certificate
    {
        $certificate = Certificate::query()->findOrFail($certificateId);

        return $this->revoke(
            certificate: $certificate,
            reason: $reason,
            revokedBy: $revokedBy
        );
    }

    public function reissue(Certificate $certificate, ?int $issuedBy = null, array $extraData = []): Certificate
    {
        $certificate->loadMissing('reportCard');

        if (! $certificate->reportCard) {
            throw new RuntimeException('Cannot reissue certificate without related report card.');
        }

        return $this->issue(
            reportCard: $certificate->reportCard,
            issuedBy: $issuedBy,
            extraData: $extraData
        );
    }

    public function verifyByToken(string $token): ?Certificate
    {
        return Certificate::query()
            ->with([
                'student',
                'batch',
                'program',
                'reportCard',
            ])
            ->where('public_token', $token)
            ->first();
    }

    public function generateCertificateNo(ReportCard $reportCard, string $type = 'completion'): string
    {
        $reportCard->loadMissing([
            'batch',
            'program',
        ]);

        $prefix = match ($type) {
            'achievement' => 'COA',
            'excellence' => 'COE',
            'participation' => 'COP',
            default => 'COC',
        };

        $date = now()->format('Ymd');
        $programCode = $this->resolveProgramCode($reportCard);
        $batchCode = $this->resolveBatchCode($reportCard);

        $base = "{$prefix}-{$date}-{$programCode}-{$batchCode}";

        $latestCount = Certificate::query()
            ->where('certificate_no', 'like', "{$base}-%")
            ->withTrashed()
            ->count();

        $sequence = str_pad((string) ($latestCount + 1), 4, '0', STR_PAD_LEFT);

        $certificateNo = "{$base}-{$sequence}";

        while (
            Certificate::query()
                ->withTrashed()
                ->where('certificate_no', $certificateNo)
                ->exists()
        ) {
            $latestCount++;
            $sequence = str_pad((string) ($latestCount + 1), 4, '0', STR_PAD_LEFT);
            $certificateNo = "{$base}-{$sequence}";
        }

        return $certificateNo;
    }

    public function generatePublicToken(): string
    {
        do {
            $token = Str::random(48);
        } while (
            Certificate::query()
                ->withTrashed()
                ->where('public_token', $token)
                ->exists()
        );

        return $token;
    }

    public function resolveCertificateType(ReportCard $reportCard): string
    {
        $finalScore = (float) $reportCard->final_score;

        if ($finalScore >= 90) {
            return 'excellence';
        }

        if ($finalScore >= 85) {
            return 'achievement';
        }

        return 'completion';
    }

    public function resolveCertificateTitle(string $type): string
    {
        return match ($type) {
            'achievement' => 'Certificate of Achievement',
            'excellence' => 'Certificate of Excellence',
            'participation' => 'Certificate of Participation',
            default => 'Certificate of Completion',
        };
    }

    private function resolveCompletedDate(ReportCard $reportCard): string
    {
        $batch = $reportCard->batch;

        if ($batch && ! empty($batch->end_date)) {
            return $batch->end_date instanceof \Carbon\Carbon
                ? $batch->end_date->toDateString()
                : (string) $batch->end_date;
        }

        if ($reportCard->published_at) {
            return $reportCard->published_at->toDateString();
        }

        return now()->toDateString();
    }

    private function buildVerificationUrl(string $token): string
    {
        /*
         * Nanti route public-nya kita buat:
         * Route::get('/certificates/verify/{token}', ...)
         */
        return url("/certificates/verify/{$token}");
    }

    private function resolveProgramCode(ReportCard $reportCard): string
    {
        $name = $reportCard->program?->name ?? 'PROGRAM';

        return Str::of($name)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->limit(16, '')
            ->toString() ?: 'PROGRAM';
    }

    private function resolveBatchCode(ReportCard $reportCard): string
    {
        $batch = $reportCard->batch;

        $name = $batch?->name
            ?? $batch?->title
            ?? $batch?->code
            ?? "BATCH-{$reportCard->batch_id}";

        return Str::of($name)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->limit(16, '')
            ->toString() ?: "BATCH-{$reportCard->batch_id}";
    }
}