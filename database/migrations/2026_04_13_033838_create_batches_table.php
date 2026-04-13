<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();

            // RELATION
            $table->foreignId('program_id')
                ->constrained()
                ->cascadeOnDelete();

            // BASIC INFO
            $table->string('name'); // contoh: Batch 1 - May 2026
            $table->string('slug')->unique();

            // SCHEDULE
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // BUSINESS
            $table->unsignedInteger('quota')->nullable();
            $table->decimal('price', 12, 2)->default(0);

            // STATUS
            $table->enum('status', ['draft', 'open', 'closed', 'ongoing', 'completed'])
                ->default('draft');

            // EXTRA
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};