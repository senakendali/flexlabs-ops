<?php

namespace App\Services\Assessment;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificateQrService
{
    
    public function generate(Certificate $certificate): Certificate
    {
        $certificate->refresh();

        $verificationUrl = $certificate->verification_url
            ?: $this->buildVerificationUrl($certificate);

        if (! $certificate->verification_url) {
            $certificate->verification_url = $verificationUrl;
        }

        $directory = 'certificates/qr';
        $filename = $this->buildFilename($certificate);
        $path = "{$directory}/{$filename}";

        Storage::disk('public')->makeDirectory($directory);

        $qrContent = QrCode::format('svg')
            ->size(360)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($verificationUrl);

        Storage::disk('public')->put($path, $qrContent);

        $certificate->qr_code_path = $path;
        $certificate->save();

        return $certificate->fresh([
            'student',
            'batch',
            'program',
            'reportCard',
            'issuer',
        ]);
    }

    public function regenerate(Certificate $certificate): Certificate
    {
        if ($certificate->qr_code_path && Storage::disk('public')->exists($certificate->qr_code_path)) {
            Storage::disk('public')->delete($certificate->qr_code_path);
        }

        return $this->generate($certificate);
    }

    public function getPublicUrl(Certificate $certificate): ?string
    {
        if (! $certificate->qr_code_path) {
            return null;
        }

        return Storage::disk('public')->url($certificate->qr_code_path);
    }

    private function buildVerificationUrl(Certificate $certificate): string
    {
        return route('public.certificates.verify', [
            'token' => $certificate->public_token,
        ]);
    }

    private function buildFilename(Certificate $certificate): string
    {
        $safeCertificateNo = str($certificate->certificate_no)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->toString();

        return "{$safeCertificateNo}-qr.svg";
    }
}