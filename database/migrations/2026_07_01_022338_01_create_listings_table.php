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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['property', 'car']);
            $table->enum('property_type', ['hotel', 'apartment', 'villa'])->nullable();
            $table->string('title');
            $table->text('description');
            $table->string('address');
            $table->string('country');
            $table->string('city');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedBigInteger('base_price_cents');
            $table->unsignedBigInteger('cleaning_fee_cents')->default(0);
            $table->unsignedBigInteger('extra_guest_fee_cents')->default(0);
            $table->enum('status', ['pending', 'published', 'rejected', 'archived'])->default('pending');
            $table->boolean('is_instant_bookable')->default(true);
            $table->unsignedInteger('max_guests');
            $table->unsignedInteger('bedrooms')->nullable();
            $table->unsignedInteger('bathrooms')->nullable(); // Changed from decimal to unsignedInteger based on common practice or we can use decimal(3,1)
            $table->string('transmission')->nullable();
            $table->string('fuel_type')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('owner_id');
            $table->index(['city', 'type', 'status']);
            $table->index('status');
        });
        
        // Fix bathrooms type if decimal is required
        Schema::table('listings', function (Blueprint $table) {
            $table->decimal('bathrooms', 3, 1)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
