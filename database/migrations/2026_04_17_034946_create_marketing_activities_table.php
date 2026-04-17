<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();

            $table->date('activity_date');
            $table->string('channel')->nullable(); // instagram, tiktok, event, ads, partnership
            $table->string('activity_type')->nullable(); // content_post, design, copywriting, meeting, ads_setup, followup_event

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('status')->default('planned'); // planned, ongoing, done, cancelled
            $table->string('output_link')->nullable();

            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['activity_date', 'status']);
            $table->index(['channel', 'activity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_activities');
    }
};