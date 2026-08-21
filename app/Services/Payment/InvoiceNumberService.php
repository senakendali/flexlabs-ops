<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class InvoiceNumberService
{
    public function generate(Order $order): string
    {
        $order->loadMissing([
            'batch:id,program_id,name',
            'batch.program:id,name',
        ]);

        $batchCode = $this->resolveBatchCode($order);
        $programCode = $this->resolveProgramCode($order);

        // Ditampilkan dalam nomor invoice sampai tanggal.
        $dateCode = now()->format('Ymd');

        // Sequence tetap dihitung berdasarkan bulan.
        $monthCode = now()->format('Ym');

        // Digunakan untuk mencari seluruh invoice dalam bulan berjalan.
        $monthlyPrefix = 'FLX-'
            . $batchCode
            . '-'
            . $programCode
            . '-'
            . $monthCode;

        // Digunakan sebagai nomor invoice yang akan disimpan.
        $documentPrefix = 'FLX-'
            . $batchCode
            . '-'
            . $programCode
            . '-'
            . $dateCode
            . '-';

        /*
        * Menerima:
        * FLX-B1-AI-20260818-004 (format tanggal)
        * FLX-B1-AI-202608-005   (format bulanan/transisi)
        */
        $pattern = '/^'
            . preg_quote($monthlyPrefix, '/')
            . '(?:\d{2})?-(\d+)$/';

        $maxSequence = Payment::query()
            ->where(function ($query) use ($monthlyPrefix) {
                $query
                    // Format tanggal: FLX-B1-AI-20260818-004
                    ->where(
                        'invoice_number',
                        'like',
                        $monthlyPrefix . '__-%'
                    )

                    // Format bulanan: FLX-B1-AI-202608-005
                    ->orWhere(
                        'invoice_number',
                        'like',
                        $monthlyPrefix . '-%'
                    );
            })
            ->lockForUpdate()
            ->pluck('invoice_number')
            ->map(function ($invoiceNumber) use ($pattern) {
                return preg_match(
                    $pattern,
                    (string) $invoiceNumber,
                    $matches
                )
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?: 0;

        $nextSequence = $maxSequence + 1;

        return $documentPrefix
            . str_pad(
                (string) $nextSequence,
                3,
                '0',
                STR_PAD_LEFT
            );
    }
    
    public function generate___(Order $order): string
    {
        $order->loadMissing([
            'batch:id,program_id,name',
            'batch.program:id,name',
        ]);

        $batchCode = $this->resolveBatchCode($order);
        $programCode = $this->resolveProgramCode($order);
        $monthCode = now()->format('Ym');
        $sequencePrefix = 'FLX-' . $batchCode . '-' . $programCode . '-' . $monthCode;
        $documentPrefix = $sequencePrefix . '-';
        $pattern = '/^' . preg_quote($sequencePrefix, '/') . '(?:\d{2})?-(\d+)$/';

        $maxSequence = Payment::query()
            ->where(function ($query) use ($documentPrefix, $sequencePrefix) {
                $query
                    ->where('invoice_number', 'like', $documentPrefix . '%')
                    ->orWhere('invoice_number', 'like', $sequencePrefix . '__-%');
            })
            ->lockForUpdate()
            ->pluck('invoice_number')
            ->map(function ($invoiceNumber) use ($pattern) {
                return preg_match($pattern, (string) $invoiceNumber, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?: 0;

        return $documentPrefix . str_pad((string) ($maxSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    private function resolveBatchCode(Order $order): string
    {
        $batchName = (string) data_get($order, 'batch.name', '');
        $batchId = (int) data_get($order, 'batch.id', 0);

        if (preg_match('/\bbatch\s*0*(\d+)\b/i', $batchName, $matches)) {
            return 'B' . ((int) $matches[1]);
        }

        if (preg_match('/\bb\s*0*(\d+)\b/i', $batchName, $matches)) {
            return 'B' . ((int) $matches[1]);
        }

        return 'B' . max(1, $batchId);
    }

    private function resolveProgramCode(Order $order): string
    {
        $programName = (string) data_get($order, 'batch.program.name', '');
        $normalized = Str::of($programName)
            ->lower()
            ->replace(['/', '-', '&'], ' ')
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->toString();

        if (Str::contains($normalized, ['software engineer', 'software engineering'])) {
            return 'SE';
        }

        if (Str::contains($normalized, 'artificial intelligence') || preg_match('/\bai\b/', $normalized)) {
            return 'AI';
        }

        if (Str::contains($normalized, [
            'ui ux',
            'ui ux design',
            'ui ux designer',
            'user interface user experience',
            'user experience design',
            'design',
        ])) {
            return 'DS';
        }

        $code = collect(explode(' ', $normalized))
            ->filter()
            ->map(fn ($word) => Str::upper(Str::substr((string) $word, 0, 1)))
            ->implode('');

        if (strlen($code) < 2) {
            $code = Str::upper(Str::substr(
                preg_replace('/[^A-Za-z0-9]/', '', $programName) ?: 'FLX',
                0,
                3
            ));
        }

        return Str::substr($code, 0, 3) ?: 'FLX';
    }
}