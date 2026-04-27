<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minute_agendas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('meeting_minute_id')
                ->constrained('meeting_minutes')
                ->cascadeOnDelete();

            $table->string('topic');
            $table->text('description')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('meeting_minute_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minute_agendas');
    }
};