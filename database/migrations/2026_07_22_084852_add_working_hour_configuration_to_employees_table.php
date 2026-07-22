<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('default_working_hour_template_id')
                ->nullable()
                ->after('duty_type')
                ->constrained('working_hour_templates')
                ->nullOnDelete();

            /*
            | Optional override jika employee punya hari kerja yang berbeda dari
            | default template.
            */
            $table->json('working_days_override')
                ->nullable()
                ->after('default_working_hour_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'default_working_hour_template_id'
            );

            $table->dropColumn('working_days_override');
        });
    }
};