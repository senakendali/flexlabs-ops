<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_daily_reports', function (Blueprint $table) {
            $table->id();

            $table->date('report_date');

            $table->unsignedInteger('total_leads')->default(0);
            $table->unsignedInteger('interacted')->default(0);
            $table->unsignedInteger('ignored')->default(0);
            $table->unsignedInteger('closed_lost')->default(0);
            $table->unsignedInteger('not_related')->default(0);
            $table->unsignedInteger('warm_leads')->default(0);
            $table->unsignedInteger('hot_leads')->default(0);
            $table->unsignedInteger('consultation')->default(0);

            $table->text('summary')->nullable();
            $table->text('highlight')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_daily_reports');
    }
};