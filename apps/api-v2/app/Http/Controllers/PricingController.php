<?php

namespace App\Http\Controllers;

use App\Enums\PlanType;
use App\Http\Resources\PricingTableResource;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Plan;
use App\Models\Promotion;
use App\Models\RenewalCoupon;
use App\Support\TestingToken;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PricingController extends Controller
{
    /**
     * Retrieve a brand's pricing.
     *
     * No FormRequest: every code parameter fails soft by description, so a request class that
     * rejected anything would contradict it and one that only coerced would earn nothing over the
     * typed accessors used here.
     */
    public function show(Request $request, Brand $brand): PricingTableResource
    {
        $type = $request->boolean('homeschool')
            ? PlanType::Homeschool
            : PlanType::Standard;

        // A testing token overrides everything. Testing plans are brand-scoped, not
        // campaign-scoped, so no promotion or coupon applies — and membership ignores homeschool
        // here too.
        if ($brand->testing_plans_enabled && TestingToken::verify($this->queryString($request, 'testing_token'))) {
            $testing = $this->group($brand->plans()->where('type', PlanType::Testing)->get());

            if ($testing !== null) {
                return new PricingTableResource([
                    'promotion_code' => null,
                    'renewal_coupon_code' => null,
                    ...$testing,
                ]);
            }
        }

        $promotion = Promotion::resolve(
            $brand,
            $this->queryString($request, 'promotion_code'),
            $this->queryString($request, 'nonce_code'),
        );

        $coupon = RenewalCoupon::resolve($brand, $this->queryString($request, 'renewal_coupon_code'));

        // Renewal coupon, then promotion, then the brand's own campaign. Each rung carries the
        // codes it would echo, so the loser of a coupon-beats-promotion race comes back null.
        $ladder = array_values(array_filter([
            $coupon === null ? null : [$coupon->campaign, null, $coupon->code],
            $promotion === null ? null : [$promotion->campaign, $promotion->code, null],
            [$brand->campaigns()->where('code', Campaign::DEFAULT_CODE)->first(), null, null],
        ]));

        foreach ($ladder as [$campaign, $promotionCode, $renewalCouponCode]) {
            if ($campaign === null) {
                continue;
            }

            $groups = $this->group($campaign->plans()->where('type', $type)->get());

            // A campaign that cannot fill both groups falls through like any other input that does
            // not apply: the description says both groups are always present and never empty.
            if ($groups !== null) {
                return new PricingTableResource([
                    'promotion_code' => $promotionCode,
                    'renewal_coupon_code' => $renewalCouponCode,
                    ...$groups,
                ]);
            }
        }

        // A data error nothing a client sends can fix, and no response the description declares.
        abort(500, "Brand {$brand->code} has no complete default pricing.");
    }

    /**
     * Split plans into the two groups a client renders, or null if either would be empty.
     *
     * Single is one student and family is more than one — student_limit is the whole rule, never
     * the plan's code.
     *
     * Array order is render order: period length, then installment count, which is the only thing
     * separating an annual plan from the same plan paid in four. The sort is stable, so a
     * remaining tie keeps insertion order.
     *
     * @param  Collection<int, Plan>  $plans
     * @return array{single: list<Plan>, family: list<Plan>}|null
     */
    private function group(Collection $plans): ?array
    {
        $sorted = $plans->sortBy(
            fn (Plan $plan): array => [$plan->period_in_days, $plan->installment_count]
        );

        $single = $sorted->where('student_limit', 1)->values()->all();
        $family = $sorted->where('student_limit', '>', 1)->values()->all();

        if ($single === [] || $family === []) {
            return null;
        }

        return ['single' => $single, 'family' => $family];
    }

    /**
     * Read a query parameter as a non-empty string, or null for anything else.
     *
     * `?promotion_code[]=x` arrives as an array, and this endpoint's whole contract is that no
     * parameter is ever a reason to fail.
     */
    private function queryString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
