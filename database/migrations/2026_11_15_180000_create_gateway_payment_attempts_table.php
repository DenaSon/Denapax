<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateway_payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider', 32)->default('zarinpal');
            $table->string('authority', 80)->nullable()->unique();
            $table->string('reference_id', 80)->nullable()->unique();
            $table->unsignedBigInteger('amount_irr');
            $table->string('status', 32)->default('initializing');
            $table->string('last_error', 160)->nullable();
            $table->timestamp('verification_started_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_payment_attempts');
    }
};
