<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Workshop Participants Source Tracking
        |--------------------------------------------------------------------------
        */
        Schema::table('workshop_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('workshop_participants', 'input_source')) {
                $table->string('input_source')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('workshop_participants', 'utm_source')) {
                $table->string('utm_source')->nullable()->after('input_source');
            }

            if (! Schema::hasColumn('workshop_participants', 'utm_medium')) {
                $table->string('utm_medium')->nullable()->after('utm_source');
            }

            if (! Schema::hasColumn('workshop_participants', 'utm_campaign')) {
                $table->string('utm_campaign')->nullable()->after('utm_medium');
            }

            if (! Schema::hasColumn('workshop_participants', 'utm_content')) {
                $table->string('utm_content')->nullable()->after('utm_campaign');
            }

            if (! Schema::hasColumn('workshop_participants', 'utm_term')) {
                $table->string('utm_term')->nullable()->after('utm_content');
            }

            if (! Schema::hasColumn('workshop_participants', 'referrer_url')) {
                $table->text('referrer_url')->nullable()->after('utm_term');
            }

            if (! Schema::hasColumn('workshop_participants', 'landing_page_url')) {
                $table->text('landing_page_url')->nullable()->after('referrer_url');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Webinar / Trial Participants Source Tracking
        |--------------------------------------------------------------------------
        */
        Schema::table('trial_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('trial_participants', 'input_source')) {
                $table->string('input_source')->nullable()->after('goal');
            }

            if (! Schema::hasColumn('trial_participants', 'utm_source')) {
                $table->string('utm_source')->nullable()->after('input_source');
            }

            if (! Schema::hasColumn('trial_participants', 'utm_medium')) {
                $table->string('utm_medium')->nullable()->after('utm_source');
            }

            if (! Schema::hasColumn('trial_participants', 'utm_campaign')) {
                $table->string('utm_campaign')->nullable()->after('utm_medium');
            }

            if (! Schema::hasColumn('trial_participants', 'utm_content')) {
                $table->string('utm_content')->nullable()->after('utm_campaign');
            }

            if (! Schema::hasColumn('trial_participants', 'utm_term')) {
                $table->string('utm_term')->nullable()->after('utm_content');
            }

            if (! Schema::hasColumn('trial_participants', 'referrer_url')) {
                $table->text('referrer_url')->nullable()->after('utm_term');
            }

            if (! Schema::hasColumn('trial_participants', 'landing_page_url')) {
                $table->text('landing_page_url')->nullable()->after('referrer_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workshop_participants', function (Blueprint $table) {
            $columns = [
                'input_source',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'utm_term',
                'referrer_url',
                'landing_page_url',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('workshop_participants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('trial_participants', function (Blueprint $table) {
            $columns = [
                'input_source',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'utm_term',
                'referrer_url',
                'landing_page_url',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('trial_participants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};