<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_reports', function (Blueprint $table) {
            // rename columns
            $table->renameColumn('budget', 'total_budget');
            $table->renameColumn('actual_spend', 'total_actual_spend');
        });

        Schema::table('marketing_reports', function (Blueprint $table) {
            // drop old column
            $table->dropColumn('qualified_leads');

            // snapshot tambahan
            $table->unsignedInteger('total_registrants')->default(0)->after('total_leads');
            $table->unsignedInteger('total_attendees')->default(0)->after('total_registrants');

            // optional numbering
            $table->string('report_no', 100)->nullable()->after('slug');

            // checklist per section
            $table->boolean('is_overview_completed')->default(false)->after('notes');
            $table->boolean('is_campaign_completed')->default(false)->after('is_overview_completed');
            $table->boolean('is_ads_completed')->default(false)->after('is_campaign_completed');
            $table->boolean('is_events_completed')->default(false)->after('is_ads_completed');
            $table->boolean('is_snapshot_completed')->default(false)->after('is_events_completed');
            $table->boolean('is_insight_completed')->default(false)->after('is_snapshot_completed');

            // rapihin default angka summary
            $table->unsignedInteger('total_leads')->default(0)->change();
            $table->unsignedInteger('total_conversions')->default(0)->change();
            $table->decimal('total_revenue', 15, 2)->default(0)->change();
            $table->decimal('total_budget', 15, 2)->default(0)->change();
            $table->decimal('total_actual_spend', 15, 2)->default(0)->change();

            // default status & active
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->change();
            $table->boolean('is_active')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_reports', function (Blueprint $table) {
            // rollback added columns
            $table->dropColumn([
                'report_no',
                'total_registrants',
                'total_attendees',
                'is_overview_completed',
                'is_campaign_completed',
                'is_ads_completed',
                'is_events_completed',
                'is_snapshot_completed',
                'is_insight_completed',
            ]);
        });

        Schema::table('marketing_reports', function (Blueprint $table) {
            // rename back
            $table->renameColumn('total_budget', 'budget');
            $table->renameColumn('total_actual_spend', 'actual_spend');
        });

        Schema::table('marketing_reports', function (Blueprint $table) {
            // restore old column
            $table->unsignedInteger('qualified_leads')->default(0)->after('total_leads');

            // revert defaults if needed
            $table->decimal('budget', 15, 2)->nullable()->change();
            $table->decimal('actual_spend', 15, 2)->nullable()->change();
            $table->decimal('total_revenue', 15, 2)->nullable()->change();
            $table->unsignedInteger('total_leads')->nullable(false)->change();
            $table->unsignedInteger('total_conversions')->nullable(false)->change();
            $table->enum('status', ['draft', 'published', 'archived'])->change();
            $table->boolean('is_active')->change();
        });
    }
};