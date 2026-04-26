<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_teaching_scopes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instructor_id')
                ->constrained('instructors')
                ->cascadeOnDelete();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('batches')
                ->nullOnDelete();

            $table->enum('teaching_role', [
                'primary_instructor',
                'assistant_instructor',
                'mentor',
                'reviewer',
            ])->default('mentor');

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['instructor_id', 'program_id', 'batch_id', 'teaching_role'],
                'teaching_scope_unique'
            );

            $table->index(['instructor_id', 'status'], 'scope_instructor_status_idx');
            $table->index(['program_id', 'batch_id'], 'scope_program_batch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_teaching_scopes');
    }
};