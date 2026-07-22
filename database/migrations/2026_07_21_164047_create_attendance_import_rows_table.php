<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_import_rows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_import_id')
                ->constrained('attendance_imports')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Employee Matching
            |--------------------------------------------------------------------------
            | Boleh null selama row belum berhasil dicocokkan dengan master employee.
            */
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Source Row Information
            |--------------------------------------------------------------------------
            */
            $table->unsignedInteger('row_number')
                ->nullable();

            /*
            | Dapat berisi hash atau identifier internal untuk mendeteksi duplicate.
            */
            $table->string('source_row_key')
                ->nullable();

            $table->date('attendance_date')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Raw Employee Values
            |--------------------------------------------------------------------------
            | employee_number_raw dapat berisi nilai seperti "Sick Leave".
            */
            $table->string('employee_number_raw')
                ->nullable();

            $table->string('employee_name_raw')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Normalized Employee Values
            |--------------------------------------------------------------------------
            */
            $table->string('employee_number', 100)
                ->nullable()
                ->index();

            $table->string('employee_name')
                ->nullable()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Attendance Time
            |--------------------------------------------------------------------------
            */
            $table->time('clock_in')
                ->nullable();

            $table->time('clock_out')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Attendance Classification
            |--------------------------------------------------------------------------
            | attendance_type:
            | - present
            | - sick_leave
            | - annual_leave
            | - permission
            | - absent
            | - missing
            | - off_day
            | - holiday
            | - unknown
            */
            $table->string('attendance_type')
                ->default('unknown')
                ->index();

            /*
            | punctuality_status:
            | - on_time
            | - late
            | - unknown
            | - not_applicable
            */
            $table->string('punctuality_status')
                ->default('unknown')
                ->index();

            $table->unsignedInteger('late_minutes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Row Source
            |--------------------------------------------------------------------------
            | - excel
            | - generated_gap
            | - manual
            */
            $table->string('source')
                ->default('excel')
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Review Status
            |--------------------------------------------------------------------------
            | - valid
            | - needs_review
            | - resolved
            | - ignored
            | - error
            | - duplicate
            */
            $table->string('review_status')
                ->default('valid')
                ->index();

            $table->text('validation_message')
                ->nullable();

            $table->text('remarks')
                ->nullable();

            /*
            | Menyimpan semua kolom mentah dari Excel.
            */
            $table->json('raw_payload')
                ->nullable();

            /*
            | Menyimpan informasi perubahan manual HR.
            */
            $table->json('resolution_metadata')
                ->nullable();

            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('resolved_at')
                ->nullable();

            $table->timestamps();

            $table->index(
                [
                    'attendance_import_id',
                    'review_status',
                ],
                'attendance_import_rows_import_review_index'
            );

            $table->index(
                [
                    'attendance_import_id',
                    'employee_id',
                    'attendance_date',
                ],
                'attendance_import_rows_employee_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_import_rows');
    }
};