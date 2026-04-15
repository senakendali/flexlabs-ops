<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipment_id')
            ->constrained('equipments')
            ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_assignments');
    }
};
