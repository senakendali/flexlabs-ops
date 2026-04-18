<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_lead_sources', function (Blueprint $table) {
            // Drop foreign key dulu
            $table->dropForeign(['assigned_to']);
        });

        Schema::table('marketing_lead_sources', function (Blueprint $table) {
            // Drop index lama yang sudah tidak relevan
            $table->dropIndex(['lead_date', 'status']);
            $table->dropIndex(['source']);
            $table->dropIndex(['email']);
            $table->dropIndex(['phone']);
        });

        // Rename kolom pakai DB statement biar aman di beberapa setup MySQL
        DB::statement('ALTER TABLE marketing_lead_sources CHANGE lead_date date DATE NULL');
        DB::statement('ALTER TABLE marketing_lead_sources CHANGE source source_type VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE marketing_lead_sources CHANGE source_detail source_name VARCHAR(255) NULL');

        Schema::table('marketing_lead_sources', function (Blueprint $table) {
            // Drop kolom lama CRM-style
            $table->dropColumn([
                'lead_name',
                'email',
                'phone',
                'status',
                'assigned_to',
            ]);

            // Tambah kolom summary
            $table->unsignedInteger('leads')->default(0)->after('date');
            $table->unsignedInteger('qualified_leads')->default(0)->after('leads');
            $table->unsignedInteger('conversions')->default(0)->after('qualified_leads');
            $table->decimal('revenue', 15, 2)->default(0)->after('conversions');

            // Index baru
            $table->index(['source_type', 'date'], 'mls_source_type_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_lead_sources', function (Blueprint $table) {
            $table->dropIndex('mls_source_type_date_index');

            $table->dropColumn([
                'leads',
                'qualified_leads',
                'conversions',
                'revenue',
            ]);
        });

        DB::statement('ALTER TABLE marketing_lead_sources CHANGE date lead_date DATE NULL');
        DB::statement('ALTER TABLE marketing_lead_sources CHANGE source_type source VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE marketing_lead_sources CHANGE source_name source_detail VARCHAR(255) NULL');

        Schema::table('marketing_lead_sources', function (Blueprint $table) {
            $table->string('lead_name')->after('lead_date');
            $table->string('email')->nullable()->after('lead_name');
            $table->string('phone')->nullable()->after('email');
            $table->string('status')->default('new')->after('source_detail');
            $table->foreignId('assigned_to')->nullable()->after('notes');

            $table->index(['lead_date', 'status']);
            $table->index(['source']);
            $table->index(['email']);
            $table->index(['phone']);
        });

        Schema::table('marketing_lead_sources', function (Blueprint $table) {
            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};