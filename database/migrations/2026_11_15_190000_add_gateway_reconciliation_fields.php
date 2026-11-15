<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gateway_payment_attempts', function (Blueprint $table): void {
            $table->uuid('verification_token')->nullable();
            $table->string('inquiry_status', 32)->nullable();
            $table->timestamp('inquiry_checked_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gateway_payment_attempts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn([
                'verification_token', 'inquiry_status', 'inquiry_checked_at',
                'resolved_at', 'resolution_note',
            ]);
        });
    }
};
