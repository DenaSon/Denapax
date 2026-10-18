<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['cart_id', 'product_variant_id']);
        });

        Schema::create('shipping_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('base_weight_grams');
            $table->unsignedBigInteger('base_price_irr');
            $table->unsignedInteger('extra_step_grams');
            $table->unsignedBigInteger('extra_step_price_irr');
            $table->unsignedInteger('packaging_weight_grams')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->uuid('checkout_key')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('order_status', 32);
            $table->string('payment_status', 32);
            $table->string('recipient_name', 220);
            $table->string('recipient_mobile', 24);
            $table->string('province', 100);
            $table->string('city', 100);
            $table->text('address_line');
            $table->string('postal_code', 16);
            $table->unsignedBigInteger('goods_subtotal_irr');
            $table->unsignedBigInteger('shipping_fee_irr');
            $table->unsignedBigInteger('total_irr');
            $table->unsignedBigInteger('shipping_weight_grams');
            $table->json('shipping_rule_snapshot');
            $table->json('payment_instructions_snapshot');
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
            $table->index(['order_status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('sku_snapshot', 100);
            $table->string('product_title_snapshot', 220);
            $table->string('variant_title_snapshot', 180);
            $table->decimal('sale_quantity_snapshot', 12, 3);
            $table->string('sale_unit_snapshot', 16);
            $table->unsignedInteger('unit_weight_grams_snapshot');
            $table->unsignedBigInteger('unit_price_irr');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total_irr');
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('actor_type', 16);
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id', 'created_at']);
        });

        Schema::create('outbox_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('aggregate_type', 80);
            $table->unsignedBigInteger('aggregate_id');
            $table->string('event_type', 120);
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamp('processed_at')->nullable();
            $table->unique(['aggregate_type', 'aggregate_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('shipping_settings');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
