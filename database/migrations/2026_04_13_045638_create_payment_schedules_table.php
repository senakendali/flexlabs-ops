<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('title');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();

            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])
                ->default('pending');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('due_date');
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};