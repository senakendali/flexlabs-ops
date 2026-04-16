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
        Schema::table('atk_request_items', function (Blueprint $table) {
            $table->foreignId('atk_item_id')
                ->nullable()
                ->after('atk_request_id')
                ->constrained('atk_items')
                ->restrictOnDelete();
        });

        Schema::table('atk_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('atk_request_items', 'item_name')) {
                $table->dropColumn('item_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atk_request_items', function (Blueprint $table) {
            $table->string('item_name')->nullable()->after('atk_item_id');
            $table->dropConstrainedForeignId('atk_item_id');
        });
    }
};