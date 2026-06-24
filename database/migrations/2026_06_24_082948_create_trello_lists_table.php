<?php

use App\Models\TrelloIntegration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trello_lists', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(TrelloIntegration::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->string('source_key')->index();

            $table->string('trello_board_id')->index();
            $table->string('trello_list_id')->index();

            $table->string('name');
            $table->unsignedInteger('position')->default(0);

            $table->boolean('is_closed')->default(false)->index();

            // Nanti diisi dari mapping
            $table->string('normalized_status')->nullable()->index();

            $table->json('raw_json')->nullable();

            $table->timestamps();

            $table->unique(['trello_integration_id', 'trello_list_id'], 'trello_lists_integration_list_unique');
            $table->index(['source_key', 'normalized_status'], 'trello_lists_source_status_index');
            $table->index(['trello_board_id', 'is_closed'], 'trello_lists_board_closed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trello_lists');
    }
};