<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnDelete();

            $table->foreignId('assessment_template_id')
                ->nullable()
                ->constrained('assessment_templates')
                ->nullOnDelete();

            $table->string('report_no')->unique();

            $table->decimal('attendance_percent', 5, 2)->default(0);
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->decimal('final_score', 5, 2)->default(0);

            $table->string('grade')->nullable();

            $table->enum('status', [
                'draft',
                'passed',
                'not_passed',
                'published',
                'cancelled',
            ])->default('draft');

            $table->boolean('is_certificate_eligible')->default(false);

            $table->text('summary')->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->text('instructor_note')->nullable();
            $table->text('academic_note')->nullable();

            $table->json('score_snapshot')->nullable();
            $table->json('rubric_snapshot')->nullable();
            $table->json('rule_snapshot')->nullable();

            $table->string('pdf_path')->nullable();

            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();

            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'student_id',
                'batch_id',
            ], 'student_batch_report_card_unique');

            $table->index(['program_id', 'batch_id']);
            $table->index('status');
            $table->index('is_certificate_eligible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};