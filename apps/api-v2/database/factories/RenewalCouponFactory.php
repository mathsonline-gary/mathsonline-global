<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\RenewalCoupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RenewalCoupon>
 */
class RenewalCouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'campaign_id' => Campaign::factory(),
            'code' => Str::upper(fake()->unique()->lexify('????????')),
            'expires_at' => now()->addYear(),
            'redeemed_at' => null,
        ];
    }

    /**
     * Indicate that the coupon has expired.
     */
    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    /**
     * Indicate that the coupon has already been spent.
     */
    public function redeemed(): static
    {
        return $this->state(['redeemed_at' => now()->subDay()]);
    }
}
