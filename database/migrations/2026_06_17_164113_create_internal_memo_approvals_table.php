<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_memo_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('internal_memo_id')
                ->constrained('internal_memos')
                ->cascadeOnDelete();

            // 1 = Acknowledged by
            // 2 = Approved by
            $table->unsignedInteger('step_order');

            $table->string('role_label'); 
            // contoh:
            // Acknowledged by
            // Approved by

            $table->foreignId('approver_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('approver_name')->nullable();
            $table->string('approver_position')->nullable();

            // pending, approved, rejected, skipped
            $table->string('status')->default('pending')->index();

            $table->longText('notes')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();

            $table->unique(['internal_memo_id', 'step_order']);
            $table->index(['approver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_memo_approvals');
    }
};