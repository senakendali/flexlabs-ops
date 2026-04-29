<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_assessment_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();

            $table->foreignId('assessment_template_id')
                ->constrained('assessment_templates')
                ->cascadeOnDelete();

            $table->foreignId('assessment_component_id')
                ->constrained('assessment_components')
                ->cascadeOnDelete();

            $table->decimal('raw_score', 5, 2)->default(0);
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('weighted_score', 6, 2)->default(0);

            $table->text('feedback')->nullable();
            $table->json('metadata')->nullable();

            $table->enum('status', [
                'draft',
                'submitted',
                'reviewed',
                'approved',
            ])->default('draft');

            $table->foreignId('assessed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assessed_at')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'student_id',
                'batch_id',
                'assessment_component_id',
            ], 'sas_student_batch_component_unique');

            $table->index(['student_id', 'batch_id'], 'sas_student_batch_idx');

            $table->index([
                'assessment_template_id',
                'assessment_component_id',
            ], 'sas_template_component_idx');

            $table->index('status', 'sas_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_assessment_scores');
    }
};