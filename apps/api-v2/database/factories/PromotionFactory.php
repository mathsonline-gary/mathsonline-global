<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
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
            'code' => Str::upper(fake()->unique()->lexify('????')),
            'expires_at' => now()->addYear(),
        ];
    }

    /**
     * Indicate that the promotion has expired.
     */
    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    /**
     * Indicate that the promotion never expires.
     */
    public function everlasting(): static
    {
        return $this->state(['expires_at' => null]);
    }
}
