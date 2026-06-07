<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workshop_id')
                ->constrained('workshops')
                ->cascadeOnDelete();

            $table->string('title')->nullable();

            $table->date('schedule_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->enum('location_type', ['online', 'offline', 'hybrid'])
                ->default('online');

            $table->string('location')->nullable();
            $table->text('meeting_url')->nullable();

            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('registered_count')->default(0);

            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('old_price', 12, 2)->nullable();

            $table->enum('status', [
                'draft',
                'open',
                'closed',
                'completed',
                'cancelled',
            ])->default('open');

            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['workshop_id', 'schedule_date']);
            $table->index(['status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_schedules');
    }
};