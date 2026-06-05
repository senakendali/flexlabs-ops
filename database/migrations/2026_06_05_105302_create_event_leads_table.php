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
        Schema::create('event_leads', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relation
            |--------------------------------------------------------------------------
            | Dibuat nullable + nullOnDelete supaya kalau event dihapus,
            | data leads tetap aman dan tidak ikut hilang.
            |--------------------------------------------------------------------------
            */
            $table->foreignId('lead_event_id')
                ->nullable()
                ->constrained('lead_events')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Lead Identity
            |--------------------------------------------------------------------------
            */
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Lead Profile
            |--------------------------------------------------------------------------
            */
            $table->string('institution')->nullable(); // sekolah, kampus, perusahaan, komunitas
            $table->string('position')->nullable();    // student, teacher, owner, employee, etc.
            $table->string('city')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Interest
            |--------------------------------------------------------------------------
            */
            $table->string('interest')->nullable(); // software engineering, AI, web dev, etc.
            $table->text('notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Lead Tracking
            |--------------------------------------------------------------------------
            */
            $table->string('source')->nullable(); // event, qr, instagram, whatsapp, ads, manual, etc.
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Follow Up Status
            |--------------------------------------------------------------------------
            | Pakai string, bukan enum, biar gampang ditambah value baru.
            | Contoh:
            | - new
            | - contacted
            | - interested
            | - registered
            | - not_interested
            |--------------------------------------------------------------------------
            */
            $table->string('status')->default('new');
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('registered_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Privacy / Technical Metadata
            |--------------------------------------------------------------------------
            */
            $table->boolean('is_consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();

            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('lead_event_id');
            $table->index('status');
            $table->index('source');
            $table->index('phone');
            $table->index('email');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_leads');
    }
};