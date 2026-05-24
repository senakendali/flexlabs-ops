<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workshop_id')
                ->constrained('workshops')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->enum('status', [
                'registered',
                'pending_payment',
                'confirmed',
                'attended',
                'cancelled',
            ])->default('registered');

            $table->timestamp('registered_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('attended_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['workshop_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_participants');
    }
};