<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');
            $table->enum('schedule_type', [
                'kickoff',
                'live_session',
                'assignment_deadline',
                'quiz_deadline',
                'mentoring',
                'replacement_class',
                'assessment',
                'final_presentation',
                'holiday',
                'other',
            ]);

            $table->date('schedule_date');
            $table->boolean('is_all_day')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->foreignId('instructor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->enum('delivery_mode', [
                'online',
                'offline',
                'hybrid',
            ]);

            $table->string('meeting_link', 2048)->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', [
                'scheduled',
                'completed',
                'cancelled',
                'rescheduled',
            ])->default('scheduled');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            $table->index(
                ['schedule_date', 'status'],
                'academic_schedules_date_status_index'
            );
            $table->index(
                ['batch_id', 'schedule_date'],
                'academic_schedules_batch_date_index'
            );
            $table->index(
                ['program_id', 'schedule_date'],
                'academic_schedules_program_date_index'
            );
            $table->index(
                ['instructor_id', 'schedule_date'],
                'academic_schedules_instructor_date_index'
            );
            $table->index(
                ['schedule_type', 'schedule_date'],
                'academic_schedules_type_date_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_schedules');
    }
};