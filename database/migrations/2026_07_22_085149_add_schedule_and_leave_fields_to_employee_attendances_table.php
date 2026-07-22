<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_attendances', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Working Schedule Snapshot
            |--------------------------------------------------------------------------
            */
            $table->foreignId('working_hour_template_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('working_hour_templates')
                ->nullOnDelete();

            $table->string('working_hours_template_raw')
                ->nullable()
                ->after('working_hour_template_id');

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

            $table->string('schedule_source')
                ->nullable()
                ->index();

            $table->boolean('schedule_is_inferred')
                ->default(false)
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Arrival and Departure
            |--------------------------------------------------------------------------
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
            */
            $table->string('leave_type')
                ->nullable()
                ->index();

            $table->string('leave_duration')
                ->nullable()
                ->index();

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

            /*
            |--------------------------------------------------------------------------
            | Leave Approval Audit
            |--------------------------------------------------------------------------
            */
            $table->foreignId('leave_approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('leave_approved_at')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employee_attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'working_hour_template_id'
            );

            $table->dropConstrainedForeignId(
                'leave_approved_by'
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

                'leave_approved_at',
            ]);
        });
    }
};