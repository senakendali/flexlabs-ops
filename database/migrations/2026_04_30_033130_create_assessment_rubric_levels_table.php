<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_rubric_levels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_rubric_id')
                ->constrained('assessment_rubrics')
                ->cascadeOnDelete();

            $table->string('name');

            $table->decimal('min_score', 5, 2)->default(0);
            $table->decimal('max_score', 5, 2)->default(100);

            $table->text('description')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['assessment_rubric_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_rubric_levels');
    }
};