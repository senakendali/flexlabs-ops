<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('community_channel_id')
                ->constrained('community_channels')
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

            $table->string('title');
            $table->longText('body');

            $table->enum('post_type', [
                'announcement',
                'question',
                'discussion',
            ])->default('discussion');

            $table->enum('status', [
                'open',
                'answered',
                'solved',
                'closed',
            ])->default('open');

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamp('published_at')->nullable();
            $table->timestamp('solved_at')->nullable();

            $table->timestamps();

            $table->index(['community_channel_id', 'post_type']);
            $table->index(['community_channel_id', 'status']);
            $table->index(['author_type', 'author_id']);
            $table->index(['student_id']);
            $table->index(['is_pinned', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_posts');
    }
};