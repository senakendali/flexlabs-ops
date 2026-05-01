<?php

namespace App\Services\Assessment;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CertificateImageService
{
    protected string $templatePath;

    public function __construct(
        protected CertificateQrService $qrService
    ) {
        $this->templatePath = public_path('images/certificates/template-flexlabs-1.png');
    }

    public function generate(Certificate $certificate): Certificate
    {
        $certificate->loadMissing([
            'student',
            'program',
            'reportCard',
            'issuer',
        ]);

        if (! $certificate->qr_code_path) {
            $certificate = $this->qrService->generate($certificate);

            $certificate->loadMissing([
                'student',
                'program',
                'reportCard',
                'issuer',
            ]);
        }

        if (! file_exists($this->templatePath)) {
            throw new RuntimeException('Template certificate tidak ditemukan: public/images/certificates/template-flexlabs-1.png');
        }

        if (! extension_loaded('gd')) {
            throw new RuntimeException('PHP GD extension belum aktif. Aktifkan extension=gd di php.ini lalu restart Apache/XAMPP.');
        }

        $regularFont = $this->resolveFontPath('regular');
        $boldFont = $this->resolveFontPath('bold');

        $canvas = $this->createImageFromFile($this->templatePath);

        if (! $canvas) {
            throw new RuntimeException('Gagal membaca template certificate image. Pastikan file template PNG/JPG valid.');
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $width = imagesx($canvas);
        $height = imagesy($canvas);

        /*
        |--------------------------------------------------------------------------
        | Base template reference: 1024 x 708
        |--------------------------------------------------------------------------
        */
        $scaleX = $width / 1024;
        $scaleY = $height / 708;
        $scale = min($scaleX, $scaleY);

        $x = fn (float $value): int => (int) round($value * $scaleX);
        $y = fn (float $value): int => (int) round($value * $scaleY);
        $fs = fn (float $value): int => max(7, (int) round($value * $scale));

        $purple = $this->allocateColor($canvas, '#5B3E8E');
        $dark = $this->allocateColor($canvas, '#171327');
        $muted = $this->allocateColor($canvas, '#6F6879');
        $softBox = $this->allocateColor($canvas, '#F8F5FF');
        $softBorder = $this->allocateColor($canvas, '#D8C9F8');
        $accent = $this->allocateColor($canvas, '#FFBE04');
        $white = $this->allocateColor($canvas, '#FFFFFF');

        $studentName = $this->resolveStudentName($certificate);
        $programName = $certificate->program->name ?? '-';
        $completedDate = $this->formatDate($certificate->completed_date);
        $title = strtoupper($certificate->title ?: 'Certificate of Achievement');
        $typeLabel = ucwords(str_replace('_', ' ', $certificate->type ?: 'achievement'));

        $description = 'For successfully completing ' . $programName . ' as part of FlexLabs learning program.';
        $signerName = 'Sena Kendali';
        $signerRole = 'Academic Manager';

        /*
        |--------------------------------------------------------------------------
        | Main Heading
        |--------------------------------------------------------------------------
        | Official certificate dinaikin sedikit, title diturunin sedikit,
        | supaya jaraknya lebih lega.
        */
        $this->drawCenteredLine(
            $canvas,
            'OFFICIAL CERTIFICATE',
            (int) round($width / 2),
            $y(152),
            $boldFont,
            $fs(10),
            $purple
        );

        $titleFontSize = $this->fitFontSize(
            $title,
            $boldFont,
            $fs(35),
            $fs(24),
            $x(730)
        );

        $this->drawCenteredLine(
            $canvas,
            $title,
            (int) round($width / 2),
            $y(210),
            $boldFont,
            $titleFontSize,
            $purple
        );

        $this->drawCenteredLine(
            $canvas,
            'This certificate is proudly presented to',
            (int) round($width / 2),
            $y(252),
            $regularFont,
            $fs(15),
            $muted
        );

        $nameFontSize = $this->fitFontSize(
            $studentName,
            $boldFont,
            $fs(38),
            $fs(27),
            $x(620)
        );

        $this->drawCenteredLine(
            $canvas,
            $studentName,
            (int) round($width / 2),
            $y(302),
            $boldFont,
            $nameFontSize,
            $dark
        );

        $underlineWidth = min(
            $x(350),
            max(
                $x(240),
                $this->textWidth($studentName, $boldFont, $nameFontSize) + $x(28)
            )
        );

        imagesetthickness($canvas, max(1, (int) round(2 * $scale)));
        imageline(
            $canvas,
            (int) round(($width - $underlineWidth) / 2),
            $y(325),
            (int) round(($width + $underlineWidth) / 2),
            $y(325),
            $accent
        );
        imagesetthickness($canvas, 1);

        $this->drawCenteredParagraph(
            $canvas,
            $description,
            (int) round($width / 2),
            $y(358),
            $regularFont,
            $fs(12.5),
            $dark,
            $x(550),
            1.36
        );

        /*
        |--------------------------------------------------------------------------
        | Achievement Badge
        |--------------------------------------------------------------------------
        */
        $badgeFontSize = $fs(11.5);
        $badgePaddingX = $x(18);
        $badgeHeight = $y(30);
        $badgeTextWidth = $this->textWidth($typeLabel, $boldFont, $badgeFontSize);
        $badgeWidth = max($x(120), $badgeTextWidth + ($badgePaddingX * 2));
        $badgeX = (int) round(($width - $badgeWidth) / 2);
        $badgeY = $y(426);

        $this->drawPill(
            $canvas,
            $badgeX,
            $badgeY,
            $badgeWidth,
            $badgeHeight,
            $purple
        );

        $this->drawCenteredLine(
            $canvas,
            $typeLabel,
            (int) round($width / 2),
            $badgeY + (int) round($badgeHeight * 0.70),
            $boldFont,
            $badgeFontSize,
            $white
        );

        /*
        |--------------------------------------------------------------------------
        | Meta Boxes
        |--------------------------------------------------------------------------
        | Batch tetap dihilangkan.
        */
        $boxY = $y(478);
        $boxW = $x(245);
        $boxH = $y(68);
        $boxRadius = $x(20);

        $this->drawMetaBoxRounded(
            $canvas,
            $x(245),
            $boxY,
            $boxW,
            $boxH,
            $boxRadius,
            'PROGRAM',
            $programName,
            $regularFont,
            $boldFont,
            $softBox,
            $softBorder,
            $muted,
            $dark,
            $scale
        );

        $this->drawMetaBoxRounded(
            $canvas,
            $x(535),
            $boxY,
            $boxW,
            $boxH,
            $boxRadius,
            'COMPLETED DATE',
            $completedDate,
            $regularFont,
            $boldFont,
            $softBox,
            $softBorder,
            $muted,
            $dark,
            $scale
        );

        /*
        |--------------------------------------------------------------------------
        | Signature
        |--------------------------------------------------------------------------
        | Diturunin lagi sedikit tapi tetap aman dari bawah.
        */
        $signatureCenterX = (int) round($width / 2);
        $signatureLineWidth = $x(160);
        $signatureLineY = $y(607);

        imagesetthickness($canvas, max(1, (int) round(1.5 * $scale)));
        imageline(
            $canvas,
            (int) round($signatureCenterX - ($signatureLineWidth / 2)),
            $signatureLineY,
            (int) round($signatureCenterX + ($signatureLineWidth / 2)),
            $signatureLineY,
            $dark
        );
        imagesetthickness($canvas, 1);

        $this->drawCenteredLine(
            $canvas,
            $signerName,
            $signatureCenterX,
            $y(632),
            $boldFont,
            $fs(14.5),
            $dark
        );

        $this->drawCenteredLine(
            $canvas,
            $signerRole,
            $signatureCenterX,
            $y(654),
            $regularFont,
            $fs(10),
            $muted
        );

        /*
        |--------------------------------------------------------------------------
        | QR Code
        |--------------------------------------------------------------------------
        */
        $this->drawQrOrPlaceholder(
            $canvas,
            $certificate,
            $x(804),
            $y(568),
            $x(68),
            $regularFont,
            $boldFont,
            $fs(9),
            $muted,
            $dark,
            $softBorder,
            $white
        );

        /*
        |--------------------------------------------------------------------------
        | Save Final PNG
        |--------------------------------------------------------------------------
        */
        $directory = 'certificates/images';
        $filename = $this->buildFilename($certificate);
        $relativePath = $directory . '/' . $filename;

        Storage::disk('public')->makeDirectory($directory);

        $absolutePath = Storage::disk('public')->path($relativePath);
        $saved = imagepng($canvas, $absolutePath);

        imagedestroy($canvas);

        if (! $saved || ! file_exists($absolutePath)) {
            throw new RuntimeException('Gagal menyimpan file certificate image ke storage.');
        }

        $certificate->forceFill([
            'image_path' => $relativePath,
        ])->save();

        return $certificate->fresh([
            'student',
            'program',
            'reportCard',
            'issuer',
        ]);
    }

    public function download(Certificate $certificate)
    {
        if (! $certificate->image_path || ! Storage::disk('public')->exists($certificate->image_path)) {
            $certificate = $this->generate($certificate);
        }

        return response()->download(
            Storage::disk('public')->path($certificate->image_path),
            $this->buildFilename($certificate),
            [
                'Content-Type' => 'image/png',
            ]
        );
    }

    protected function drawQrOrPlaceholder(
        $canvas,
        Certificate $certificate,
        int $qrX,
        int $qrY,
        int $qrSize,
        string $regularFont,
        string $boldFont,
        int $captionFontSize,
        int $captionColor,
        int $textColor,
        int $borderColor,
        int $backgroundColor
    ): void {
        $hasDrawnQr = false;

        if ($certificate->qr_code_path) {
            $qrAbsolutePath = Storage::disk('public')->path($certificate->qr_code_path);

            if (file_exists($qrAbsolutePath)) {
                $extension = strtolower(pathinfo($qrAbsolutePath, PATHINFO_EXTENSION));

                if (in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                    $qrImage = $this->createImageFromFile($qrAbsolutePath);

                    if ($qrImage) {
                        imagecopyresampled(
                            $canvas,
                            $qrImage,
                            $qrX,
                            $qrY,
                            0,
                            0,
                            $qrSize,
                            $qrSize,
                            imagesx($qrImage),
                            imagesy($qrImage)
                        );

                        imagedestroy($qrImage);
                        $hasDrawnQr = true;
                    }
                }
            }
        }

        if (! $hasDrawnQr) {
            $radius = (int) round($qrSize * 0.16);

            $this->drawRoundedRectangle(
                $canvas,
                $qrX,
                $qrY,
                $qrX + $qrSize,
                $qrY + $qrSize,
                $radius,
                $backgroundColor,
                true
            );

            $this->drawRoundedRectangle(
                $canvas,
                $qrX,
                $qrY,
                $qrX + $qrSize,
                $qrY + $qrSize,
                $radius,
                $borderColor,
                false,
                2
            );

            $this->drawCenteredLine(
                $canvas,
                'QR',
                (int) round($qrX + ($qrSize / 2)),
                $qrY + (int) round($qrSize * 0.48),
                $boldFont,
                $captionFontSize + 3,
                $textColor
            );

            $this->drawCenteredLine(
                $canvas,
                'CODE',
                (int) round($qrX + ($qrSize / 2)),
                $qrY + (int) round($qrSize * 0.70),
                $boldFont,
                $captionFontSize,
                $textColor
            );
        }

        $this->drawCenteredLine(
            $canvas,
            'Scan to verify',
            (int) round($qrX + ($qrSize / 2)),
            $qrY + $qrSize + 16,
            $regularFont,
            $captionFontSize,
            $captionColor
        );
    }

    protected function buildFilename(Certificate $certificate): string
    {
        $safeCertificateNo = Str::of($certificate->certificate_no ?: 'certificate')
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->toString();

        return $safeCertificateNo . '.png';
    }

    protected function resolveStudentName(Certificate $certificate): string
    {
        $student = $certificate->student;

        $name = $student->name
            ?? $student->full_name
            ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

        return $name ?: ($student->email ?? ('Student #' . $certificate->student_id));
    }

    protected function formatDate($date): string
    {
        return $date ? $date->format('d F Y') : '-';
    }

    protected function resolveFontPath(string $type = 'regular'): string
    {
        $candidates = $type === 'bold'
            ? [
                storage_path('app/fonts/NotoSans-Bold.ttf'),
                public_path('fonts/NotoSans-Bold.ttf'),
                resource_path('fonts/NotoSans-Bold.ttf'),
                'C:\Windows\Fonts\arialbd.ttf',
                'C:\Windows\Fonts\calibrib.ttf',
                'C:\Windows\Fonts\seguisb.ttf',
            ]
            : [
                storage_path('app/fonts/NotoSans-Regular.ttf'),
                public_path('fonts/NotoSans-Regular.ttf'),
                resource_path('fonts/NotoSans-Regular.ttf'),
                'C:\Windows\Fonts\arial.ttf',
                'C:\Windows\Fonts\calibri.ttf',
                'C:\Windows\Fonts\segoeui.ttf',
            ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new RuntimeException("Font {$type} tidak ditemukan. Simpan font di storage/app/fonts atau gunakan font bawaan Windows.");
    }

    protected function createImageFromFile(string $path)
    {
        if (! file_exists($path)) {
            return null;
        }

        $contents = @file_get_contents($path);

        if ($contents === false || $contents === '') {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        if ($image !== false) {
            return $image;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => @imagecreatefrompng($path),
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    protected function allocateColor($image, string $hex, int $alpha = 0): int
    {
        [$r, $g, $b] = $this->hexToRgb($hex);

        return imagecolorallocatealpha($image, $r, $g, $b, $alpha);
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0]
                . $hex[1] . $hex[1]
                . $hex[2] . $hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    protected function drawCenteredLine(
        $image,
        string $text,
        int $centerX,
        int $baselineY,
        string $fontPath,
        int $fontSize,
        int $color
    ): void {
        $box = imagettfbbox($fontSize, 0, $fontPath, $text);
        $width = $this->bboxWidth($box);
        $x = (int) round($centerX - ($width / 2));

        imagettftext($image, $fontSize, 0, $x, $baselineY, $color, $fontPath, $text);
    }

    protected function drawCenteredParagraph(
        $image,
        string $text,
        int $centerX,
        int $topY,
        string $fontPath,
        int $fontSize,
        int $color,
        int $maxWidth,
        float $lineHeight = 1.5
    ): void {
        $lines = $this->wrapText($text, $fontPath, $fontSize, $maxWidth);
        $lineGap = (int) round($fontSize * $lineHeight);
        $currentTopY = $topY;

        foreach ($lines as $line) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $line);
            $textHeight = $this->bboxHeight($box);
            $baseline = $currentTopY + $textHeight;

            $this->drawCenteredLine(
                $image,
                $line,
                $centerX,
                $baseline,
                $fontPath,
                $fontSize,
                $color
            );

            $currentTopY += $lineGap;
        }
    }

    protected function drawMetaBoxRounded(
        $image,
        int $x,
        int $y,
        int $width,
        int $height,
        int $radius,
        string $label,
        string $value,
        string $regularFont,
        string $boldFont,
        int $fillColor,
        int $borderColor,
        int $labelColor,
        int $valueColor,
        float $scale
    ): void {
        $this->drawRoundedRectangle(
            $image,
            $x,
            $y,
            $x + $width,
            $y + $height,
            $radius,
            $fillColor,
            true
        );

        $this->drawRoundedRectangle(
            $image,
            $x,
            $y,
            $x + $width,
            $y + $height,
            $radius,
            $borderColor,
            false,
            2
        );

        $labelFontSize = max(7, (int) round(8.5 * $scale));
        $valueFontSize = max(9, (int) round(10.8 * $scale));

        $labelBaseline = $y + (int) round(18 * $scale);

        $this->drawCenteredLine(
            $image,
            strtoupper($label),
            (int) round($x + ($width / 2)),
            $labelBaseline,
            $regularFont,
            $labelFontSize,
            $labelColor
        );

        $valueMaxWidth = $width - (int) round(30 * $scale);

        while (
            $valueFontSize > max(8, (int) round(8.8 * $scale))
            && count($this->wrapText($value, $boldFont, $valueFontSize, $valueMaxWidth)) > 2
        ) {
            $valueFontSize--;
        }

        $valueLines = array_slice(
            $this->wrapText($value, $boldFont, $valueFontSize, $valueMaxWidth),
            0,
            2
        );

        $lineGap = (int) round($valueFontSize * 1.18);
        $contentAreaTop = $y + (int) round(26 * $scale);
        $contentAreaBottom = $y + $height - (int) round(8 * $scale);
        $contentAreaHeight = $contentAreaBottom - $contentAreaTop;
        $blockHeight = max(1, count($valueLines)) * $lineGap;
        $currentTop = (int) round($contentAreaTop + (($contentAreaHeight - $blockHeight) / 2));

        foreach ($valueLines as $line) {
            $box = imagettfbbox($valueFontSize, 0, $boldFont, $line);
            $textHeight = $this->bboxHeight($box);
            $baseline = $currentTop + $textHeight;

            $this->drawCenteredLine(
                $image,
                $line,
                (int) round($x + ($width / 2)),
                $baseline,
                $boldFont,
                $valueFontSize,
                $valueColor
            );

            $currentTop += $lineGap;
        }
    }

    protected function drawRoundedRectangle(
        $image,
        int $x1,
        int $y1,
        int $x2,
        int $y2,
        int $radius,
        int $color,
        bool $filled = true,
        int $thickness = 1
    ): void {
        $radius = max(1, min(
            $radius,
            (int) floor(($x2 - $x1) / 2),
            (int) floor(($y2 - $y1) / 2)
        ));

        if ($filled) {
            imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
            imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

            imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
            imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);

            return;
        }

        imagesetthickness($image, $thickness);

        imageline($image, $x1 + $radius, $y1, $x2 - $radius, $y1, $color);
        imageline($image, $x1 + $radius, $y2, $x2 - $radius, $y2, $color);
        imageline($image, $x1, $y1 + $radius, $x1, $y2 - $radius, $color);
        imageline($image, $x2, $y1 + $radius, $x2, $y2 - $radius, $color);

        imagearc($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color);
        imagearc($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color);
        imagearc($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color);
        imagearc($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color);

        imagesetthickness($image, 1);
    }

    protected function drawPill(
        $image,
        int $x,
        int $y,
        int $width,
        int $height,
        int $fillColor
    ): void {
        $radius = (int) round($height / 2);

        imagefilledrectangle(
            $image,
            $x + $radius,
            $y,
            $x + $width - $radius,
            $y + $height,
            $fillColor
        );

        imagefilledellipse(
            $image,
            $x + $radius,
            $y + $radius,
            $height,
            $height,
            $fillColor
        );

        imagefilledellipse(
            $image,
            $x + $width - $radius,
            $y + $radius,
            $height,
            $height,
            $fillColor
        );
    }

    protected function wrapText(
        string $text,
        string $fontPath,
        int $fontSize,
        int $maxWidth
    ): array {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = trim($currentLine . ' ' . $word);

            if ($this->textWidth($testLine, $fontPath, $fontSize) <= $maxWidth) {
                $currentLine = $testLine;
                continue;
            }

            if ($currentLine !== '') {
                $lines[] = $currentLine;
            }

            $currentLine = $word;
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines ?: [''];
    }

    protected function fitFontSize(
        string $text,
        string $fontPath,
        int $startSize,
        int $minSize,
        int $maxWidth
    ): int {
        $size = $startSize;

        while ($size > $minSize && $this->textWidth($text, $fontPath, $size) > $maxWidth) {
            $size--;
        }

        return $size;
    }

    protected function textWidth(string $text, string $fontPath, int $fontSize): int
    {
        $box = imagettfbbox($fontSize, 0, $fontPath, $text);

        return $this->bboxWidth($box);
    }

    protected function bboxWidth(array $box): int
    {
        $xs = [$box[0], $box[2], $box[4], $box[6]];

        return (int) round(max($xs) - min($xs));
    }

    protected function bboxHeight(array $box): int
    {
        $ys = [$box[1], $box[3], $box[5], $box[7]];

        return (int) round(max($ys) - min($ys));
    }
}