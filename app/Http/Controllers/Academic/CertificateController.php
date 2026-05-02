<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ReportCard;
use App\Services\Assessment\CertificatePdfService;
use App\Services\Assessment\CertificateQrService;
use App\Services\Assessment\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService,
        protected CertificateQrService $qrService,
        protected CertificatePdfService $pdfService
    ) {
    }

    public function index(Request $request)
    {
        $query = Certificate::query()
            ->with(['student', 'batch', 'program', 'reportCard', 'issuer'])
            ->when($request->filled('program_id'), function ($q) use ($request) {
                $q->where('program_id', $request->program_id);
            })
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->batch_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('type'), function ($q) use ($request) {
                $q->where('type', $request->type);
            })
            ->latest();

        $certificates = $query->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $certificates,
            ]);
        }

        return view('academic.certificates.index', compact('certificates'));
    }

    public function show(Request $request, Certificate $certificate)
    {
        $certificate->load([
            'student',
            'batch',
            'program',
            'reportCard',
            'issuer',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $this->certificatePayload($certificate),
            ]);
        }

        return view('academic.certificates.show', compact('certificate'));
    }

    public function issue(Request $request)
    {
        $validated = $request->validate([
            'report_card_id' => ['required', 'exists:report_cards,id'],
            'type' => ['nullable', 'in:completion,achievement,excellence,participation'],
            'title' => ['nullable', 'string', 'max:255'],
            'issued_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
        ]);

        $reportCard = ReportCard::query()->findOrFail($validated['report_card_id']);

        try {
            $certificate = $this->certificateService->issue(
                reportCard: $reportCard,
                issuedBy: $request->user()?->id,
                extraData: [
                    'type' => $validated['type'] ?? null,
                    'title' => $validated['title'] ?? null,
                    'issued_date' => $validated['issued_date'] ?? null,
                    'completed_date' => $validated['completed_date'] ?? null,
                ]
            );

            // PDF adalah output resmi. Service ini juga memastikan QR tersedia.
            $certificate = $this->prepareCertificatePdf($certificate);
        } catch (RuntimeException $exception) {
            return $this->failedResponse($request, $exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->failedResponse($request, $exception->getMessage(), 500);
        }

        if ($request->expectsJson()) {
            return $this->successResponse(
                message: 'Certificate issued successfully.',
                certificate: $certificate
            );
        }

        return redirect()
            ->route('academic.certificates.show', $certificate)
            ->with('success', 'Certificate issued successfully.');
    }

    public function reissue(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'type' => ['nullable', 'in:completion,achievement,excellence,participation'],
            'title' => ['nullable', 'string', 'max:255'],
            'issued_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
        ]);

        try {
            $this->deleteGeneratedPdfIfExists($certificate);

            $certificate = $this->certificateService->reissue(
                certificate: $certificate,
                issuedBy: $request->user()?->id,
                extraData: [
                    'type' => $validated['type'] ?? null,
                    'title' => $validated['title'] ?? null,
                    'issued_date' => $validated['issued_date'] ?? null,
                    'completed_date' => $validated['completed_date'] ?? null,
                ]
            );

            // Reissue berarti QR dan PDF resmi dibuat ulang supaya sinkron.
            $certificate = $this->qrService->regenerate($certificate);
            $certificate = $this->pdfService->generate($certificate);
        } catch (RuntimeException $exception) {
            return $this->failedResponse($request, $exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->failedResponse($request, $exception->getMessage(), 500);
        }

        if ($request->expectsJson()) {
            return $this->successResponse(
                message: 'Certificate reissued successfully.',
                certificate: $certificate
            );
        }

        return redirect()
            ->route('academic.certificates.show', $certificate)
            ->with('success', 'Certificate reissued successfully.');
    }

    public function revoke(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $certificate = $this->certificateService->revoke(
            certificate: $certificate,
            reason: $validated['reason'],
            revokedBy: $request->user()?->id
        );

        $certificate->loadMissing(['student', 'batch', 'program', 'reportCard', 'issuer']);

        if ($request->expectsJson()) {
            return $this->successResponse(
                message: 'Certificate revoked successfully.',
                certificate: $certificate
            );
        }

        return back()->with('success', 'Certificate revoked successfully.');
    }

    public function verify(string $token)
    {
        $certificate = $this->certificateService->verifyByToken($token);

        abort_if(! $certificate, 404);

        $certificate->loadMissing([
            'student',
            'batch',
            'program',
            'reportCard',
            'issuer',
        ]);

        return view('public.certificates.verify', compact('certificate'));
    }

    public function regenerateQr(Request $request, Certificate $certificate)
    {
        try {
            $this->deleteGeneratedPdfIfExists($certificate);

            $certificate = $this->qrService->regenerate($certificate);
            $certificate = $this->pdfService->generate($certificate);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->failedResponse(
                request: $request,
                message: 'Gagal regenerate QR code: ' . $exception->getMessage(),
                status: 500
            );
        }

        if ($request->expectsJson()) {
            return $this->successResponse(
                message: 'QR code and certificate PDF regenerated successfully.',
                certificate: $certificate
            );
        }

        return redirect()
            ->route('academic.certificates.show', $certificate)
            ->with('success', 'QR code dan certificate PDF berhasil diregenerate.');
    }

    public function generatePdf(Request $request, Certificate $certificate)
    {
        try {
            $certificate = $this->prepareCertificatePdf($certificate);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->failedResponse(
                request: $request,
                message: 'Gagal generate certificate PDF: ' . $exception->getMessage(),
                status: 500
            );
        }

        if ($request->expectsJson()) {
            return $this->successResponse(
                message: 'Certificate PDF generated successfully.',
                certificate: $certificate
            );
        }

        return redirect()
            ->route('academic.certificates.show', $certificate)
            ->with('success', 'Certificate PDF berhasil digenerate.');
    }

    public function downloadPdf(Certificate $certificate)
    {
        return $this->pdfService->download($certificate);
    }

    public function streamPdf(Certificate $certificate)
    {
        return $this->pdfService->stream($certificate);
    }

    /**
     * Backward compatibility untuk route/tombol lama yang sebelumnya masih generate image.
     * Sekarang output resmi diarahkan ke PDF.
     */
    public function generateImage(Request $request, Certificate $certificate)
    {
        return $this->generatePdf($request, $certificate);
    }

    /**
     * Backward compatibility untuk route/tombol lama download image.
     */
    public function downloadImage(Certificate $certificate)
    {
        return $this->downloadPdf($certificate);
    }

    private function prepareCertificatePdf(Certificate $certificate): Certificate
    {
        if (! $this->hasUsableQrImage($certificate)) {
            $certificate = $certificate->qr_code_path
                ? $this->qrService->regenerate($certificate)
                : $this->qrService->generate($certificate);
        }

        return $this->pdfService->generate($certificate);
    }

    private function hasUsableQrImage(Certificate $certificate): bool
    {
        if (! $certificate->qr_code_path) {
            return false;
        }

        if (! Storage::disk('public')->exists($certificate->qr_code_path)) {
            return false;
        }

        $extension = strtolower(pathinfo($certificate->qr_code_path, PATHINFO_EXTENSION));

        return in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true);
    }

    private function deleteGeneratedPdfIfExists(Certificate $certificate): void
    {
        if ($certificate->pdf_path && Storage::disk('public')->exists($certificate->pdf_path)) {
            Storage::disk('public')->delete($certificate->pdf_path);
        }

        $certificate->forceFill([
            'pdf_path' => null,
        ])->save();
    }

    private function failedResponse(Request $request, string $message, int $status)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], $status);
        }

        return back()->with('error', $message);
    }

    private function successResponse(string $message, Certificate $certificate)
    {
        return response()->json([
            'message' => $message,
            'data' => $this->certificatePayload($certificate),
            'redirect_url' => route('academic.certificates.show', $certificate),
            'show_url' => route('academic.certificates.show', $certificate),
            'download_pdf_url' => route('academic.certificates.download-pdf', $certificate),
            'verify_url' => $certificate->verification_url,
        ]);
    }

    private function certificatePayload(Certificate $certificate): array
    {
        $certificate->loadMissing(['student', 'batch', 'program', 'reportCard', 'issuer']);

        return [
            'id' => $certificate->id,
            'report_card_id' => $certificate->report_card_id,
            'student_id' => $certificate->student_id,
            'batch_id' => $certificate->batch_id,
            'program_id' => $certificate->program_id,
            'certificate_no' => $certificate->certificate_no,
            'type' => $certificate->type,
            'title' => $certificate->title,
            'issued_date' => optional($certificate->issued_date)->format('Y-m-d') ?: $certificate->issued_date,
            'completed_date' => optional($certificate->completed_date)->format('Y-m-d') ?: $certificate->completed_date,
            'final_score' => $certificate->final_score,
            'grade' => $certificate->grade,
            'public_token' => $certificate->public_token,
            'verification_url' => $certificate->verification_url,
            'qr_code_path' => $certificate->qr_code_path,
            'pdf_path' => $certificate->pdf_path,
            'status' => $certificate->status,
            'issued_at' => optional($certificate->issued_at)->toDateTimeString(),
            'student' => $certificate->student,
            'batch' => $certificate->batch,
            'program' => $certificate->program,
            'report_card' => $certificate->reportCard,
            'issuer' => $certificate->issuer,
        ];
    }
}
