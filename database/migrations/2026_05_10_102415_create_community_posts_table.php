<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('community_post_id')
                ->constrained('community_posts')
                ->cascadeOnDelete();

            /**
             * Bisa diisi:
             * - student
             * - instructor
             * - admin
             */
            $table->string('author_type')->default('student');
            $table->unsignedBigInteger('author_id')->nullable();

            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();

            $table->longText('body');

            $table->boolean('is_solution')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['community_post_id']);
            $table->index(['author_type', 'author_id']);
            $table->index(['student_id']);
            $table->index(['is_solution']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_comments');
    }
};