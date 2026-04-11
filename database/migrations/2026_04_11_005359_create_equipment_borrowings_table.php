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
        Schema::create('equipment_borrowings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipment_id')
                ->constrained('equipments')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('borrowed_at');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->enum('status', ['borrowed', 'returned', 'overdue'])
                ->default('borrowed');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_borrowings');
    }
};
