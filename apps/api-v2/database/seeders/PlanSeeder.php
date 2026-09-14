<?php

namespace Database\Seeders;

use App\Enums\PlanType;
use App\Models\Campaign;
use App\Models\Plan;
use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed the campaigns, plans and promotions behind showPricing.
     *
     * A representative set for the three brands BrandSeeder creates, with membership's own
     * figures. Not membership's whole PlanSeeder: that is 166 KB of every discount variant every
     * campaign has ever carried, and it exists to mirror production rather than to make a table
     * render. Membership's 'price_saved' is NULL wherever nothing is saved, and 0 here, because
     * the Pricing schema on the wire has no null.
     *
     * Stripe price ids are not ported. Nothing here takes a payment, and a checkout endpoint can
     * bring its own column when it needs one.
     *
     * No renewal coupons: a coupon is issued to one customer for one renewal, so there is no
     * reference data for it. Tests use the factory.
     */
    public function run(): void
    {
        // brand_id => [currency, [code => [price_original, price, price_saved, recurring, months, students, type]]]
        foreach ($this->catalogue() as $brandId => [$currency, $standard, $homeschool, $discounted, $installment]) {
            $default = Campaign::create(['brand_id' => $brandId, 'code' => Campaign::DEFAULT_CODE]);
            $discount = Campaign::create(['brand_id' => $brandId, 'code' => '50DISC']);

            $this->attach($default, $brandId, $currency, $standard, PlanType::Standard);
            $this->attach($default, $brandId, $currency, $homeschool, PlanType::Homeschool);
            $this->attach($default, $brandId, $currency, $installment, PlanType::Standard);
            $this->attach($discount, $brandId, $currency, $discounted, PlanType::Standard);

            Promotion::create([
                'brand_id' => $brandId,
                'campaign_id' => $discount->id,
                // The code the description's own example uses.
                'code' => 'XMAS',
                'expires_at' => null,
            ]);
        }
    }

    /**
     * Create the given plans and offer them in the given campaign.
     *
     * @param  array<string, array{float, float, float, bool, int, int, int}>  $rows
     */
    private function attach(Campaign $campaign, int $brandId, string $currency, array $rows, PlanType $type): void
    {
        foreach ($rows as $code => [$priceOriginal, $price, $priceSaved, $recurring, $months, $students, $installments]) {
            $plan = Plan::create([
                'brand_id' => $brandId,
                'code' => $code,
                'price' => $price,
                'price_original' => $priceOriginal,
                'price_saved' => $priceSaved,
                'currency' => $currency,
                'is_recurring' => $recurring,
                'billing_period_interval' => 'month',
                'billing_period_count' => $months,
                'billing_period_extra_count' => 0,
                'installment_count' => $installments,
                'student_limit' => $students,
                'type' => $type,
            ]);

            $campaign->plans()->attach($plan);
        }
    }

    /**
     * Membership's own figures for the three seeded brands.
     *
     * Brand 1 also carries the four-instalment annual plans, so the render order's second key
     * — a single payment before the instalment plan of the same length — is visible in seeded
     * data and not only in a test.
     *
     * @return array<int, array{string, array<string, array{float, float, float, bool, int, int, int}>, array<string, array{float, float, float, bool, int, int, int}>, array<string, array{float, float, float, bool, int, int, int}>, array<string, array{float, float, float, bool, int, int, int}>}>
     */
    private function catalogue(): array
    {
        return [
            1 => ['aud', [
                'M1' => [39.90, 39.90, 0, true, 1, 1, 0],
                'S1' => [167.00, 167.00, 0, false, 6, 1, 0],
                'Y1' => [257.00, 257.00, 0, false, 12, 1, 0],
                'M3' => [59.90, 59.90, 0, true, 1, 5, 0],
                'S3' => [267.00, 267.00, 0, false, 6, 5, 0],
                'Y3' => [397.00, 397.00, 0, false, 12, 5, 0],
            ], [
                'HS_M1' => [39.90, 19.95, 0, true, 1, 1, 0],
                'HS_S1' => [167.00, 83.50, 0, false, 6, 1, 0],
                'HS_Y1' => [257.00, 128.50, 128.50, false, 12, 1, 0],
                'HS_M3' => [59.90, 29.95, 0, true, 1, 5, 0],
                'HS_S3' => [267.00, 133.50, 0, false, 6, 5, 0],
                'HS_Y3' => [397.00, 198.50, 198.50, false, 12, 5, 0],
            ], [
                'M1_50' => [39.90, 19.95, 0, true, 1, 1, 0],
                'S1_50' => [167.00, 83.50, 0, false, 6, 1, 0],
                'Y1_50' => [257.00, 128.50, 0, false, 12, 1, 0],
                'M3_50' => [59.90, 29.95, 0, true, 1, 5, 0],
                'S3_50' => [267.00, 133.50, 0, false, 6, 5, 0],
                'Y3_50' => [397.00, 198.50, 0, false, 12, 5, 0],
            ], [
                'Y1_4X3' => [64.25, 64.25, 0, false, 12, 1, 4],
                'Y3_4X3' => [99.25, 99.25, 0, false, 12, 5, 4],
            ]],

            2 => ['gbp', [
                'M1' => [18.95, 18.95, 0, true, 1, 1, 0],
                // price_saved is the saving against paying monthly, not price_original - price:
                // twelve months at 18.95 is 227.40, and 227.40 - 119.00 is 108.40. Membership
                // stores it, which is why the description forbids recomputing it.
                'Y1' => [119.00, 119.00, 108.40, false, 12, 1, 0],
                'M3' => [29.95, 29.95, 0, true, 1, 5, 0],
                'Y3' => [199.00, 199.00, 160.40, false, 12, 5, 0],
            ], [
                'HS_M1' => [18.95, 9.48, 0, true, 1, 1, 0],
                'HS_Y1' => [119.00, 59.50, 0, false, 12, 1, 0],
                'HS_M3' => [29.95, 14.98, 0, true, 1, 5, 0],
                'HS_Y3' => [199.00, 99.50, 0, false, 12, 5, 0],
            ], [
                'M1_50' => [18.95, 9.48, 0, true, 1, 1, 0],
                'Y1_50' => [119.00, 59.50, 0, false, 12, 1, 0],
                'M3_50' => [29.95, 14.98, 0, true, 1, 5, 0],
                'Y3_50' => [199.00, 99.50, 0, false, 12, 5, 0],
            ], []],

            4 => ['usd', [
                'M1' => [39.90, 39.90, 0, true, 1, 1, 0],
                'Y1' => [257.00, 257.00, 221.00, false, 12, 1, 0],
                'M3' => [59.90, 59.90, 0, true, 1, 5, 0],
                'Y3' => [397.00, 397.00, 321.00, false, 12, 5, 0],
            ], [
                'HS_M1' => [39.90, 19.95, 0, true, 1, 1, 0],
                'HS_Y1' => [257.00, 128.50, 0, false, 12, 1, 0],
                'HS_M3' => [59.90, 29.95, 0, true, 1, 5, 0],
                'HS_Y3' => [397.00, 198.50, 0, false, 12, 5, 0],
            ], [
                'M1_50' => [39.90, 19.95, 0, true, 1, 1, 0],
                'Y1_50' => [257.00, 128.50, 0, false, 12, 1, 0],
                'M3_50' => [59.90, 29.95, 0, true, 1, 5, 0],
                'Y3_50' => [397.00, 198.50, 0, false, 12, 5, 0],
            ], []],
        ];
    }
}
