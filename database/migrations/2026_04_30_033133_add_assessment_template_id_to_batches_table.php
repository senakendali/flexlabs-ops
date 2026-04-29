<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (! Schema::hasColumn('batches', 'assessment_template_id')) {
                $table->foreignId('assessment_template_id')
                    ->nullable()
                    ->after('program_id')
                    ->constrained('assessment_templates')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'assessment_template_id')) {
                $table->dropConstrainedForeignId('assessment_template_id');
            }
        });
    }
};