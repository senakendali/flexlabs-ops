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
        Schema::create('atk_request_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('atk_request_id')
                ->constrained('atk_requests')
                ->cascadeOnDelete();

            $table->string('item_name');
            $table->integer('qty')->default(1);
            $table->string('unit')->nullable(); // pcs, box, pack, dll
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('atk_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atk_request_items');
    }
};