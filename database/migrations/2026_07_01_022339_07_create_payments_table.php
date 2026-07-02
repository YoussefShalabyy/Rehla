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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('fee_cents')->default(0);
            $table->enum('gateway', ['paymob', 'revenuecat', 'stripe', 'fawry', 'paypal', 'null_adapter']);
            $table->string('gateway_transaction_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('booking_id');
            $table->index('gateway_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
