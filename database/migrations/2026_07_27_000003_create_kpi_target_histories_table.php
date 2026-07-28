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
        Schema::create('kpi_target_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kpi_target_id')
                ->constrained('kpi_targets')
                ->cascadeOnDelete();

            /*
             * Contoh action:
             * created, updated, copied, status_changed, locked, unlocked,
             * deleted, atau restored.
             */
            $table->string('action', 50)->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(
                ['kpi_target_id', 'created_at'],
                'kpi_target_histories_timeline_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_target_histories');
    }
};
