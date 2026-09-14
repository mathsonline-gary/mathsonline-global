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
     * The currency each seeded brand prices in. Membership keeps it on the brand, not the plan.
     *
     * @var array<int, string>
     */
    private const CURRENCIES = [1 => 'aud', 2 => 'gbp', 4 => 'usd'];

    /**
     * The campaigns of the three seeded brands: membership's id => [brand, code].
     *
     * Every campaign membership gives brands 1, 2 and 4 except brand 4's 'ORIGINAL', which no
     * other seeded brand has. The ids are membership's, so a campaign keeps one identity across
     * both repositories.
     *
     * @var array<int, array{int, string}>
     */
    private const CAMPAIGNS = [
        36 => [1, Campaign::DEFAULT_CODE],
        37 => [1, '12PLUS6'],
        38 => [1, '12PLUS3'],
        39 => [1, '10DISC'],
        40 => [1, '20DISC'],
        41 => [1, '50DISC'],
        42 => [1, 'INSTALMENT4X3'],
        1 => [2, Campaign::DEFAULT_CODE],
        2 => [2, '12PLUS6'],
        3 => [2, '12PLUS3'],
        4 => [2, '10DISC'],
        5 => [2, '20DISC'],
        6 => [2, '50DISC'],
        27 => [2, 'INSTALMENT4X3'],
        7 => [4, Campaign::DEFAULT_CODE],
        8 => [4, '12PLUS6'],
        9 => [4, '12PLUS3'],
        10 => [4, '10DISC'],
        11 => [4, '20DISC'],
        12 => [4, '50DISC'],
        28 => [4, 'INSTALMENT4X3'],
    ];

    /**
     * Seed the campaigns, plans and promotions behind showPricing.
     *
     * Every row is membership's, id included, so the two databases agree on what a campaign or a
     * plan is. Membership's 'price_saved' is NULL wherever nothing is saved, and 0 here, because
     * the Pricing schema on the wire has no null; that is the only value that differs.
     *
     * Stripe price ids are not ported. Nothing here takes a payment, and a checkout endpoint can
     * bring its own column when it needs one.
     *
     * The XMAS promotions are ours: membership seeds no promotions, and showPricing needs a code
     * to resolve. No renewal coupons either — a coupon is issued to one customer for one renewal,
     * so there is no reference data for it. Tests use the factory.
     */
    public function run(): void
    {
        foreach (self::CAMPAIGNS as $id => [$brandId, $code]) {
            Campaign::forceCreate(['id' => $id, 'brand_id' => $brandId, 'code' => $code]);

            if ($code === '50DISC') {
                Promotion::create([
                    'brand_id' => $brandId,
                    'campaign_id' => $id,
                    // The code the description's own example uses.
                    'code' => 'XMAS',
                    'expires_at' => null,
                ]);
            }
        }

        foreach ($this->plans() as $id => [$brandId, $code, $priceOriginal, $price, $priceSaved, $recurring, $months, $extraMonths, $installments, $students, $type]) {
            Plan::forceCreate([
                'id' => $id,
                'brand_id' => $brandId,
                'code' => $code,
                'price' => $price,
                'price_original' => $priceOriginal,
                'price_saved' => $priceSaved,
                'currency' => self::CURRENCIES[$brandId],
                'is_recurring' => $recurring,
                // Membership sets no other interval on any of these plans.
                'billing_period_interval' => 'month',
                'billing_period_count' => $months,
                'billing_period_extra_count' => $extraMonths,
                'installment_count' => $installments,
                'student_limit' => $students,
                'type' => $type,
            ]);
        }

        foreach ($this->campaignPlans() as $campaignId => $planIds) {
            Campaign::find($campaignId)->plans()->attach($planIds);
        }
    }

    /**
     * Membership's plans for the three seeded brands, in its ids.
     *
     * A code is not unique within a brand: membership prices the same plan differently per
     * campaign and gives every variant the same code, so brand 1 has four rows coded 'M1'. The id
     * is what identifies a plan, and the campaign it hangs off is what makes it reachable.
     *
     * @return array<int, array{int, string, float, float, float, bool, int, int, int, int, PlanType}>
     */
    private function plans(): array
    {
        return [
            1 => [2, 'M1', 18.95, 18.95, 0, true, 1, 0, 0, 1, PlanType::Standard],
            2 => [2, 'Y1', 119.00, 119.00, 108.40, false, 12, 0, 0, 1, PlanType::Standard],
            3 => [2, 'M3', 29.95, 29.95, 0, true, 1, 0, 0, 5, PlanType::Standard],
            4 => [2, 'Y3', 199.00, 199.00, 160.40, false, 12, 0, 0, 5, PlanType::Standard],
            5 => [2, 'HS_M1', 18.95, 9.48, 0, true, 1, 0, 0, 1, PlanType::Homeschool],
            6 => [2, 'HS_Y1', 119.00, 59.50, 0, false, 12, 0, 0, 1, PlanType::Homeschool],
            7 => [2, 'HS_M3', 29.95, 14.98, 0, true, 1, 0, 0, 5, PlanType::Homeschool],
            8 => [2, 'HS_Y3', 199.00, 99.50, 0, false, 12, 0, 0, 5, PlanType::Homeschool],
            9 => [2, 'Y1', 119.00, 119.00, 222.10, false, 12, 6, 0, 1, PlanType::Standard],
            10 => [2, 'Y3', 199.00, 199.00, 340.10, false, 12, 6, 0, 5, PlanType::Standard],
            11 => [2, 'HS_Y1', 119.00, 59.50, 0, false, 12, 6, 0, 1, PlanType::Homeschool],
            12 => [2, 'HS_Y3', 199.00, 99.50, 0, false, 12, 6, 0, 5, PlanType::Homeschool],
            13 => [2, 'Y1', 119.00, 119.00, 165.25, false, 12, 3, 0, 1, PlanType::Standard],
            14 => [2, 'Y3', 199.00, 199.00, 250.25, false, 12, 3, 0, 5, PlanType::Standard],
            15 => [2, 'HS_Y1', 119.00, 59.50, 0, false, 12, 3, 0, 1, PlanType::Homeschool],
            16 => [2, 'HS_Y3', 199.00, 99.50, 0, false, 12, 3, 0, 5, PlanType::Homeschool],
            17 => [2, 'M1', 18.95, 17.06, 0, true, 1, 0, 0, 1, PlanType::Standard],
            18 => [2, 'Y1', 119.00, 107.10, 0, false, 12, 0, 0, 1, PlanType::Standard],
            19 => [2, 'M3', 29.95, 26.96, 0, true, 1, 0, 0, 5, PlanType::Standard],
            20 => [2, 'Y3', 199.00, 179.10, 0, false, 12, 0, 0, 5, PlanType::Standard],
            21 => [2, 'M1', 18.95, 15.16, 0, true, 1, 0, 0, 1, PlanType::Standard],
            22 => [2, 'Y1', 119.00, 95.20, 0, false, 12, 0, 0, 1, PlanType::Standard],
            23 => [2, 'M3', 29.95, 23.96, 0, true, 1, 0, 0, 5, PlanType::Standard],
            24 => [2, 'Y3', 199.00, 159.20, 0, false, 12, 0, 0, 5, PlanType::Standard],
            25 => [2, 'M1', 18.95, 9.48, 0, true, 1, 0, 0, 1, PlanType::Standard],
            26 => [2, 'Y1', 119.00, 59.50, 0, false, 12, 0, 0, 1, PlanType::Standard],
            27 => [2, 'M3', 29.95, 14.98, 0, true, 1, 0, 0, 5, PlanType::Standard],
            28 => [2, 'Y3', 199.00, 99.50, 0, false, 12, 0, 0, 5, PlanType::Standard],

            33 => [4, 'M1', 39.90, 39.90, 0, true, 1, 0, 0, 1, PlanType::Standard],
            34 => [4, 'Y1', 257.00, 257.00, 221.00, false, 12, 0, 0, 1, PlanType::Standard],
            35 => [4, 'M3', 59.90, 59.90, 0, true, 1, 0, 0, 5, PlanType::Standard],
            36 => [4, 'Y3', 397.00, 397.00, 321.00, false, 12, 0, 0, 5, PlanType::Standard],
            37 => [4, 'HS_M1', 39.90, 19.95, 0, true, 1, 0, 0, 1, PlanType::Homeschool],
            38 => [4, 'HS_Y1', 257.00, 128.50, 0, false, 12, 0, 0, 1, PlanType::Homeschool],
            39 => [4, 'HS_M3', 59.90, 29.95, 0, true, 1, 0, 0, 5, PlanType::Homeschool],
            40 => [4, 'HS_Y3', 397.00, 198.50, 0, false, 12, 0, 0, 5, PlanType::Homeschool],
            41 => [4, 'Y1', 257.00, 257.00, 221.00, false, 12, 6, 0, 1, PlanType::Standard],
            42 => [4, 'Y3', 397.00, 397.00, 321.00, false, 12, 6, 0, 5, PlanType::Standard],
            43 => [4, 'HS_Y1', 257.00, 128.50, 0, false, 12, 6, 0, 1, PlanType::Homeschool],
            44 => [4, 'HS_Y3', 397.00, 198.50, 0, false, 12, 6, 0, 5, PlanType::Homeschool],
            45 => [4, 'Y1', 257.00, 257.00, 221.00, false, 12, 3, 0, 1, PlanType::Standard],
            46 => [4, 'Y3', 397.00, 397.00, 321.00, false, 12, 3, 0, 5, PlanType::Standard],
            47 => [4, 'HS_Y1', 257.00, 128.50, 0, false, 12, 3, 0, 1, PlanType::Homeschool],
            48 => [4, 'HS_Y3', 397.00, 198.50, 0, false, 12, 3, 0, 5, PlanType::Homeschool],
            49 => [4, 'M1', 39.90, 35.91, 0, true, 1, 0, 0, 1, PlanType::Standard],
            50 => [4, 'Y1', 257.00, 231.30, 0, false, 12, 0, 0, 1, PlanType::Standard],
            51 => [4, 'M3', 59.90, 53.91, 0, true, 1, 0, 0, 5, PlanType::Standard],
            52 => [4, 'Y3', 397.00, 357.30, 0, false, 12, 0, 0, 5, PlanType::Standard],
            53 => [4, 'M1', 39.90, 31.92, 0, true, 1, 0, 0, 1, PlanType::Standard],
            54 => [4, 'Y1', 257.00, 205.60, 0, false, 12, 0, 0, 1, PlanType::Standard],
            55 => [4, 'M3', 59.90, 47.92, 0, true, 1, 0, 0, 5, PlanType::Standard],
            56 => [4, 'Y3', 397.00, 317.60, 0, false, 12, 0, 0, 5, PlanType::Standard],
            57 => [4, 'M1', 39.90, 19.95, 0, true, 1, 0, 0, 1, PlanType::Standard],
            58 => [4, 'Y1', 257.00, 128.50, 0, false, 12, 0, 0, 1, PlanType::Standard],
            59 => [4, 'M3', 59.90, 29.95, 0, true, 1, 0, 0, 5, PlanType::Standard],
            60 => [4, 'Y3', 397.00, 198.50, 0, false, 12, 0, 0, 5, PlanType::Standard],

            159 => [2, 'Y1_4X3', 29.75, 29.75, 0, false, 12, 0, 4, 1, PlanType::Standard],
            160 => [2, 'Y3_4X3', 49.75, 49.75, 0, false, 12, 0, 4, 5, PlanType::Standard],

            162 => [4, 'Y1_4X3', 64.25, 64.25, 0, false, 12, 0, 4, 1, PlanType::Standard],
            163 => [4, 'Y3_4X3', 99.25, 99.25, 0, false, 12, 0, 4, 5, PlanType::Standard],

            167 => [1, 'M1', 39.90, 39.90, 0, true, 1, 0, 0, 1, PlanType::Standard],
            168 => [1, 'M1', 39.90, 35.91, 0, true, 1, 0, 0, 1, PlanType::Standard],
            169 => [1, 'M1', 39.90, 31.92, 0, true, 1, 0, 0, 1, PlanType::Standard],
            170 => [1, 'M1', 39.90, 19.95, 0, true, 1, 0, 0, 1, PlanType::Standard],
            171 => [1, 'S1', 167.00, 167.00, 0, false, 6, 0, 0, 1, PlanType::Standard],
            172 => [1, 'S1', 167.00, 150.30, 0, false, 6, 0, 0, 1, PlanType::Standard],
            173 => [1, 'S1', 167.00, 133.50, 0, false, 6, 0, 0, 1, PlanType::Standard],
            174 => [1, 'S1', 167.00, 83.50, 0, false, 6, 0, 0, 1, PlanType::Standard],
            175 => [1, 'Y1', 257.00, 257.00, 0, false, 12, 0, 0, 1, PlanType::Standard],
            176 => [1, 'Y1', 257.00, 231.30, 0, false, 12, 0, 0, 1, PlanType::Standard],
            177 => [1, 'Y1', 257.00, 205.60, 0, false, 12, 0, 0, 1, PlanType::Standard],
            178 => [1, 'Y1', 257.00, 128.50, 0, false, 12, 0, 0, 1, PlanType::Standard],
            179 => [1, 'M3', 59.90, 59.90, 0, true, 1, 0, 0, 5, PlanType::Standard],
            180 => [1, 'M3', 59.90, 53.91, 0, true, 1, 0, 0, 5, PlanType::Standard],
            181 => [1, 'M3', 59.90, 47.92, 0, true, 1, 0, 0, 5, PlanType::Standard],
            182 => [1, 'M3', 59.90, 29.95, 0, true, 1, 0, 0, 5, PlanType::Standard],
            183 => [1, 'S3', 267.00, 267.00, 0, false, 6, 0, 0, 5, PlanType::Standard],
            184 => [1, 'S3', 267.00, 240.30, 0, false, 6, 0, 0, 5, PlanType::Standard],
            185 => [1, 'S3', 267.00, 213.60, 0, false, 6, 0, 0, 5, PlanType::Standard],
            186 => [1, 'S3', 267.00, 133.50, 0, false, 6, 0, 0, 5, PlanType::Standard],
            187 => [1, 'Y3', 397.00, 397.00, 0, false, 12, 0, 0, 5, PlanType::Standard],
            188 => [1, 'Y3', 397.00, 357.30, 0, false, 12, 0, 0, 5, PlanType::Standard],
            189 => [1, 'Y3', 397.00, 317.60, 0, false, 12, 0, 0, 5, PlanType::Standard],
            190 => [1, 'Y3', 397.00, 198.50, 0, false, 12, 0, 0, 5, PlanType::Standard],
            191 => [1, 'HS_M1', 39.90, 19.95, 0, true, 1, 0, 0, 1, PlanType::Homeschool],
            192 => [1, 'HS_S1', 167.00, 83.50, 0, false, 6, 0, 0, 1, PlanType::Homeschool],
            193 => [1, 'HS_Y1', 257.00, 128.50, 128.50, false, 12, 0, 0, 1, PlanType::Homeschool],
            194 => [1, 'HS_M3', 59.90, 29.95, 0, true, 1, 0, 0, 5, PlanType::Homeschool],
            195 => [1, 'HS_S3', 267.00, 133.50, 0, false, 6, 0, 0, 5, PlanType::Homeschool],
            196 => [1, 'HS_Y3', 397.00, 198.50, 198.50, false, 12, 0, 0, 5, PlanType::Homeschool],
            197 => [1, 'Y1', 257.00, 257.00, 0, false, 12, 3, 0, 1, PlanType::Standard],
            198 => [1, 'Y3', 397.00, 397.00, 0, false, 12, 3, 0, 5, PlanType::Standard],
            199 => [1, 'HS_Y1', 257.00, 128.50, 0, false, 12, 3, 0, 1, PlanType::Homeschool],
            200 => [1, 'HS_Y3', 397.00, 198.50, 0, false, 12, 3, 0, 5, PlanType::Homeschool],
            201 => [1, 'Y1', 257.00, 257.00, 0, false, 12, 6, 0, 1, PlanType::Standard],
            202 => [1, 'Y3', 397.00, 397.00, 0, false, 12, 6, 0, 5, PlanType::Standard],
            203 => [1, 'HS_Y1', 257.00, 128.50, 0, false, 12, 6, 0, 1, PlanType::Homeschool],
            204 => [1, 'HS_Y3', 397.00, 198.50, 0, false, 12, 6, 0, 5, PlanType::Homeschool],
            205 => [1, 'Y1_4X3', 64.25, 64.25, 0, false, 12, 0, 4, 1, PlanType::Standard],
            206 => [1, 'Y3_4X3', 99.25, 99.25, 0, false, 12, 0, 4, 5, PlanType::Standard],
        ];
    }

    /**
     * Which plans each campaign offers — membership's 'campaign_plan', for these campaigns.
     *
     * A plan may sit in several campaigns: the monthly plans are the same rows in a brand's
     * DEFAULT, 12PLUS6 and 12PLUS3 campaigns, because only the annual plans differ between them.
     *
     * @return array<int, list<int>>
     */
    private function campaignPlans(): array
    {
        return [
            36 => [167, 171, 175, 179, 183, 187, 191, 192, 193, 194, 195, 196],
            37 => [167, 171, 179, 183, 191, 192, 194, 195, 201, 202, 203, 204],
            38 => [167, 171, 179, 183, 191, 192, 194, 195, 197, 198, 199, 200],
            39 => [168, 172, 176, 180, 184, 188],
            40 => [169, 173, 177, 181, 185, 189],
            41 => [170, 174, 178, 182, 186, 190],
            42 => [205, 206],
            1 => [1, 2, 3, 4, 5, 6, 7, 8],
            2 => [1, 3, 5, 7, 9, 10, 11, 12],
            3 => [1, 3, 5, 7, 13, 14, 15, 16],
            4 => [17, 18, 19, 20],
            5 => [21, 22, 23, 24],
            6 => [25, 26, 27, 28],
            27 => [159, 160],
            7 => [33, 34, 35, 36, 37, 38, 39, 40],
            8 => [33, 35, 37, 39, 41, 42, 43, 44],
            9 => [33, 35, 37, 39, 45, 46, 47, 48],
            10 => [49, 50, 51, 52],
            11 => [53, 54, 55, 56],
            12 => [57, 58, 59, 60],
            28 => [162, 163],
        ];
    }
}
