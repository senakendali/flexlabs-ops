<?php

namespace App\Services\Assessment;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

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

        if (! $certificate->qr_code_path) {
            $certificate = $this->qrService->generate($certificate);
        }

        $directory = 'certificates/pdf';
        $filename = $this->buildFilename($certificate);
        $path = "{$directory}/{$filename}";
        $absolutePath = Storage::disk('public')->path($path);

        Storage::disk('public')->makeDirectory($directory);

        $html = view('academic.certificates.pdf', [
            'certificate' => $certificate,
            'logoDataUri' => $this->resolvePublicImageDataUri('images/logo-black.png'),
            'qrDataUri' => $this->resolveStorageImageDataUri($certificate->qr_code_path),
        ])->render();

        $browser = Browsershot::html($html)
            ->format('A4')
            ->landscape()
            ->showBackground()
            ->margins(0, 0, 0, 0)
            ->waitUntilNetworkIdle()
            ->emulateMedia('screen');

        // Kalau nanti di Linux/server ada issue sandbox, aktifkan ini:
        // $browser->noSandbox();

        $browser->savePdf($absolutePath);

        $certificate->pdf_path = $path;
        $certificate->save();

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
        $safeCertificateNo = Str::of($certificate->certificate_no)
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