<?php

namespace App\Services\Assessment;

use App\Models\Certificate;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CertificateQrService
{
    public function generate(Certificate $certificate): Certificate
    {
        $certificate->loadMissing([
            'student',
            'program',
            'batch',
            'reportCard',
        ]);

        $publicToken = $certificate->public_token ?: Str::random(40);
        $verificationUrl = route('public.certificates.verify', $publicToken);

        $directory = 'certificates/qr';
        Storage::disk('public')->makeDirectory($directory);

        $filename = $this->buildFilename($certificate);
        $relativePath = $directory . '/' . $filename;
        $absolutePath = Storage::disk('public')->path($relativePath);

        $options = new QROptions([
            'outputType'       => QROutputInterface::GDIMAGE_PNG,
            'eccLevel'         => QRCode::ECC_M,
            'scale'            => 8,
            'imageBase64'      => false,
            'imageTransparent' => false,
            'addQuietzone'     => true,
            'quietzoneSize'    => 2,
        ]);

        $qrBinary = (new QRCode($options))->render($verificationUrl);

        if (! $qrBinary) {
            throw new RuntimeException('QR PNG gagal dibuat.');
        }

        file_put_contents($absolutePath, $qrBinary);

        if (! file_exists($absolutePath)) {
            throw new RuntimeException('File QR PNG gagal disimpan.');
        }

        $certificate->forceFill([
            'public_token' => $publicToken,
            'verification_url' => $verificationUrl,
            'qr_code_path' => $relativePath,
        ])->save();

        return $certificate->fresh([
            'student',
            'program',
            'batch',
            'reportCard',
        ]);
    }

    public function regenerate(Certificate $certificate): Certificate
    {
        if ($certificate->qr_code_path && Storage::disk('public')->exists($certificate->qr_code_path)) {
            Storage::disk('public')->delete($certificate->qr_code_path);
        }

        $certificate->forceFill([
            'qr_code_path' => null,
        ])->save();

        return $this->generate($certificate);
    }

    protected function buildFilename(Certificate $certificate): string
    {
        $base = Str::of($certificate->certificate_no ?: ('certificate-' . $certificate->id))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->toString();

        return $base . '-qr.png';
    }
}