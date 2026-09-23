<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillStudentIds extends Command
{
    protected $signature = 'students:backfill-student-id
                            {--dry-run : Preview changes without updating students}';

    protected $description = 'Backfill Student ID from each student\'s first invoice';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->newLine();

        $this->info(
            $dryRun
                ? 'Running Student ID backfill in DRY RUN mode...'
                : 'Running Student ID backfill...'
        );

        $this->newLine();

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'ready' => 0,
            'skipped_existing' => 0,
            'skipped_no_payment' => 0,
            'skipped_invalid_invoice' => 0,
            'skipped_duplicate' => 0,
        ];

        Student::query()
            ->select([
                'id',
                'student_id',
                'full_name',
                'email',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($students) use ($dryRun, &$stats) {
                foreach ($students as $student) {
                    $stats['processed']++;

                    /*
                    |--------------------------------------------------------------------------
                    | Existing Student ID
                    |--------------------------------------------------------------------------
                    |
                    | Jangan overwrite Student ID yang sudah terbentuk.
                    |
                    */
                    if (filled($student->student_id)) {
                        $stats['skipped_existing']++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | First Payment / Invoice
                    |--------------------------------------------------------------------------
                    |
                    | Cari payment paling pertama dari seluruh order milik student,
                    | bukan hanya order terakhir.
                    |
                    */
                    $firstPayment = Payment::query()
                        ->whereHas('order', function ($query) use ($student) {
                            $query->where(
                                'student_id',
                                $student->id
                            );
                        })
                        ->whereNotNull('invoice_number')
                        ->where('invoice_number', '<>', '')
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->first([
                            'id',
                            'order_id',
                            'invoice_number',
                            'created_at',
                        ]);

                    if (!$firstPayment) {
                        $stats['skipped_no_payment']++;

                        $this->warn(
                            sprintf(
                                '[SKIP] #%d %s - No invoice found',
                                $student->id,
                                $student->full_name
                            )
                        );

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Generate Student ID
                    |--------------------------------------------------------------------------
                    */
                    $studentId = $this->generateStudentIdFromPayment(
                        $firstPayment
                    );

                    if ($studentId === null) {
                        $stats['skipped_invalid_invoice']++;

                        $this->warn(
                            sprintf(
                                '[SKIP] #%d %s - Unsupported invoice: %s',
                                $student->id,
                                $student->full_name,
                                $firstPayment->invoice_number
                            )
                        );

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Duplicate Guard
                    |--------------------------------------------------------------------------
                    */
                    $duplicateExists = Student::query()
                        ->where(
                            'student_id',
                            $studentId
                        )
                        ->where(
                            'id',
                            '<>',
                            $student->id
                        )
                        ->exists();

                    if ($duplicateExists) {
                        $stats['skipped_duplicate']++;

                        $this->error(
                            sprintf(
                                '[SKIP] #%d %s - Student ID %s already exists',
                                $student->id,
                                $student->full_name,
                                $studentId
                            )
                        );

                        continue;
                    }

                    if ($dryRun) {
                        $stats['ready']++;

                        $this->line(
                            sprintf(
                                '[READY] #%d %s | %s → %s',
                                $student->id,
                                $student->full_name,
                                $firstPayment->invoice_number,
                                $studentId
                            )
                        );

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Update Student
                    |--------------------------------------------------------------------------
                    */
                    DB::transaction(function () use (
                        $student,
                        $studentId
                    ) {
                        $lockedStudent = Student::query()
                            ->whereKey($student->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        /*
                        | Student ID mungkin sudah diisi oleh proses lain ketika
                        | command sedang berjalan. Kalau sudah ada, jangan overwrite.
                        */
                        if (filled($lockedStudent->student_id)) {
                            return;
                        }

                        $lockedStudent->update([
                            'student_id' => $studentId,
                        ]);
                    });

                    $stats['updated']++;

                    $this->info(
                        sprintf(
                            '[UPDATED] #%d %s | %s → %s',
                            $student->id,
                            $student->full_name,
                            $firstPayment->invoice_number,
                            $studentId
                        )
                    );
                }
            });

        $this->newLine();

        $this->table(
            [
                'Result',
                'Total',
            ],
            [
                ['Processed', $stats['processed']],
                [
                    $dryRun ? 'Ready to update' : 'Updated',
                    $dryRun
                        ? $stats['ready']
                        : $stats['updated'],
                ],
                ['Already has Student ID', $stats['skipped_existing']],
                ['No invoice', $stats['skipped_no_payment']],
                ['Unsupported invoice', $stats['skipped_invalid_invoice']],
                ['Duplicate Student ID', $stats['skipped_duplicate']],
            ]
        );

        $this->newLine();

        if ($dryRun) {
            $this->comment(
                'Dry run finished. No student records were changed.'
            );

            $this->comment(
                'Run without --dry-run after reviewing the results.'
            );
        } else {
            $this->info('Student ID backfill completed.');
        }

        return self::SUCCESS;
    }

    private function generateStudentIdFromPayment(
        Payment $payment
    ): ?string {
        $invoiceNumber = trim(
            (string) $payment->invoice_number
        );

        if ($invoiceNumber === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Current Invoice Format
        |--------------------------------------------------------------------------
        |
        | FLX-B4-SE-20260923-009
        |
        | menjadi:
        |
        | B4SE20260923-009
        |
        */
        if (
            preg_match(
                '/^FLX-(B\d+)-([A-Z0-9]+)-(\d{8})-(\d+)$/i',
                $invoiceNumber,
                $matches
            ) === 1
        ) {
            return Str::upper($matches[1])
                . Str::upper($matches[2])
                . $matches[3]
                . '-'
                . $matches[4];
        }

        /*
        |--------------------------------------------------------------------------
        | Legacy Monthly Invoice Format
        |--------------------------------------------------------------------------
        |
        | Existing system pernah menggunakan format:
        |
        | FLX-B4-SE-202608-009
        |
        | Format tersebut tidak mempunyai tanggal hari.
        |
        | Untuk backfill, tanggal invoice diambil dari created_at payment:
        |
        | FLX-B4-SE-202608-009
        | created_at: 2026-08-13
        |
        | menjadi:
        |
        | B4SE20260813-009
        |
        */
        if (
            preg_match(
                '/^FLX-(B\d+)-([A-Z0-9]+)-(\d{6})-(\d+)$/i',
                $invoiceNumber,
                $matches
            ) === 1
        ) {
            if (!$payment->created_at) {
                return null;
            }

            $dateCode = $payment->created_at->format(
                'Ymd'
            );

            return Str::upper($matches[1])
                . Str::upper($matches[2])
                . $dateCode
                . '-'
                . $matches[4];
        }

        return null;
    }
}