<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Relasi utama
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            // Optional: kalau payment terkait schedule tertentu
            $table->foreignId('payment_schedule_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Invoice internal
            $table->string('invoice_number')->unique();

            // Nominal pembayaran
            $table->decimal('amount', 12, 2)->default(0);

            // Tanggal payment dibuat / dibayar
            $table->date('payment_date')->nullable();

            // Metode pembayaran
            $table->string('payment_method')->nullable();
            // contoh: transfer, cash, va, qris, xendit, dll

            // Nomor referensi internal / eksternal
            $table->string('reference_number')->nullable();

            // ID transaksi dari gateway
            $table->string('gateway_transaction_id')->nullable()->index();

            // Provider payment gateway
            $table->string('gateway_provider')->nullable();
            // contoh: xendit, midtrans, dll

            // Raw payload / response dari gateway
            $table->json('gateway_payload')->nullable();

            // Status pembayaran
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled'])
                ->default('pending');

            // Catatan tambahan
            $table->text('notes')->nullable();

            // Waktu payment benar-benar sukses
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // Index biar cepat
            $table->index('invoice_number');
            $table->index('status');
            $table->index('payment_date');
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};