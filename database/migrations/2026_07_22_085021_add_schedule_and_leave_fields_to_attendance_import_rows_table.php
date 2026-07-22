<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_import_rows', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Working Hour Template
            |--------------------------------------------------------------------------
            */
            $table->foreignId('working_hour_template_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('working_hour_templates')
                ->nullOnDelete();

            /*
            | Nilai asli dari Excel, misalnya:
            | - Regular working hours
            | - Shift 3 (Edu)
            */
            $table->string('working_hours_template_raw')
                ->nullable()
                ->after('working_hour_template_id');

            /*
            | Snapshot jadwal pada tanggal attendance.
            | Tetap disimpan walaupun master template berubah di masa depan.
            */
            $table->time('scheduled_start_time')
                ->nullable()
                ->after('clock_out');

            $table->time('scheduled_end_time')
                ->nullable()
                ->after('scheduled_start_time');

            $table->unsignedInteger('scheduled_work_minutes')
                ->nullable()
                ->after('scheduled_end_time');

            $table->unsignedInteger('worked_minutes')
                ->nullable()
                ->after('scheduled_work_minutes');

            /*
            | excel
            | employee_default
            | manual
            | inferred
            */
            $table->string('schedule_source')
                ->nullable()
                ->index();

            $table->boolean('schedule_is_inferred')
                ->default(false)
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Arrival and Departure Status
            |--------------------------------------------------------------------------
            | arrival_status:
            | - on_time
            | - late
            | - excused_late
            | - unknown
            | - not_applicable
            |
            | departure_status:
            | - on_time
            | - early_departure
            | - excused_early_departure
            | - unknown
            | - not_applicable
            */
            $table->string('arrival_status')
                ->default('unknown')
                ->index();

            $table->string('departure_status')
                ->default('unknown')
                ->index();

            $table->unsignedInteger('early_leave_minutes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Leave / Permission
            |--------------------------------------------------------------------------
            | leave_type:
            | - sick_leave
            | - annual_leave
            | - permission
            | - unpaid_leave
            | - other
            |
            | Half day bukan leave type. Half day disimpan di leave_duration.
            */
            $table->string('leave_type')
                ->nullable()
                ->index();

            /*
            | full_day
            | half_day
            | hourly
            */
            $table->string('leave_duration')
                ->nullable()
                ->index();

            /*
            | first_half
            | second_half
            | late_arrival
            | early_departure
            */
            $table->string('leave_session')
                ->nullable()
                ->index();

            $table->time('leave_start_time')
                ->nullable();

            $table->time('leave_end_time')
                ->nullable();

            $table->unsignedInteger('leave_minutes')
                ->nullable();

            $table->boolean('is_excused')
                ->default(false)
                ->index();

            $table->text('leave_reason')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_import_rows', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'working_hour_template_id'
            );

            $table->dropColumn([
                'working_hours_template_raw',

                'scheduled_start_time',
                'scheduled_end_time',
                'scheduled_work_minutes',
                'worked_minutes',

                'schedule_source',
                'schedule_is_inferred',

                'arrival_status',
                'departure_status',
                'early_leave_minutes',

                'leave_type',
                'leave_duration',
                'leave_session',

                'leave_start_time',
                'leave_end_time',
                'leave_minutes',

                'is_excused',
                'leave_reason',
            ]);
        });
    }
};