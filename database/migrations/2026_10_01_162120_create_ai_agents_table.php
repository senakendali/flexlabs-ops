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
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->string('role', 150);

            $table->text('description')->nullable();

            $table->longText('system_prompt')->nullable();

            $table->string('agent_type', 50)->default('specialist');
            // orchestrator | specialist

            $table->string('status', 30)->default('active');
            // active | inactive

            $table->string('runtime_state', 30)->default('idle');
            // idle | thinking | working | waiting | waiting_approval | completed | error

            $table->string('avatar')->nullable();

            $table->boolean('can_delegate')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_agents');
    }
};
