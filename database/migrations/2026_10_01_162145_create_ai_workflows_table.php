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
        Schema::create('ai_workflows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')
                ->nullable()
                ->constrained('ai_conversations')
                ->nullOnDelete();

            $table->foreignId('requested_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('orchestrator_agent_id')
                ->nullable()
                ->constrained('ai_agents')
                ->nullOnDelete();

            $table->string('division', 50)->index();

            $table->string('title');

            $table->text('objective')->nullable();

            $table->string('status', 30)->default('pending');
            // pending | running | waiting | waiting_approval | completed | failed | cancelled

            $table->json('context')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_workflows');
    }
};
