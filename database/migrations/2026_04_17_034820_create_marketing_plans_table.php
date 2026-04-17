<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();

            $table->string('period_type')->default('monthly'); // weekly, monthly, quarterly
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->string('objective')->nullable(); // awareness, leads, conversion
            $table->text('strategy')->nullable();
            $table->text('notes')->nullable();

            $table->decimal('budget', 15, 2)->default(0);

            $table->string('status')->default('draft'); // draft, active, completed, cancelled
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['period_type', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_plans');
    }
};