<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_memos', function (Blueprint $table) {
            $table->id();

            // Memo identity
            $table->string('memo_number')->unique();
            $table->date('memo_date');

            // Header info
            $table->string('subject');
            $table->string('attachment_label')->nullable();

            $table->string('to_name');
            $table->string('to_position')->nullable();

            $table->string('from_name');
            $table->string('from_position')->nullable();

            // Content
            $table->longText('purpose')->nullable();
            $table->longText('notes')->nullable();

            // Amount summary
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0); // contoh: 11.00
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total_amount', 15, 2)->default(0);

            // Status flow:
            // draft, submitted, waiting_acknowledgement, waiting_approval, approved, rejected, cancelled
            $table->string('status')->default('draft')->index();

            // Creator / requester
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['memo_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_memos');
    }
};