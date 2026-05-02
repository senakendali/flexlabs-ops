<?php

namespace App\Services\Assessment;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificatePdfService
{
    public function __construct(
        protected CertificateQrService $qrService
    ) {
    }

    public function generate(Certificate $certificate): Certificate
    {
        $certificate->loadMissing([
            'student',
            'batch',
            'program',
            'reportCard',
            'issuer',
        ]);

        if (! $certificate->qr_code_path || ! Storage::disk('public')->exists($certificate->qr_code_path)) {
            $certificate = $this->qrService->generate($certificate);

            $certificate->loadMissing([
                'student',
                'batch',
                'program',
                'reportCard',
                'issuer',
            ]);
        }

        $directory = 'certificates/pdf';
        $filename = $this->buildFilename($certificate);
        $path = "{$directory}/{$filename}";

        Storage::disk('public')->makeDirectory($directory);

        $pdf = Pdf::loadView('academic.certificates.pdf', [
            'certificate' => $certificate,
            'logoDataUri' => $this->resolvePublicImageDataUri('images/logo-black.png'),
            'qrDataUri' => $this->resolveStorageImageDataUri($certificate->qr_code_path),
        ])
            ->setPaper('a4', 'landscape')
            ->setWarnings(false);

        Storage::disk('public')->put($path, $pdf->output());

        $certificate->forceFill([
            'pdf_path' => $path,
        ])->save();

        return $certificate->fresh([
            'student',
            'batch',
            'program',
            'reportCard',
            'issuer',
        ]);
    }

    public function download(Certificate $certificate)
    {
        $certificate = $this->generate($certificate);

        $absolutePath = Storage::disk('public')->path($certificate->pdf_path);

        return response()->download(
            $absolutePath,
            $this->buildFilename($certificate),
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }

    public function stream(Certificate $certificate)
    {
        $certificate = $this->generate($certificate);

        $absolutePath = Storage::disk('public')->path($certificate->pdf_path);

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function buildFilename(Certificate $certificate): string
    {
        $safeCertificateNo = Str::of($certificate->certificate_no ?: ('certificate-' . $certificate->id))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->toString();

        return "{$safeCertificateNo}.pdf";
    }

    private function resolvePublicImageDataUri(string $relativePath): ?string
    {
        $path = public_path($relativePath);

        return $this->fileToDataUri($path);
    }

    private function resolveStorageImageDataUri(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $path = Storage::disk('public')->path($relativePath);

        return $this->fileToDataUri($path);
    }

    private function fileToDataUri(?string $path): ?string
    {
        if (! $path || ! file_exists($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}