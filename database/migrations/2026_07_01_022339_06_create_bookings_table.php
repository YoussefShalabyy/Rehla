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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('booking_reference', 20)->unique();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedInteger('guests_count');
            $table->string('currency', 3)->default('EGP');
            $table->unsignedBigInteger('total_amount_cents');
            $table->unsignedBigInteger('platform_fee_cents');
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['listing_id', 'check_in_date', 'check_out_date']);
            $table->index(['customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
