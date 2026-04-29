<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_rubrics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assessment_component_id')
                ->constrained('assessment_components')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['assessment_component_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_rubrics');
    }
};