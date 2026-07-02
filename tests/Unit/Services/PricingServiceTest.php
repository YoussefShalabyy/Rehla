<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Listing;
use App\Models\PlatformSetting;
use App\Services\Booking\PricingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new PricingService();
    }

    public function test_calculates_correct_total_for_3_night_stay()
    {
        // 3 nights, 1000 base = 3000
        // cleaning 200 = 3200
        // platform fee 10% = 320
        // total = 3520
        
        PlatformSetting::set('platform_fee_percentage', 10);

        $listing = Listing::factory()->make([
            'base_price_cents' => 1000,
            'cleaning_fee_cents' => 200,
            'extra_guest_fee_cents' => 50,
            'max_guests' => 2,
        ]);

        $checkIn = Carbon::today()->format('Y-m-d');
        $checkOut = Carbon::today()->addDays(3)->format('Y-m-d');

        $result = $this->pricingService->calculate($listing, $checkIn, $checkOut, 2);

        $this->assertEquals(3, $result->nights);
        $this->assertEquals(3000, $result->baseTotalCents);
        $this->assertEquals(200, $result->cleaningFeeCents);
        $this->assertEquals(0, $result->extraGuestFeeCents); // No extra guests
        $this->assertEquals(320, $result->platformFeeCents);
        $this->assertEquals(3520, $result->grandTotalCents);
    }

    public function test_adds_platform_fee_from_platform_settings()
    {
        // Change platform fee to 15%
        PlatformSetting::set('platform_fee_percentage', 15);

        $listing = Listing::factory()->make([
            'base_price_cents' => 1000,
            'cleaning_fee_cents' => 0,
            'max_guests' => 2,
        ]);

        $result = $this->pricingService->calculate(
            $listing, 
            Carbon::today()->format('Y-m-d'), 
            Carbon::today()->addDays(1)->format('Y-m-d'), 
            2
        );

        $this->assertEquals(1000, $result->baseTotalCents);
        $this->assertEquals(150, $result->platformFeeCents); // 15% of 1000
        $this->assertEquals(1150, $result->grandTotalCents);
    }

    public function test_platform_fee_is_always_integer_cents()
    {
        PlatformSetting::set('platform_fee_percentage', 12.5); // Fractional percentage

        $listing = Listing::factory()->make([
            'base_price_cents' => 105, // 105 cents
            'cleaning_fee_cents' => 0,
            'max_guests' => 2,
        ]);

        $result = $this->pricingService->calculate(
            $listing, 
            Carbon::today()->format('Y-m-d'), 
            Carbon::today()->addDays(1)->format('Y-m-d'), 
            2
        );

        // 12.5% of 105 = 13.125
        // Expect rounding to 13 cents
        $this->assertIsInt($result->platformFeeCents);
        $this->assertEquals(13, $result->platformFeeCents);
        $this->assertEquals(118, $result->grandTotalCents);
    }

    public function test_extra_guest_fee_applied_when_guests_exceed_base()
    {
        PlatformSetting::set('platform_fee_percentage', 0); // No fee for easier math

        $listing = Listing::factory()->make([
            'base_price_cents' => 1000,
            'cleaning_fee_cents' => 0,
            'extra_guest_fee_cents' => 200,
            'max_guests' => 2,
        ]);

        // 3 guests = 1 extra guest
        // 2 nights = 200 * 1 * 2 = 400 extra fee
        // Base = 2000
        // Total = 2400

        $result = $this->pricingService->calculate(
            $listing, 
            Carbon::today()->format('Y-m-d'), 
            Carbon::today()->addDays(2)->format('Y-m-d'), 
            3
        );

        $this->assertEquals(400, $result->extraGuestFeeCents);
        $this->assertEquals(2400, $result->grandTotalCents);
    }
}
