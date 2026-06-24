<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE trello_lists MODIFY position DECIMAL(24,4) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE trello_lists MODIFY position INT UNSIGNED NOT NULL DEFAULT 0');
    }
};