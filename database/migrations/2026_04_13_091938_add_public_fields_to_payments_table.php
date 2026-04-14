<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Token untuk public payment link
            $table->string('public_token')
                ->unique()
                ->nullable()
                ->after('invoice_number');

            // URL dari payment gateway (hosted payment page)
            $table->text('payment_url')
                ->nullable()
                ->after('public_token');

            // Expired time untuk payment link
            $table->timestamp('expired_at')
                ->nullable()
                ->after('status');

            // Index tambahan
            $table->index('public_token');
            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['public_token']);
            $table->dropIndex(['expired_at']);

            $table->dropColumn([
                'public_token',
                'payment_url',
                'expired_at'
            ]);
        });
    }
};