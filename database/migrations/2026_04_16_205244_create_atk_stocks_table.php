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
        Schema::create('atk_stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('atk_item_id')
                ->constrained('atk_items')
                ->cascadeOnDelete();

            $table->integer('current_stock')->default(0);
            $table->integer('reserved_stock')->default(0); // optional untuk request pending
            $table->string('location')->nullable(); // lemari A, gudang, ruang admin, dll
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique('atk_item_id');
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atk_stocks');
    }
};