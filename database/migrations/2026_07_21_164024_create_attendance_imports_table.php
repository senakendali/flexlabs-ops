<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_imports', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Uploaded File
            |--------------------------------------------------------------------------
            */
            $table->string('original_file_name');
            $table->string('stored_file_path');

            $table->string('sheet_name')
                ->default('Attendance');

            /*
            |--------------------------------------------------------------------------
            | Detected Period
            |--------------------------------------------------------------------------
            */
            $table->date('date_from')
                ->nullable()
                ->index();

            $table->date('date_to')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Import Statistics
            |--------------------------------------------------------------------------
            */
            $table->unsignedInteger('total_rows')
                ->default(0);

            $table->unsignedInteger('imported_rows')
                ->default(0);

            /*
            | Row yang dibuat sistem karena tanggal kerja tidak ditemukan.
            */
            $table->unsignedInteger('generated_rows')
                ->default(0);

            $table->unsignedInteger('valid_rows')
                ->default(0);

            $table->unsignedInteger('review_rows')
                ->default(0);

            $table->unsignedInteger('error_rows')
                ->default(0);

            $table->unsignedInteger('duplicate_rows')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Import Lifecycle
            |--------------------------------------------------------------------------
            | uploaded:
            | File baru berhasil disimpan.
            |
            | reviewing:
            | File selesai dibaca dan menunggu review HR.
            |
            | processing:
            | Data sedang dipindahkan ke employee_attendances.
            |
            | completed:
            | Import berhasil dikonfirmasi.
            |
            | failed:
            | Parsing atau finalisasi gagal.
            |
            | cancelled:
            | Import dibatalkan HR.
            */
            $table->string('status')
                ->default('uploaded')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | User Tracking
            |--------------------------------------------------------------------------
            */
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('imported_at')
                ->nullable();

            $table->timestamp('confirmed_at')
                ->nullable();

            $table->text('failure_message')
                ->nullable();

            /*
            | Contoh settings:
            | {
            |   "late_after": "08:00",
            |   "duplicate_action": "update",
            |   "working_days": [1,2,3,4,5]
            | }
            */
            $table->json('settings')
                ->nullable();

            /*
            | Ringkasan hasil parsing atau validasi.
            */
            $table->json('summary')
                ->nullable();

            $table->timestamps();

            $table->index([
                'status',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_imports');
    }
};