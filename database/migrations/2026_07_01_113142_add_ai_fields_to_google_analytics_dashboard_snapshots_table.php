<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_analytics_dashboard_snapshots', function (Blueprint $table) {
            $table->longText('ai_summary_text')->nullable()->after('summary_text');
            $table->json('ai_payload')->nullable()->after('ai_summary_text');
            $table->string('ai_model')->nullable()->after('ai_payload');
            $table->timestamp('ai_generated_at')->nullable()->after('ai_model');
        });
    }

    public function down(): void
    {
        Schema::table('google_analytics_dashboard_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'ai_summary_text',
                'ai_payload',
                'ai_model',
                'ai_generated_at',
            ]);
        });
    }
};