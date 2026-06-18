<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_memo_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('internal_memo_id')
                ->constrained('internal_memos')
                ->cascadeOnDelete();

            $table->text('details');

            $table->decimal('price', 15, 2)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('estimated_price', 15, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['internal_memo_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_memo_items');
    }
};