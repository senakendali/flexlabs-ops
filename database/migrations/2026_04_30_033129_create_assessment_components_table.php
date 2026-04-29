<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_template_id')
                ->constrained('assessment_templates')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code');

            $table->enum('type', [
                'attendance',
                'progress',
                'quiz',
                'assignment',
                'project',
                'attitude',
                'custom',
            ])->default('custom');

            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('max_score', 5, 2)->default(100);

            $table->boolean('is_required')->default(true);
            $table->boolean('is_auto_calculated')->default(false);

            $table->unsignedInteger('sort_order')->default(0);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['assessment_template_id', 'code']);
            $table->index(['assessment_template_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_components');
    }
};