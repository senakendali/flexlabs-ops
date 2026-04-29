<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_rubric_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_assessment_score_id')
                ->constrained('student_assessment_scores')
                ->cascadeOnDelete();

            $table->foreignId('assessment_rubric_criteria_id')
                ->constrained('assessment_rubric_criteria')
                ->cascadeOnDelete();

            $table->decimal('raw_score', 5, 2)->default(0);
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('weighted_score', 6, 2)->default(0);

            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique([
                'student_assessment_score_id',
                'assessment_rubric_criteria_id',
            ], 'student_rubric_score_unique');

            $table->index('student_assessment_score_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_rubric_scores');
    }
};