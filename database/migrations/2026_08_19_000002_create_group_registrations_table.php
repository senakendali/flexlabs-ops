<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->enum('buyer_type', ['individual', 'company']);
            $table->foreignId('buyer_student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->restrictOnDelete();
            $table->foreignId('batch_id')
                ->constrained('batches')
                ->restrictOnDelete();

            // Snapshot data pembeli agar dokumen transaksi lama tidak berubah
            // ketika master student/company diperbarui.
            $table->string('buyer_name');
            $table->string('buyer_email')->nullable();
            $table->string('buyer_phone', 30)->nullable();

            $table->unsignedInteger('quantity');
            $table->decimal('price_per_seat', 12, 2);
            $table->decimal('original_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('service_amount', 12, 2);

            // WHT/PPh 23 menggunakan metode gross-up untuk company.
            $table->decimal('wht_rate', 5, 2)->default(0);
            $table->decimal('wht_amount', 12, 2)->default(0);
            $table->decimal('invoice_total', 12, 2);
            $table->decimal('net_payable', 12, 2);
            $table->enum('wht_status', ['not_applicable', 'pending', 'received'])
                ->default('not_applicable');
            $table->string('wht_certificate_number')->nullable();
            $table->date('wht_certificate_date')->nullable();
            $table->text('wht_certificate_file')->nullable();

            $table->enum('status', ['draft', 'pending', 'confirmed', 'cancelled', 'completed'])
                ->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['buyer_type', 'status']);
            $table->index(['batch_id', 'status']);
            $table->index('wht_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_registrations');
    }
};