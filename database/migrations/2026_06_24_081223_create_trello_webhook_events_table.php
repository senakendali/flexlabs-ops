<?php

use App\Models\TrelloIntegration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trello_webhook_events', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(TrelloIntegration::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('source_key')->nullable()->index();

            // Trello references
            $table->string('trello_board_id')->nullable()->index();
            $table->string('trello_action_id')->nullable()->unique();
            $table->string('trello_action_type')->nullable()->index();

            $table->string('trello_card_id')->nullable()->index();
            $table->text('trello_card_name')->nullable();

            $table->string('trello_list_id')->nullable()->index();
            $table->string('trello_list_name')->nullable();

            // Actor / creator
            $table->string('trello_member_creator_id')->nullable()->index();
            $table->string('trello_member_creator_name')->nullable();
            $table->string('trello_member_creator_username')->nullable();

            // Time
            $table->timestamp('happened_at')->nullable()->index();
            $table->timestamp('received_at')->nullable()->index();

            // Processing state
            $table->string('processing_status')->default('pending')->index();
            // pending, processed, ignored, failed

            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();

            // Raw payload
            $table->json('headers_json')->nullable();
            $table->json('payload_json')->nullable();

            $table->timestamps();

            $table->index(['source_key', 'processing_status']);
            $table->index(['trello_board_id', 'processing_status']);
            $table->index(['trello_action_type', 'processing_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trello_webhook_events');
    }
};