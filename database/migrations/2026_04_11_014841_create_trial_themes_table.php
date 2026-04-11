<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_themes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['program_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_themes');
    }
};