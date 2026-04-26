<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_lesson_progresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('sub_topic_id')
                ->constrained('sub_topics')
                ->cascadeOnDelete();

            $table->unsignedInteger('last_position_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();

            $table->decimal('progress_percentage', 5, 2)->default(0);

            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_watched_at')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'sub_topic_id'], 'student_lesson_progress_unique');
            $table->index(['student_id', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_lesson_progresses');
    }
};