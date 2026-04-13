<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->constrained()
                ->restrictOnDelete();

            // Snapshot harga saat order dibuat
            $table->decimal('original_price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2)->default(0);

            // Status transaksi
            $table->enum('status', ['pending', 'partial', 'paid', 'cancelled'])
                ->default('pending');

            // Catatan internal sales/admin
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index(['student_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};