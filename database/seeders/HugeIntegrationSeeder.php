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

        // Define templates for all types and categories to ensure the UI horizontal lists are fully populated
        $templates = [
            ['type' => ListingType::Property, 'prop_type' => PropertyType::Villa, 'car_cat' => null, 'name' => 'Beautiful Villa', 'images' => ['https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&q=80', 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&q=80', 'https://images.unsplash.com/photo-1613977257363-707ba9348227?w=800&q=80']],
            ['type' => ListingType::Property, 'prop_type' => PropertyType::Apartment, 'car_cat' => null, 'name' => 'Luxury Apartment', 'images' => ['https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&q=80', 'https://images.unsplash.com/photo-1502672260266-1c15a8223041?w=800&q=80', 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&q=80']],
            ['type' => ListingType::Property, 'prop_type' => PropertyType::Hotel, 'car_cat' => null, 'name' => 'Grand Hotel Suite', 'images' => ['https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80', 'https://images.unsplash.com/photo-1551882547-ff40c0d5b5df?w=800&q=80', 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=800&q=80']],
            ['type' => ListingType::Car, 'prop_type' => null, 'car_cat' => 'luxury', 'name' => 'Mercedes S Class', 'images' => ['https://images.unsplash.com/photo-1563720223185-11003d516935?w=800&q=80', 'https://images.unsplash.com/photo-1609521263047-f8f205293f24?w=800&q=80', 'https://images.unsplash.com/photo-1555626906-fcf10d6851b4?w=800&q=80']],
            ['type' => ListingType::Car, 'prop_type' => null, 'car_cat' => 'sports', 'name' => 'Ferrari Spider', 'images' => ['https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=800&q=80', 'https://images.unsplash.com/photo-1614200187524-dc4b892acf16?w=800&q=80', 'https://images.unsplash.com/photo-1592198084033-aade902d1aae?w=800&q=80']],
            ['type' => ListingType::Car, 'prop_type' => null, 'car_cat' => 'economy', 'name' => 'Toyota Corolla', 'images' => ['https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?w=800&q=80', 'https://images.unsplash.com/photo-1590362891991-f700075c1973?w=800&q=80', 'https://images.unsplash.com/photo-1605810731427-da28892d1921?w=800&q=80']],
            ['type' => ListingType::Car, 'prop_type' => null, 'car_cat' => 'family', 'name' => 'Honda CRV SUV', 'images' => ['https://images.unsplash.com/photo-1550130635-c3cbf4c1eb16?w=800&q=80', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?w=800&q=80', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80']],
        ];

        // 3. Create Listings
        $listings = [];
        foreach ($providers as $provider) {
            // Give each provider one of each template
            foreach ($templates as $j => $tpl) {
                $isCar = $tpl['type'] === ListingType::Car;
                
                $listing = Listing::create([
                    'uuid' => Str::uuid(),
                    'owner_id' => $provider->id,
                    'title' => $tpl['name'] . " " . random_int(100, 999),
                    'description' => "This is a fantastic place or car for your next trip. Enjoy premium comfort and a seamless experience. Highly recommended for couples and families looking to explore.",
                    'type' => $tpl['type'],
                    'property_type' => $tpl['prop_type'],
                    'category' => $tpl['car_cat'],
                    'status' => ListingStatus::Published,
                    'base_price_cents' => random_int(5000, 50000), // $50 to $500
                    'cleaning_fee_cents' => $isCar ? 0 : 5000,
                    'extra_guest_fee_cents' => 1000,
                    'max_guests' => $isCar ? 4 : random_int(2, 10),
                    'country' => 'Egypt',
                    'city' => ['Cairo', 'Alexandria', 'Gouna', 'Sharm El Sheikh'][array_rand(['Cairo', 'Alexandria', 'Gouna', 'Sharm El Sheikh'])],
                    'address' => "123 Random St, Area " . random_int(1, 100),
                    'latitude' => 30.0444 + (lcg_value() / 10),
                    'longitude' => 31.2357 + (lcg_value() / 10),
                    'is_instant_bookable' => true,
                ]);

                // Attach amenities
                $listing->amenities()->attach(array_rand(array_flip($amenityIds), 3));

                // Attach 3 static unsplash images
                foreach ($tpl['images'] as $index => $imgUrl) {
                    Media::create([
                        'uuid' => Str::uuid(),
                        'entity_type' => Listing::class,
                        'entity_id' => $listing->id,
                        'type' => 'image',
                        'provider' => 'cloudinary',
                        'url' => $imgUrl,
                        'public_id' => "dummy_public_id_{$index}",
                        'order' => $index + 1,
                        'is_primary' => $index === 0,
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
