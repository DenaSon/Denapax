<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_group_alert_channels', function (Blueprint $table): void {
            $table->id();
            $table->bigInteger('chat_id');
            $table->timestamp('activated_at');
            $table->timestamps();
        });

        Schema::create('admin_group_alert_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('outbox_event_id')->unique();
            $table->foreign('outbox_event_id')->references('id')->on('outbox_events')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('attempts')->default(0);
            $table->bigInteger('telegram_message_id')->nullable();
            $table->string('last_error', 150)->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_group_alert_logs');
        Schema::dropIfExists('admin_group_alert_channels');
    }
};
