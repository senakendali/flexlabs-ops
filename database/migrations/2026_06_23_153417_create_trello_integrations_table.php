<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trello_integrations', function (Blueprint $table) {
            $table->id();

            // Internal identity
            $table->string('source_key')->unique(); 
            // contoh: marketing, academic

            $table->string('name');
            // contoh: Marketing Trello, Academic Trello

            $table->string('department')->nullable();
            // contoh: marketing, academic

            // Trello workspace / organization info
            $table->string('trello_workspace_id')->nullable()->index();
            $table->string('trello_workspace_name')->nullable();

            // Trello board info
            $table->string('trello_board_id')->unique();
            $table->string('trello_board_name')->nullable();

            // Token owner info
            $table->string('token_owner_name')->nullable();
            $table->string('token_owner_email')->nullable();

            // Credentials - encrypted by model cast
            $table->text('api_key')->nullable();
            $table->text('api_token')->nullable();

            // Webhook info
            $table->string('webhook_id')->nullable()->unique();
            $table->text('callback_url')->nullable();

            // Integration status
            $table->string('sync_mode')->default('webhook');
            $table->string('status')->default('pending');
            // pending, active, inactive, error

            $table->boolean('is_active')->default(true)->index();

            // Tracking
            $table->timestamp('last_registered_at')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->text('last_error')->nullable();

            // Flexible fields
            $table->json('settings')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_key', 'is_active']);
            $table->index(['department', 'is_active']);
            $table->index(['trello_board_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trello_integrations');
    }
};