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
        Schema::table('internal_memos', function (Blueprint $table) {
            if (! Schema::hasColumn('internal_memos', 'attachment_url')) {
                $table->text('attachment_url')
                    ->nullable()
                    ->after('attachment_label');
            }
        });

        Schema::table('internal_memo_approvals', function (Blueprint $table) {
            if (! Schema::hasColumn('internal_memo_approvals', 'approver_email')) {
                $table->string('approver_email')
                    ->nullable()
                    ->after('approver_id');
            }

            if (! Schema::hasColumn('internal_memo_approvals', 'notification_sent_at')) {
                $table->timestamp('notification_sent_at')
                    ->nullable()
                    ->after('status');
            }

            if (! Schema::hasColumn('internal_memo_approvals', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')
                    ->nullable()
                    ->after('notification_sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internal_memo_approvals', function (Blueprint $table) {
            if (Schema::hasColumn('internal_memo_approvals', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }

            if (Schema::hasColumn('internal_memo_approvals', 'notification_sent_at')) {
                $table->dropColumn('notification_sent_at');
            }

            if (Schema::hasColumn('internal_memo_approvals', 'approver_email')) {
                $table->dropColumn('approver_email');
            }
        });

        Schema::table('internal_memos', function (Blueprint $table) {
            if (Schema::hasColumn('internal_memos', 'attachment_url')) {
                $table->dropColumn('attachment_url');
            }
        });
    }
};