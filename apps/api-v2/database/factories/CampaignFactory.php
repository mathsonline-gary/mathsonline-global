<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
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
            'code' => Str::upper(fake()->unique()->lexify('?????')),
        ];
    }

    /**
     * Indicate that the campaign is the brand's default.
     */
    public function default(): static
    {
        return $this->state(['code' => Campaign::DEFAULT_CODE]);
    }
}
