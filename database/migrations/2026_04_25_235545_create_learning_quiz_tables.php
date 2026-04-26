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
        | Learning Quizzes
        |--------------------------------------------------------------------------
        | Master quiz untuk LMS / learning activity.
        */
        Schema::create('learning_quizzes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('topic_id')
                ->nullable()
                ->constrained('topics')
                ->nullOnDelete();

            $table->foreignId('sub_topic_id')
                ->nullable()
                ->constrained('sub_topics')
                ->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();

            $table->longText('instruction')->nullable();

            $table->enum('quiz_type', [
                'practice',
                'graded',
            ])->default('graded');

            $table->unsignedSmallInteger('duration_minutes')->nullable();

            $table->unsignedSmallInteger('passing_score')->default(70);
            $table->unsignedSmallInteger('max_attempts')->default(1);

            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_options')->default(false);

            $table->boolean('show_result_after_submit')->default(true);
            $table->boolean('show_correct_answer_after_submit')->default(false);

            $table->enum('status', [
                'draft',
                'published',
                'archived',
            ])->default('draft');

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['topic_id', 'sub_topic_id']);
            $table->index(['status', 'is_active']);
        });

        /*
        |--------------------------------------------------------------------------
        | Learning Quiz Questions
        |--------------------------------------------------------------------------
        | Pertanyaan untuk quiz.
        */
        Schema::create('learning_quiz_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('learning_quiz_id')
                ->constrained('learning_quizzes')
                ->cascadeOnDelete();

            $table->longText('question_text');

            $table->enum('question_type', [
                'single_choice',
                'multiple_choice',
                'true_false',
                'short_answer',
            ])->default('single_choice');

            $table->longText('explanation')->nullable();

            $table->unsignedSmallInteger('score')->default(1);
            $table->unsignedInteger('sort_order')->default(1);

            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['learning_quiz_id', 'question_type']);
            $table->index(['sort_order', 'is_active']);
        });

        /*
        |--------------------------------------------------------------------------
        | Learning Quiz Options
        |--------------------------------------------------------------------------
        | Pilihan jawaban untuk single choice, multiple choice, dan true/false.
        */
        Schema::create('learning_quiz_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('learning_quiz_question_id')
                ->constrained('learning_quiz_questions')
                ->cascadeOnDelete();

            $table->longText('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order')->default(1);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['learning_quiz_question_id', 'is_correct']);
            $table->index(['sort_order', 'is_active']);
        });

        /*
        |--------------------------------------------------------------------------
        | Batch Learning Quizzes
        |--------------------------------------------------------------------------
        | Quiz yang diberikan ke batch tertentu + deadline.
        */
        Schema::create('batch_learning_quizzes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('learning_quiz_id')
                ->constrained('learning_quizzes')
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();

            $table->dateTime('available_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('closed_at')->nullable();

            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('passing_score')->nullable();
            $table->unsignedSmallInteger('max_attempts')->nullable();

            $table->boolean('allow_late_attempt')->default(false);

            $table->enum('status', [
                'draft',
                'published',
                'closed',
                'archived',
            ])->default('draft');

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['learning_quiz_id', 'batch_id'], 'batch_learning_quiz_unique');

            $table->index(['batch_id', 'status', 'is_active']);
            $table->index(['available_at', 'due_at', 'closed_at']);
        });

        /*
        |--------------------------------------------------------------------------
        | Learning Quiz Attempts
        |--------------------------------------------------------------------------
        | Percobaan pengerjaan quiz oleh student.
        */
        Schema::create('learning_quiz_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_learning_quiz_id')
                ->constrained('batch_learning_quizzes')
                ->cascadeOnDelete();

            $table->foreignId('learning_quiz_id')
                ->constrained('learning_quizzes')
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('attempt_number')->default(1);

            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();

            $table->unsignedInteger('duration_seconds')->nullable();

            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();

            $table->boolean('is_passed')->nullable();

            $table->enum('status', [
                'in_progress',
                'submitted',
                'graded',
                'expired',
                'cancelled',
            ])->default('in_progress');

            $table->foreignId('graded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('graded_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['batch_learning_quiz_id', 'student_id', 'attempt_number'],
                'student_quiz_attempt_unique'
            );

            $table->index(['student_id', 'status']);
            $table->index(['learning_quiz_id', 'batch_id']);
            $table->index(['submitted_at', 'graded_at']);
        });

        /*
        |--------------------------------------------------------------------------
        | Learning Quiz Answers
        |--------------------------------------------------------------------------
        | Jawaban student per question.
        */
        Schema::create('learning_quiz_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('learning_quiz_attempt_id')
                ->constrained('learning_quiz_attempts')
                ->cascadeOnDelete();

            $table->foreignId('learning_quiz_question_id')
                ->constrained('learning_quiz_questions')
                ->cascadeOnDelete();

            $table->foreignId('learning_quiz_option_id')
                ->nullable()
                ->constrained('learning_quiz_options')
                ->nullOnDelete();

            $table->json('selected_option_ids')->nullable();

            $table->longText('answer_text')->nullable();

            $table->boolean('is_correct')->nullable();

            $table->decimal('score', 8, 2)->nullable();
            $table->longText('feedback')->nullable();

            $table->timestamps();

            $table->unique(
                ['learning_quiz_attempt_id', 'learning_quiz_question_id'],
                'attempt_question_unique'
            );

            $table->index(['learning_quiz_question_id', 'is_correct']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_quiz_answers');
        Schema::dropIfExists('learning_quiz_attempts');
        Schema::dropIfExists('batch_learning_quizzes');
        Schema::dropIfExists('learning_quiz_options');
        Schema::dropIfExists('learning_quiz_questions');
        Schema::dropIfExists('learning_quizzes');
    }
};