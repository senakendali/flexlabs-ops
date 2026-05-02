<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('instructor_session_tracking_items', 'sort_order')) {
            Schema::table('instructor_session_tracking_items', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')
                    ->default(0)
                    ->after('delivery_status');

                $table->index('sort_order', 'ist_items_sort_order_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('instructor_session_tracking_items', 'sort_order')) {
            Schema::table('instructor_session_tracking_items', function (Blueprint $table) {
                $table->dropIndex('ist_items_sort_order_idx');
                $table->dropColumn('sort_order');
            });
        }
    }
};