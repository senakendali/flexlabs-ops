<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();

            $table->string('platform'); // meta_ads, tiktok_ads, google_ads
            $table->string('campaign_name');
            $table->string('ad_name')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('budget', 15, 2)->default(0);
            $table->decimal('spend', 15, 2)->default(0);

            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('leads')->default(0);
            $table->unsignedBigInteger('conversions')->default(0);

            $table->decimal('cost_per_click', 15, 2)->default(0);
            $table->decimal('cost_per_lead', 15, 2)->default(0);

            $table->string('status')->default('draft'); // draft, active, completed, paused
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['platform', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_ads');
    }
};