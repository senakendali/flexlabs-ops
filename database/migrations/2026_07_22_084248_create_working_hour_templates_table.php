<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hour_templates', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Template Identity
            |--------------------------------------------------------------------------
            | Contoh:
            | - Regular working hours
            | - Shift 3 (Edu)
            */
            $table->string('code')
                ->nullable()
                ->unique();

            $table->string('name')
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Scheduled Working Time
            |--------------------------------------------------------------------------
            */
            $table->time('start_time')
                ->nullable();

            $table->time('end_time')
                ->nullable();

            $table->time('break_start_time')
                ->nullable();

            $table->time('break_end_time')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Half-day Boundaries
            |--------------------------------------------------------------------------
            | Membantu menentukan batas sesi kerja pertama dan kedua.
            */
            $table->time('first_half_end_time')
                ->nullable();

            $table->time('second_half_start_time')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Working Days
            |--------------------------------------------------------------------------
            | ISO day numbers:
            | 1 Monday
            | 2 Tuesday
            | 3 Wednesday
            | 4 Thursday
            | 5 Friday
            | 6 Saturday
            | 7 Sunday
            |
            | Contoh Regular:
            | [1, 2, 3, 4, 5]
            */
            $table->json('working_days')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Attendance Rules
            |--------------------------------------------------------------------------
            */
            $table->unsignedInteger('late_tolerance_minutes')
                ->default(0);

            $table->unsignedInteger('scheduled_work_minutes')
                ->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            /*
            | attendance_import:
            | Template pertama kali ditemukan dari Excel.
            |
            | manual:
            | Dibuat atau dilengkapi HR.
            */
            $table->string('source')
                ->default('attendance_import')
                ->index();

            $table->json('metadata')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hour_templates');
    }
};