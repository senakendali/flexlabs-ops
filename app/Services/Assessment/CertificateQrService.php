<?php

namespace App\Services\Assessment;

use App\Models\Certificate;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
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

        if (! extension_loaded('gd')) {
            throw new RuntimeException(
                'PHP GD extension belum aktif. Aktifkan extension=gd di php.ini, lalu restart Apache/PHP.'
            );
        }

        $publicToken = $certificate->public_token ?: Str::random(40);
        $verificationUrl = route('public.certificates.verify', $publicToken);

        $directory = 'certificates/qr';
        Storage::disk('public')->makeDirectory($directory);

        $filename = $this->buildFilename($certificate);
        $relativePath = $directory . '/' . $filename;
        $absolutePath = Storage::disk('public')->path($relativePath);

        /*
         * NOTE:
         * Jangan pakai QROutputInterface::GDIMAGE_PNG di sini.
         * Di beberapa versi chillerlan/php-qrcode constant outputType sudah deprecated/berubah.
         * Pakai outputInterface + QRGdImagePNG supaya lebih aman untuk versi baru.
         */
        $options = new QROptions([
            'outputInterface'  => QRGdImagePNG::class,
            'eccLevel'         => EccLevel::M,
            'scale'            => 8,
            'outputBase64'     => false,
            'imageTransparent' => false,
            'addQuietzone'     => true,
            'quietzoneSize'    => 2,
        ]);

        $qrBinary = (new QRCode($options))->render($verificationUrl);

        if (! is_string($qrBinary) || $qrBinary === '') {
            throw new RuntimeException('QR PNG gagal dibuat.');
        }

        file_put_contents($absolutePath, $qrBinary);

        if (! file_exists($absolutePath) || filesize($absolutePath) <= 0) {
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
