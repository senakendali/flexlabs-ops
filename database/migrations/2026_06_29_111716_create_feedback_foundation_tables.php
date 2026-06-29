<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_forms', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();

            // program, workshop, webinar, instructor, general
            $table->string('type')->default('program')->index();

            // Sengaja belum FK keras dulu biar aman dengan struktur existing.
            $table->unsignedBigInteger('program_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();

            $table->boolean('is_active')->default(true)->index();

            $table->dateTime('starts_at')->nullable()->index();
            $table->dateTime('ends_at')->nullable()->index();

            $table->json('settings')->nullable();

            $table->timestamps();

            $table->index(['type', 'is_active'], 'feedback_forms_type_active_index');
        });

        Schema::create('feedback_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feedback_form_id')
                ->constrained('feedback_forms')
                ->cascadeOnDelete();

            // Program, Materi, Instructor, Platform, Support, Outcome, NPS, Testimonial
            $table->string('section')->nullable()->index();

            $table->text('question_text');
            $table->text('help_text')->nullable();

            // rating_1_5, rating_0_10, text, textarea, select, radio, checkbox
            $table->string('question_type')->default('rating_1_5')->index();

            // 5 untuk rating_1_5, 10 untuk NPS, nullable untuk text/textarea.
            $table->unsignedTinyInteger('rating_scale')->nullable();

            // Untuk select/radio/checkbox.
            $table->json('options')->nullable();

            $table->boolean('is_required')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();

            $table->unsignedInteger('sort_order')->default(0)->index();

            $table->timestamps();

            $table->index(['feedback_form_id', 'section'], 'feedback_questions_form_section_index');
            $table->index(['feedback_form_id', 'sort_order'], 'feedback_questions_form_sort_index');
        });

        Schema::create('feedback_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feedback_form_id')
                ->constrained('feedback_forms')
                ->cascadeOnDelete();

            // Sengaja nullable dan belum FK keras dulu.
            $table->unsignedBigInteger('student_id')->nullable()->index();
            $table->unsignedBigInteger('program_id')->nullable()->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->unsignedBigInteger('instructor_id')->nullable()->index();

            // Untuk public link:
            // feedback.flexlabs.co.id/f/{token}
            $table->string('token', 80)->unique();

            $table->string('student_name')->nullable();
            $table->string('student_email')->nullable()->index();

            // draft, submitted
            $table->string('status')->default('draft')->index();

            // Optional summary score biar dashboard gampang.
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->unsignedTinyInteger('nps_score')->nullable();

            $table->dateTime('submitted_at')->nullable()->index();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['feedback_form_id', 'status'], 'feedback_responses_form_status_index');
            $table->index(['program_id', 'batch_id'], 'feedback_responses_program_batch_index');
        });

        Schema::create('feedback_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feedback_response_id')
                ->constrained('feedback_responses')
                ->cascadeOnDelete();

            // Nullable supaya kalau pertanyaan dihapus, jawaban historis tetap aman.
            $table->foreignId('feedback_question_id')
                ->nullable()
                ->constrained('feedback_questions')
                ->nullOnDelete();

            // Snapshot biar data historis tetap kebaca walaupun question berubah.
            $table->text('question_text_snapshot')->nullable();
            $table->string('question_type_snapshot')->nullable();

            // Untuk rating/select/radio sederhana.
            $table->string('answer_value')->nullable();

            // Untuk rating numeric / NPS.
            $table->decimal('answer_number', 8, 2)->nullable();

            // Untuk textarea/comment/testimonial.
            $table->longText('answer_text')->nullable();

            // Untuk checkbox/multiple answer atau data tambahan.
            $table->json('answer_json')->nullable();

            $table->timestamps();

            $table->unique(
                ['feedback_response_id', 'feedback_question_id'],
                'feedback_answers_response_question_unique'
            );

            $table->index(['feedback_response_id', 'feedback_question_id'], 'feedback_answers_response_question_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_answers');
        Schema::dropIfExists('feedback_responses');
        Schema::dropIfExists('feedback_questions');
        Schema::dropIfExists('feedback_forms');
    }
};