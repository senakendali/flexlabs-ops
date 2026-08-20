<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_registration_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_registration_id')
                ->constrained('group_registrations')
                ->cascadeOnDelete();
            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();
            $table->foreignId('student_enrollment_id')
                ->nullable()
                ->constrained('student_enrollments')
                ->nullOnDelete();
            $table->enum('status', ['pending', 'assigned', 'enrolled', 'cancelled'])
                ->default('pending');
            $table->dateTime('enrolled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['group_registration_id', 'student_id'],
                'group_registration_student_unique'
            );
            $table->index(
                ['group_registration_id', 'status'],
                'grp_reg_participants_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_registration_participants');
    }
};