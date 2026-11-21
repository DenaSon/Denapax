<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_view_daily', function (Blueprint $table): void {
            $table->id();
            $table->date('view_date');
            $table->string('page_type', 16);
            // Zero identifies the homepage; NULL would break MySQL unique keys.
            $table->unsignedBigInteger('product_id')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('unique_visitors')->default(0);
            $table->timestamps();

            $table->unique(['view_date', 'page_type', 'product_id'], 'page_view_daily_key');
            $table->index(['page_type', 'view_date'], 'page_view_daily_type_date');
        });

        Schema::create('page_view_visitors', function (Blueprint $table): void {
            $table->id();
            $table->date('visit_date');
            $table->string('page_type', 16);
            $table->unsignedBigInteger('product_id')->default(0);
            $table->char('visitor_hash', 64);
            $table->timestamp('last_viewed_at');
            $table->unique(
                ['visit_date', 'page_type', 'product_id', 'visitor_hash'],
                'page_view_visitor_key'
            );
            $table->index('visit_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_view_visitors');
        Schema::dropIfExists('page_view_daily');
    }
};
