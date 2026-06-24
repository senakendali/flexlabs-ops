<?php

use App\Models\TrelloIntegration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trello_cards', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(TrelloIntegration::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('trello_list_record_id')
                ->nullable()
                ->constrained('trello_lists')
                ->nullOnDelete();

            $table->string('source_key')->index();

            $table->string('trello_board_id')->index();
            $table->string('trello_card_id')->index();
            $table->string('trello_list_id')->nullable()->index();

            $table->text('name');
            $table->longText('description')->nullable();

            $table->string('trello_list_name')->nullable();
            $table->string('normalized_status')->nullable()->index();

            $table->string('url')->nullable();
            $table->string('short_url')->nullable();

            $table->timestamp('due_at')->nullable()->index();
            $table->boolean('due_complete')->default(false)->index();

            $table->boolean('is_closed')->default(false)->index();

            $table->decimal('position', 16, 4)->nullable();

            $table->timestamp('last_activity_at')->nullable()->index();

            $table->json('labels_json')->nullable();
            $table->json('members_json')->nullable();
            $table->json('badges_json')->nullable();
            $table->json('raw_json')->nullable();

            $table->timestamps();

            $table->unique(['trello_integration_id', 'trello_card_id'], 'trello_cards_integration_card_unique');

            $table->index(['source_key', 'normalized_status'], 'trello_cards_source_status_index');
            $table->index(['source_key', 'due_at'], 'trello_cards_source_due_index');
            $table->index(['trello_board_id', 'is_closed'], 'trello_cards_board_closed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trello_cards');
    }
};