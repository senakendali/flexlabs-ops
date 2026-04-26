<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Instructor Availability Slots
        |--------------------------------------------------------------------------
        | Slot jadwal yang disediakan instructor untuk sesi 1-on-1.
        */
        Schema::create('instructor_availability_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instructor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            $table->enum('status', [
                'available',
                'booked',
                'blocked',
            ])->default('available');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['instructor_id', 'date', 'start_time', 'end_time'],
                'instructor_slot_unique'
            );

            $table->index(['instructor_id', 'date'], 'instructor_slot_date_index');
            $table->index(['status', 'is_active'], 'instructor_slot_status_index');
        });

        /*
        |--------------------------------------------------------------------------
        | Student Mentoring Sessions
        |--------------------------------------------------------------------------
        | Booking 1-on-1 antara student dan instructor.
        */
        Schema::create('student_mentoring_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('instructor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('availability_slot_id')
                ->constrained('instructor_availability_slots')
                ->cascadeOnDelete();

            $table->enum('topic_type', [
                'code_review',
                'debugging',
                'project_consultation',
                'career_portfolio',
                'lesson_discussion',
                'other',
            ])->default('lesson_discussion');

            $table->longText('notes')->nullable();
            $table->text('meeting_url')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rescheduled',
                'completed',
                'cancelled',
                'rejected',
            ])->default('pending');

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            /*
            | Satu slot cuma boleh dipakai oleh satu booking aktif.
            */
            $table->unique('availability_slot_id', 'student_mentoring_slot_unique');

            $table->index(['student_id', 'status'], 'student_mentoring_student_status_index');
            $table->index(['instructor_id', 'status'], 'student_mentoring_instructor_status_index');
            $table->index(['requested_at'], 'student_mentoring_requested_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_mentoring_sessions');
        Schema::dropIfExists('instructor_availability_slots');
    }
};