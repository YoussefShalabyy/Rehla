<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Media;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\Wallet;
use App\Enums\UserRole;
use App\Enums\ListingType;
use App\Enums\PropertyType;
use App\Enums\ListingStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentGateway;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HugeIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Amenities
        $amenities = [
            ['name' => 'WiFi', 'icon' => 'wifi'],
            ['name' => 'Pool', 'icon' => 'pool'],
            ['name' => 'Air Conditioning', 'icon' => 'ac_unit'],
            ['name' => 'Kitchen', 'icon' => 'kitchen'],
            ['name' => 'Free Parking', 'icon' => 'local_parking'],
            ['name' => 'TV', 'icon' => 'tv'],
        ];

        foreach ($amenities as $am) {
            Amenity::firstOrCreate(['name' => $am['name']], $am);
        }
        $amenityIds = Amenity::pluck('id')->toArray();

        // 2. Create 10 Providers
        $providers = [];
        for ($i = 1; $i <= 10; $i++) {
            $user = User::create([
                'uuid' => Str::uuid(),
                'name' => "Provider Num{$i}",
                'email' => "provider{$i}@example.com",
                'password' => bcrypt('password'),
                'phone' => "+2010000000{$i}",
                'role' => UserRole::Provider,
            ]);
            Wallet::create(['uuid' => Str::uuid(), 'user_id' => $user->id, 'balance_cents' => random_int(10000, 500000)]);
            $providers[] = $user;
        }

        // 3. Create 100 Listings (Villas and Cars)
        $listings = [];
        foreach ($providers as $provider) {
            for ($j = 1; $j <= 10; $j++) {
                $isCar = $j % 3 === 0;
                
                $listing = Listing::create([
                    'uuid' => Str::uuid(),
                    'owner_id' => $provider->id,
                    'title' => $isCar ? "Luxury Car Model {$j}" : "Beautiful Villa {$j}",
                    'description' => "This is a fantastic place or car for your next trip. Enjoy premium comfort.",
                    'type' => $isCar ? ListingType::Car : ListingType::Property,
                    'property_type' => $isCar ? null : PropertyType::Villa,
                    'status' => ListingStatus::Published,
                    'base_price_cents' => random_int(5000, 50000), // $50 to $500
                    'cleaning_fee_cents' => $isCar ? 0 : 5000,
                    'extra_guest_fee_cents' => 1000,
                    'max_guests' => $isCar ? 4 : random_int(2, 10),
                    'country' => 'Egypt',
                    'city' => ['Cairo', 'Alexandria', 'Gouna', 'Sharm El Sheikh'][array_rand(['Cairo', 'Alexandria', 'Gouna', 'Sharm El Sheikh'])],
                    'address' => "123 Random St, Area {$j}",
                    'latitude' => 30.0444 + (lcg_value() / 10),
                    'longitude' => 31.2357 + (lcg_value() / 10),
                    'is_instant_bookable' => true,
                ]);

                // Attach amenities
                $listing->amenities()->attach(array_rand(array_flip($amenityIds), 3));

                // Attach 3 dummy images
                for ($img = 1; $img <= 3; $img++) {
                    Media::create([
                        'uuid' => Str::uuid(),
                        'entity_type' => Listing::class,
                        'entity_id' => $listing->id,
                        'type' => 'image',
                        'provider' => 'cloudinary',
                        'url' => "https://source.unsplash.com/800x600/?" . ($isCar ? "car" : "villa") . "&sig={$listing->id}{$img}",
                        'public_id' => "dummy_public_id_{$img}",
                        'order' => $img,
                        'is_primary' => $img === 1,
                    ]);
                }

                $listings[] = $listing;
            }
        }

        // 4. Create 50 Customers
        $customers = [];
        for ($k = 1; $k <= 50; $k++) {
            $user = User::create([
                'uuid' => Str::uuid(),
                'name' => "Customer Num{$k}",
                'email' => "customer{$k}@example.com",
                'password' => bcrypt('password'),
                'phone' => "+2011000000{$k}",
                'role' => UserRole::Customer,
            ]);
            Wallet::create(['uuid' => Str::uuid(), 'user_id' => $user->id, 'balance_cents' => random_int(5000, 100000)]);
            $customers[] = $user;
        }

        // 5. Create Bookings, Payments, and Reviews
        foreach ($customers as $customer) {
            // Each customer makes 2 bookings
            for ($b = 1; $b <= 2; $b++) {
                $listing = $listings[array_rand($listings)];
                $isCompleted = $b === 1; // 1 completed, 1 upcoming
                
                $checkIn = $isCompleted ? now()->subDays(10) : now()->addDays(random_int(2, 20));
                $checkOut = $checkIn->copy()->addDays(random_int(1, 5));

                $booking = Booking::create([
                    'uuid' => Str::uuid(),
                    'booking_reference' => strtoupper(Str::random(8)),
                    'listing_id' => $listing->id,
                    'customer_id' => $customer->id,
                    'check_in_date' => $checkIn,
                    'check_out_date' => $checkOut,
                    'guests_count' => 2,
                    'total_amount_cents' => $listing->base_price_cents * 2,
                    'platform_fee_cents' => 1000,
                    'status' => $isCompleted ? BookingStatus::Completed : BookingStatus::Confirmed,
                ]);

                Payment::create([
                    'uuid' => Str::uuid(),
                    'booking_id' => $booking->id,
                    'amount_cents' => $booking->total_amount_cents,
                    'gateway' => PaymentGateway::Paymob,
                    'status' => PaymentStatus::Paid,
                    'gateway_transaction_id' => Str::random(10),
                ]);

                if ($isCompleted) {
                    Review::create([
                        'uuid' => Str::uuid(),
                        'booking_id' => $booking->id,
                        'listing_id' => $listing->id,
                        'reviewer_id' => $customer->id,
                        'rating' => random_int(4, 5),
                        'comment' => "Amazing experience! Highly recommended.",
                        'status' => 'approved',
                    ]);
                }
            }
        }
    }
}
