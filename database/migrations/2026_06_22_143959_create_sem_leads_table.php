<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sem_leads', function (Blueprint $table) {
            $table->id();

            // Lead identity
            $table->string('name');
            $table->string('whatsapp_number', 30);
            $table->string('program_interest')->nullable();

            // Form details
            $table->text('help_need')->nullable();
            $table->string('best_contact_time', 50)->nullable();

            // Tracking source
            $table->string('source', 100)->default('sem');
            $table->string('landing_page_url')->nullable();
            $table->string('referrer_url')->nullable();

            // UTM tracking
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();

            // Google Ads click tracking
            $table->string('gclid')->nullable();
            $table->string('gbraid')->nullable();
            $table->string('wbraid')->nullable();

            // Kommo sync tracking
            $table->string('kommo_sync_status', 30)->default('pending');
            $table->unsignedBigInteger('kommo_lead_id')->nullable();
            $table->unsignedBigInteger('kommo_contact_id')->nullable();
            $table->timestamp('kommo_synced_at')->nullable();
            $table->text('kommo_error')->nullable();

            // Technical metadata
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index('whatsapp_number');
            $table->index('program_interest');
            $table->index('best_contact_time');
            $table->index('kommo_sync_status', 'sem_leads_kommo_status_idx');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sem_leads');
    }
};