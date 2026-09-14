<?php

namespace Database\Factories;

use App\Enums\PlanType;
use App\Models\Brand;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * A standard single monthly plan with nothing discounted. Family, annual and installment
     * variants are one attribute each, so they are set at the call site rather than given a state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 60);

        return [
            'brand_id' => Brand::factory(),
            'code' => fake()->unique()->bothify('?#'),
            'price' => $price,
            'price_original' => $price,
            'price_saved' => 0,
            'currency' => 'aud',
            'is_recurring' => true,
            'billing_period_interval' => 'month',
            'billing_period_count' => 1,
            'billing_period_extra_count' => 0,
            'installment_count' => 0,
            'student_limit' => 1,
            'type' => PlanType::Standard,
        ];
    }

    /**
     * Indicate that the plan is offered to homeschoolers.
     */
    public function homeschool(): static
    {
        return $this->state(['type' => PlanType::Homeschool]);
    }

    /**
     * Indicate that the plan exercises a real payment without a real charge.
     */
    public function testing(): static
    {
        return $this->state(['type' => PlanType::Testing]);
    }
}
