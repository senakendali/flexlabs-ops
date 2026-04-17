<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_plan_id')->nullable()->constrained('marketing_plans')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('channel')->nullable(); // instagram, tiktok, meta_ads, event, partnership, etc
            $table->string('type')->nullable(); // awareness, lead_generation, promo, branding

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('budget', 15, 2)->default(0);

            $table->unsignedInteger('target_leads')->default(0);
            $table->unsignedInteger('target_conversions')->default(0);

            $table->unsignedInteger('actual_leads')->default(0);
            $table->unsignedInteger('actual_conversions')->default(0);

            $table->string('status')->default('planned'); // planned, ongoing, completed, cancelled
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};