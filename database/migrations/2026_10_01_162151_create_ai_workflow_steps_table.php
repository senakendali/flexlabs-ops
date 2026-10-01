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
        Schema::create('ai_workflow_steps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workflow_id')
                ->constrained('ai_workflows')
                ->cascadeOnDelete();

            $table->foreignId('agent_id')
                ->nullable()
                ->constrained('ai_agents')
                ->nullOnDelete();

            $table->unsignedInteger('step_order');

            $table->string('name');

            $table->text('description')->nullable();

            $table->string('action', 100)->nullable();

            $table->string('status', 30)->default('pending');
            // pending | running | waiting | waiting_approval | completed | failed | skipped

            $table->json('input')->nullable();

            $table->json('output')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique([
                'workflow_id',
                'step_order',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_workflow_steps');
    }
};
