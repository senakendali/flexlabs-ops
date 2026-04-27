<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minute_action_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('meeting_minute_id')
                ->constrained('meeting_minutes')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->foreignId('pic_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Untuk PIC eksternal / manual input
            $table->string('pic_name')->nullable();

            $table->string('priority')->default('medium');
            // low, medium, high, urgent

            $table->date('due_date')->nullable();

            $table->string('status')->default('pending');
            // pending, in_progress, done, blocked, cancelled

            $table->timestamp('completed_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('meeting_minute_id');
            $table->index('pic_user_id');
            $table->index(['status', 'priority']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minute_action_items');
    }
};