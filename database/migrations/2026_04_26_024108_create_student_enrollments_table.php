<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('program_id')
                ->nullable()
                ->constrained('programs')
                ->nullOnDelete();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();

            $table->enum('status', [
                'active',
                'completed',
                'cancelled',
                'on_hold',
            ])->default('active');

            $table->enum('access_status', [
                'active',
                'suspended',
                'expired',
            ])->default('active');

            $table->enum('enrollment_source', [
                'manual',
                'payment',
                'import',
            ])->default('manual');

            $table->dateTime('enrolled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('access_expires_at')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['student_id', 'batch_id'], 'student_batch_enrollment_unique');

            $table->index(['status', 'access_status']);
            $table->index(['program_id', 'batch_id']);
            $table->index(['enrolled_at', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};