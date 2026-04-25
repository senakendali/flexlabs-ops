<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
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

            $table->enum('assignment_type', [
                'text',
                'file',
                'link',
                'mixed',
            ])->default('mixed');

            $table->longText('instruction')->nullable();
            $table->text('attachment_url')->nullable();
            $table->text('starter_file_url')->nullable();
            $table->text('reference_url')->nullable();

            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->unsignedSmallInteger('max_score')->default(100);

            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(1);

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

        Schema::create('batch_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
                ->constrained('assignments')
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->cascadeOnDelete();

            $table->dateTime('available_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('closed_at')->nullable();

            $table->unsignedSmallInteger('max_score')->nullable();

            $table->boolean('allow_late_submission')->default(false);

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

            $table->unique(['assignment_id', 'batch_id']);
            $table->index(['batch_id', 'status', 'is_active']);
            $table->index(['due_at']);
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('assignment_id')
                ->constrained('assignments')
                ->cascadeOnDelete();

            $table->foreignId('batch_assignment_id')
                ->nullable()
                ->constrained('batch_assignments')
                ->nullOnDelete();

            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('batches')
                ->nullOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->longText('answer_text')->nullable();
            $table->text('answer_url')->nullable();
            $table->text('submitted_file')->nullable();

            $table->enum('status', [
                'draft',
                'submitted',
                'late',
                'reviewed',
                'returned',
            ])->default('draft');

            $table->unsignedSmallInteger('score')->nullable();
            $table->longText('feedback')->nullable();

            $table->dateTime('submitted_at')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('reviewed_at')->nullable();

            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
            $table->index(['student_id', 'status']);
            $table->index(['batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('batch_assignments');
        Schema::dropIfExists('assignments');
    }
};