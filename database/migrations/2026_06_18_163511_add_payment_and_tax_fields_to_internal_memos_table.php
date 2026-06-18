<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_memos', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('memo_date');

            $table->string('payment_source')->nullable()->after('attachment_label');
            // bank / cash

            $table->string('tax_treatment')->default('not_include')->after('tax_rate');
            // include / not_include

            $table->string('tax_entity_type')->default('non_pkp')->after('tax_treatment');
            // pkp / non_pkp
        });
    }

    public function down(): void
    {
        Schema::table('internal_memos', function (Blueprint $table) {
            $table->dropColumn([
                'due_date',
                'payment_source',
                'tax_treatment',
                'tax_entity_type',
            ]);
        });
    }
};