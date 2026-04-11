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
        Schema::create('equipments', function (Blueprint $table) {
            $table->id();

            // Identitas utama
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique(); // CAM-001, MIC-001
            $table->string('serial_number')->nullable()->unique();

            // Detail
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('description')->nullable();

            // Status
            $table->enum('condition', ['good', 'minor_damage', 'damaged'])
                ->default('good');

            $table->enum('status', ['available', 'borrowed', 'maintenance'])
                ->default('available');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_categories');
    }
};
