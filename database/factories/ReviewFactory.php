<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Booking;
use App\Models\User;
use App\Models\Listing;
use App\Enums\UserRole;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'uuid'        => (string) Str::uuid(),
            'booking_id'  => Booking::factory()->completed(),
            'reviewer_id' => User::factory()->create(['role' => UserRole::Customer])->id,
            'listing_id'  => Listing::factory()->published(),
            'rating'      => $this->faker->numberBetween(1, 5),
            'comment'     => $this->faker->paragraph(),
            'status'      => ReviewStatus::Approved,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => ReviewStatus::Approved]);
    }

    public function hidden(): static
    {
        return $this->state(['status' => ReviewStatus::Hidden]);
    }
}
