<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The previous storefront used Telegram IDs as required identity. This
        // cutover intentionally refuses to discard real customer history.
        if (DB::table('customers')->exists()) {
            throw new RuntimeException('Web customer cutover requires an empty customers table.');
        }

        if (Schema::hasColumn('customers', 'telegram_user_id')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->dropUnique('customers_telegram_user_id_unique');
                $table->dropColumn(['telegram_user_id', 'telegram_chat_id', 'display_name']);
            });
        }

        if (! Schema::hasColumn('customers', 'mobile_verified_at')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->timestamp('mobile_verified_at')->nullable();
                $table->unique('mobile');
            });
        }

        Schema::dropIfExists('telegram_sessions');
        Schema::dropIfExists('telegram_updates');
        Schema::dropIfExists('admin_group_alert_logs');
        Schema::dropIfExists('admin_group_alert_channels');

        if (Schema::hasColumn('payment_receipts', 'telegram_file_id')) {
            Schema::table('payment_receipts', fn (Blueprint $table) => $table->dropColumn('telegram_file_id'));
        }
        if (Schema::hasColumn('notification_logs', 'telegram_message_id')) {
            Schema::table('notification_logs', fn (Blueprint $table) => $table->dropColumn('telegram_message_id'));
        }

        Schema::table('notification_logs', function (Blueprint $table): void {
            $table->string('channel', 20)->default('web')->change();
        });

        Schema::create('customer_login_codes', function (Blueprint $table): void {
            $table->string('mobile', 16)->primary();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at');
            $table->timestamps();
        });

        Schema::create('customer_notifications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('outbox_event_id')->unique();
            $table->foreign('outbox_event_id')->references('id')->on('outbox_events')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        // Restoring a retired Telegram identity from a mobile number is not
        // possible. Roll back application code from a paired database backup.
        throw new RuntimeException('Web-only cutover is irreversible: restore the pre-cutover backup.');
    }
};
