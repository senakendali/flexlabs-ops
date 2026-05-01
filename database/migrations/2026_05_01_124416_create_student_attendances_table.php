<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Main Relations
            |--------------------------------------------------------------------------
            | Attendance dicatat berdasarkan jadwal instructor/live session.
            */
            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();

            $table->foreignId('instructor_schedule_id')
                ->constrained('instructor_schedules')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Attendance Status
            |--------------------------------------------------------------------------
            | present  = hadir tepat waktu
            | late     = hadir terlambat
            | excused  = izin
            | absent   = tidak hadir
            */
            $table->enum('status', [
                'present',
                'late',
                'excused',
                'absent',
            ])->default('absent');

            /*
            |--------------------------------------------------------------------------
            | Attendance Mode
            |--------------------------------------------------------------------------
            | offline = hadir onsite / kelas langsung
            | online  = hadir via meeting online
            |
            | Nullable karena absent / excused tidak perlu mode.
            */
            $table->enum('attendance_mode', [
                'offline',
                'online',
            ])->nullable();

            /*
            |--------------------------------------------------------------------------
            | Attendance Detail
            |--------------------------------------------------------------------------
            */
            $table->timestamp('attended_at')->nullable();
            $table->text('notes')->nullable();

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
            | Constraints & Indexes
            |--------------------------------------------------------------------------
            | 1 student cuma boleh punya 1 attendance record per schedule.
            */
            $table->unique(
                ['instructor_schedule_id', 'student_id'],
                'std_att_schedule_student_unique'
            );

            $table->index(
                ['batch_id', 'student_id'],
                'std_att_batch_student_index'
            );

            $table->index(
                ['instructor_schedule_id', 'status'],
                'std_att_schedule_status_index'
            );

            $table->index(
                ['attendance_mode', 'status'],
                'std_att_mode_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};