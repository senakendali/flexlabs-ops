<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_analytics_dashboard_snapshots', function (Blueprint $table) {
            $table->id();

            $table->string('property_id')->nullable()->index();
            $table->string('date_preset')->default('last_7d')->index();

            $table->string('date_start')->nullable();
            $table->string('date_stop')->nullable();

            $table->boolean('is_available')->default(false)->index();

            $table->unsignedBigInteger('total_users')->default(0);
            $table->unsignedBigInteger('new_users')->default(0);
            $table->unsignedBigInteger('sessions')->default(0);
            $table->unsignedBigInteger('engaged_sessions')->default(0);
            $table->decimal('engagement_rate', 8, 2)->default(0);
            $table->decimal('bounce_rate', 8, 2)->default(0);
            $table->unsignedBigInteger('key_events')->default(0);
            $table->decimal('key_event_rate', 8, 2)->default(0);
            $table->string('average_engagement_time_label')->nullable();

            $table->longText('summary_text')->nullable();
            $table->json('payload')->nullable();

            // Last sync error. Kalau sync gagal, payload lama tetap bisa dipakai.
            $table->longText('error_message')->nullable();

            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['property_id', 'date_preset'],
                'ga_dashboard_snapshots_property_preset_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_analytics_dashboard_snapshots');
    }
};