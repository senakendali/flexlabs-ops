<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Target
            |--------------------------------------------------------------------------
            | all     = semua student
            | program = student yang ikut program tertentu
            | batch   = student yang ikut batch tertentu
            */
            $table->enum('target_type', [
                'all',
                'program',
                'batch',
            ])->default('all');

            $table->foreignId('program_id')
                ->nullable()
                ->constrained('programs')
                ->nullOnDelete();

            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('batches')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Publishing
            |--------------------------------------------------------------------------
            */
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->enum('status', [
                'draft',
                'published',
                'archived',
            ])->default('draft');

            $table->boolean('is_pinned')->default(false);
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

            $table->index(['target_type', 'status'], 'ann_target_status_idx');
            $table->index(['program_id', 'status'], 'ann_program_status_idx');
            $table->index(['batch_id', 'status'], 'ann_batch_status_idx');
            $table->index(['publish_at', 'expired_at'], 'ann_publish_window_idx');
            $table->index(['is_active', 'is_pinned'], 'ann_active_pinned_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};