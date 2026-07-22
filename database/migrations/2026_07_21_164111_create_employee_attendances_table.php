<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Import Reference
            |--------------------------------------------------------------------------
            | Boleh null untuk attendance yang dibuat manual atau API.
            */
            $table->foreignId('attendance_import_id')
                ->nullable()
                ->constrained('attendance_imports')
                ->nullOnDelete();

            $table->foreignId('attendance_import_row_id')
                ->nullable()
                ->constrained('attendance_import_rows')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Employee
            |--------------------------------------------------------------------------
            */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->date('attendance_date')
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
            | Attendance Status
            |--------------------------------------------------------------------------
            */
            $table->string('attendance_type')
                ->default('present')
                ->index();

            $table->string('punctuality_status')
                ->default('unknown')
                ->index();

            $table->unsignedInteger('late_minutes')
                ->nullable();

            /*
            | - excel
            | - generated_gap
            | - manual
            | - api
            */
            $table->string('source')
                ->default('excel')
                ->index();

            $table->text('remarks')
                ->nullable();

            $table->json('metadata')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit User
            |--------------------------------------------------------------------------
            */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | One Attendance Per Employee Per Day
            |--------------------------------------------------------------------------
            | Sesuai format Evertime saat ini: satu row per employee per tanggal.
            */
            $table->unique(
                [
                    'employee_id',
                    'attendance_date',
                ],
                'employee_attendances_employee_date_unique'
            );

            $table->index(
                [
                    'attendance_date',
                    'attendance_type',
                ],
                'employee_attendances_date_type_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendances');
    }
};