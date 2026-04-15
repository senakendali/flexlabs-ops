<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_daily_reports', function (Blueprint $table) {
            $table->unsignedInteger('closed_deal')->default(0)->after('consultation');
            $table->decimal('revenue', 15, 2)->default(0)->after('closed_deal');
        });
    }

    public function down(): void
    {
        Schema::table('sales_daily_reports', function (Blueprint $table) {
            $table->dropColumn(['closed_deal', 'revenue']);
        });
    }
};
