<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\PropertyType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Listing extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'type'                  => ListingType::class,
            'property_type'         => PropertyType::class,
            'status'                => ListingStatus::class,
            'is_instant_bookable'   => 'boolean',
            'base_price_cents'      => 'integer',
            'cleaning_fee_cents'    => 'integer',
            'extra_guest_fee_cents' => 'integer',
            'average_rating'        => 'float',
            'total_reviews'         => 'integer',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function amenities(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'listing_amenity');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'entity');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::Published);
    }

    public function scopeOfType(Builder $query, ListingType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function getReviewsCountAttribute(): int
    {
        // Retrieve actual count of approved reviews from the database
        return $this->reviews()->where('status', \App\Enums\ReviewStatus::Approved)->count();
    }
}
