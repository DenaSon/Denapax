<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id')->unique();
            $table->bigInteger('telegram_chat_id')->nullable();
            $table->string('display_name', 220)->nullable();
            $table->string('full_name', 220)->nullable();
            $table->string('mobile', 24)->nullable();
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 80)->nullable();
            $table->string('recipient_name', 220);
            $table->string('recipient_mobile', 24);
            $table->string('province', 100);
            $table->string('city', 100);
            $table->text('address_line');
            $table->string('postal_code', 16);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->index(['customer_id', 'is_default']);
        });

        Schema::create('telegram_sessions', function (Blueprint $table): void {
            $table->foreignId('customer_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('state', 100)->default('idle');
            $table->json('context')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('telegram_updates', function (Blueprint $table): void {
            $table->unsignedBigInteger('update_id')->primary();
            $table->string('status', 16);
            $table->timestamp('received_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_summary')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_updates');
        Schema::dropIfExists('telegram_sessions');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
