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

        // 2. Create 5 Admin users who will own all listings
        // NOTE: Each developer running this seeder on their local machine will get
        // a fresh set of admin and customer users. This is intentional for local dev.
        $admins = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = User::create([
                'uuid'     => Str::uuid(),
                'name'     => "Admin Num{$i}",
                'email'    => "admin_seed{$i}@example.com",
                'password' => bcrypt('password'),
                'phone'    => "+2011000000{$i}",
                'role'     => UserRole::Admin,
            ]);
            Wallet::create(['uuid' => Str::uuid(), 'user_id' => $user->id, 'balance_cents' => 0]);
            $admins[] = $user;
        }

        // 3. Create 20 Customer users
        $customers = [];
        for ($i = 1; $i <= 20; $i++) {
            $user = User::create([
                'uuid'     => Str::uuid(),
                'name'     => "Customer Num{$i}",
                'email'    => "customer_seed{$i}@example.com",
                'password' => bcrypt('password'),
                'phone'    => "+2012000000{$i}",
                'role'     => UserRole::Customer,
            ]);
            Wallet::create(['uuid' => Str::uuid(), 'user_id' => $user->id, 'balance_cents' => random_int(10000, 500000)]);
            $customers[] = $user;
        }

        // 4. Define listing templates to ensure all UI sections are populated
        $templates = [
            ['type' => ListingType::Property, 'prop_type' => PropertyType::Villa,      'car_cat' => null, 'name' => 'Beautiful Villa',    'images' => ['https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&q=80', 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&q=80', 'https://images.unsplash.com/photo-1613977257363-707ba9348227?w=800&q=80']],
            ['type' => ListingType::Property, 'prop_type' => PropertyType::Apartment,  'car_cat' => null, 'name' => 'Luxury Apartment',  'images' => ['https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&q=80', 'https://images.unsplash.com/photo-1502672260266-1c15a8223041?w=800&q=80', 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&q=80']],
            ['type' => ListingType::Property, 'prop_type' => PropertyType::Hotel,      'car_cat' => null, 'name' => 'Grand Hotel Suite', 'images' => ['https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80', 'https://images.unsplash.com/photo-1551882547-ff40c0d5b5df?w=800&q=80', 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=800&q=80']],
            ['type' => ListingType::Property, 'prop_type' => PropertyType::Room,       'car_cat' => null, 'name' => 'Cozy Private Room', 'images' => ['https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=800&q=80', 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80', 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?w=800&q=80']],
            ['type' => ListingType::Car,      'prop_type' => null,                     'car_cat' => 'luxury',  'name' => 'Mercedes S Class', 'images' => ['https://images.unsplash.com/photo-1563720223185-11003d516935?w=800&q=80', 'https://images.unsplash.com/photo-1609521263047-f8f205293f24?w=800&q=80', 'https://images.unsplash.com/photo-1555626906-fcf10d6851b4?w=800&q=80']],
            ['type' => ListingType::Car,      'prop_type' => null,                     'car_cat' => 'sports',  'name' => 'Ferrari Spider',   'images' => ['https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=800&q=80', 'https://images.unsplash.com/photo-1614200187524-dc4b892acf16?w=800&q=80', 'https://images.unsplash.com/photo-1592198084033-aade902d1aae?w=800&q=80']],
            ['type' => ListingType::Car,      'prop_type' => null,                     'car_cat' => 'economy', 'name' => 'Toyota Corolla',   'images' => ['https://images.unsplash.com/photo-1541899481282-d53bffe3c35d?w=800&q=80', 'https://images.unsplash.com/photo-1590362891991-f700075c1973?w=800&q=80', 'https://images.unsplash.com/photo-1605810731427-da28892d1921?w=800&q=80']],
            ['type' => ListingType::Car,      'prop_type' => null,                     'car_cat' => 'family',  'name' => 'Honda CRV SUV',    'images' => ['https://images.unsplash.com/photo-1550130635-c3cbf4c1eb16?w=800&q=80', 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?w=800&q=80', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?w=800&q=80']],
        ];

        // 5. Create Listings — each admin gets one of each template
        $listings = [];
        foreach ($admins as $admin) {
            foreach ($templates as $tpl) {
                $isCar = $tpl['type'] === ListingType::Car;

                $listing = Listing::create([
                    'uuid'                  => Str::uuid(),
                    'created_by'            => $admin->id,
                    'title'                 => $tpl['name'] . ' ' . random_int(100, 999),
                    'description'           => 'This is a fantastic place or car for your next trip. Enjoy premium comfort and a seamless experience.',
                    'type'                  => $tpl['type'],
                    'property_type'         => $tpl['prop_type'],
                    'category'              => $tpl['car_cat'],
                    'status'                => ListingStatus::Active,
                    'base_price_cents'      => random_int(5000, 50000),
                    'cleaning_fee_cents'    => $isCar ? 0 : 5000,
                    'extra_guest_fee_cents' => 1000,
                    'max_guests'            => $isCar ? 4 : random_int(2, 10),
                    'country'               => 'Egypt',
                    'city'                  => ['Cairo', 'Alexandria', 'Gouna', 'Sharm El Sheikh'][array_rand(['Cairo', 'Alexandria', 'Gouna', 'Sharm El Sheikh'])],
                    'address'               => '123 Random St, Area ' . random_int(1, 100),
                    'latitude'              => 30.0444 + (lcg_value() / 10),
                    'longitude'             => 31.2357 + (lcg_value() / 10),
                    'is_instant_bookable'   => true,
                ]);

                // Attach amenities
                $listing->amenities()->attach(array_rand(array_flip($amenityIds), 3));

                // Attach 3 static unsplash images
                foreach ($tpl['images'] as $index => $imgUrl) {
                    Media::create([
                        'uuid'        => Str::uuid(),
                        'entity_type' => 'listing',
                        'entity_id'   => $listing->id,
                        'type'        => 'image',
                        'url'         => $imgUrl,
                        'public_id'   => 'seeded/' . $listing->uuid . '/' . $index,
                        'provider'    => 'local',
                        'is_primary'  => $index === 0,
                        'order'       => $index,
                    ]);
                }

                $listings[] = $listing;
            }
        }

        // 6. Create Bookings with Payments and Reviews
        $bookingStatuses = [BookingStatus::Completed, BookingStatus::Confirmed, BookingStatus::Pending, BookingStatus::Cancelled];

        foreach ($listings as $listing) {
            $bookingCount = random_int(2, 5);
            for ($b = 0; $b < $bookingCount; $b++) {
                $customer  = $customers[array_rand($customers)];
                $checkIn   = now()->subDays(random_int(10, 90))->format('Y-m-d');
                $checkOut  = now()->subDays(random_int(1, 9))->format('Y-m-d');
                $status    = $bookingStatuses[array_rand($bookingStatuses)];
                $nights    = max(1, (int) round((strtotime($checkOut) - strtotime($checkIn)) / 86400));
                $total     = $listing->base_price_cents * $nights + $listing->cleaning_fee_cents;
                $platFee   = (int) round($total * 0.10);

                $booking = Booking::create([
                    'uuid'               => Str::uuid(),
                    'booking_reference'  => 'SEED-' . strtoupper(Str::random(8)),
                    'listing_id'         => $listing->id,
                    'customer_id'        => $customer->id,
                    'check_in_date'      => $checkIn,
                    'check_out_date'     => $checkOut,
                    'guests_count'       => random_int(1, min($listing->max_guests, 4)),
                    'total_amount_cents' => $total,
                    'platform_fee_cents' => $platFee,
                    'status'             => $status,
                    'payment_status'     => $status === BookingStatus::Completed ? PaymentStatus::Paid : PaymentStatus::Pending,
                    'pricing_snapshot'   => json_encode([
                        'nights'                => $nights,
                        'base_total_cents'      => $listing->base_price_cents * $nights,
                        'cleaning_fee_cents'    => $listing->cleaning_fee_cents,
                        'extra_guest_fee_cents' => 0,
                        'platform_fee_cents'    => $platFee,
                        'grand_total_cents'     => $total,
                    ]),
                ]);

                // Create payment for completed bookings
                if ($status === BookingStatus::Completed) {
                    Payment::create([
                        'uuid'                   => Str::uuid(),
                        'booking_id'             => $booking->id,
                        'amount_cents'           => $total,
                        'gateway'                => PaymentGateway::Paymob,
                        'status'                 => PaymentStatus::Paid,
                        'gateway_transaction_id' => 'SEED_' . strtoupper(Str::random(10)),
                        'provider_response'      => ['seeded' => true],
                    ]);

                    // Add a review for some completed bookings
                    if (random_int(0, 1)) {
                        $rating = random_int(3, 5);
                        Review::create([
                            'uuid'        => Str::uuid(),
                            'booking_id'  => $booking->id,
                            'reviewer_id' => $customer->id,
                            'listing_id'  => $listing->id,
                            'rating'      => $rating,
                            'comment'     => 'Great experience! ' . ($rating === 5 ? 'Absolutely loved it.' : 'Would recommend.'),
                            'status'      => \App\Enums\ReviewStatus::Approved,
                        ]);
                    }
                }
            }
        }

        // 7. Recalculate real average ratings for all listings
        foreach ($listings as $listing) {
            $stats = Review::where('listing_id', $listing->id)
                ->where('status', \App\Enums\ReviewStatus::Approved)
                ->selectRaw('COUNT(*) as total, AVG(rating) as average')
                ->first();

            $listing->update([
                'average_rating' => $stats->average ? round((float) $stats->average, 2) : 0.00,
                'total_reviews'  => (int) $stats->total,
            ]);
        }
    }
}
