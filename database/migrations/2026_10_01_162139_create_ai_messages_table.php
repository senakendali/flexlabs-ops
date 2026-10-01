<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')
                ->constrained('ai_conversations')
                ->cascadeOnDelete();

            $table->foreignId('agent_id')
                ->nullable()
                ->constrained('ai_agents')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('sender_type', 30);
            // user | agent | system

            $table->string('message_type', 30)->default('text');
            // text | status | tool_result | approval

            $table->longText('content')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index([
                'conversation_id',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
