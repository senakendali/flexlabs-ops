<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_lead_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('marketing_event_id')->nullable()->constrained('marketing_events')->nullOnDelete();

            $table->date('lead_date')->nullable();

            $table->string('lead_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('source'); // instagram, tiktok, ads, event, referral, website
            $table->string('source_detail')->nullable(); // reel, webinar april, meta ads campaign A, etc

            $table->string('status')->default('new'); // new, contacted, qualified, converted, closed_lost
            $table->text('notes')->nullable();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['lead_date', 'status']);
            $table->index(['source']);
            $table->index(['email']);
            $table->index(['phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_lead_sources');
    }
};