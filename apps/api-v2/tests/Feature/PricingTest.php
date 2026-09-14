<?php

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Plan;
use App\Models\Promotion;
use App\Models\RenewalCoupon;
use App\Support\TestingToken;

/**
 * Create a campaign holding one single-student and one family plan, so it can fill a table.
 *
 * @param  array<string, mixed>  $attributes  applied to both plans
 */
function campaignWith(Brand $brand, string $code, array $attributes = [], bool $homeschool = false): Campaign
{
    $campaign = Campaign::create(['brand_id' => $brand->id, 'code' => $code]);

    $factory = Plan::factory()->when($homeschool, fn ($f) => $f->homeschool());

    $campaign->plans()->attach([
        $factory->create(['brand_id' => $brand->id, 'student_limit' => 1] + $attributes)->id,
        $factory->create(['brand_id' => $brand->id, 'student_limit' => 5] + $attributes)->id,
    ]);

    return $campaign;
}

function defaultCampaign(Brand $brand, array $attributes = [], bool $homeschool = false): Campaign
{
    return campaignWith($brand, Campaign::DEFAULT_CODE, $attributes, $homeschool);
}

/**
 * Create a brand whose default campaign fills the table and whose testing plans do too.
 */
function brandWithTestingPlans(): Brand
{
    $brand = Brand::factory()->create(['testing_plans_enabled' => true]);
    defaultCampaign($brand, ['code' => 'FULL']);

    Plan::factory()->testing()->create(['brand_id' => $brand->id, 'code' => 'TEST1', 'student_limit' => 1]);
    Plan::factory()->testing()->create(['brand_id' => $brand->id, 'code' => 'TEST3', 'student_limit' => 5]);

    return $brand;
}

function pricing(Brand $brand): string
{
    return "/api/v2/brands/{$brand->code}/pricing";
}

test('a brand\'s default pricing is returned grouped single and family', function () {
    $brand = Brand::factory()->create(['code' => 'MOL_AU']);
    $campaign = Campaign::create(['brand_id' => $brand->id, 'code' => Campaign::DEFAULT_CODE]);

    $campaign->plans()->attach([
        Plan::factory()->create([
            'brand_id' => $brand->id,
            'code' => 'M1',
            'price' => 19.97,
            'price_original' => 19.97,
            'price_saved' => 0,
            'currency' => 'aud',
            'is_recurring' => true,
            'student_limit' => 1,
            'billing_period_count' => 1,
            'billing_period_extra_count' => 0,
            'installment_count' => 0,
        ])->id,
        Plan::factory()->create([
            'brand_id' => $brand->id,
            'code' => 'M3',
            'price' => 29.97,
            'price_original' => 29.97,
            'price_saved' => 0,
            'currency' => 'aud',
            'is_recurring' => true,
            'student_limit' => 5,
            'billing_period_count' => 1,
            'billing_period_extra_count' => 0,
            'installment_count' => 0,
        ])->id,
    ]);

    $this->getJson(pricing($brand))
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'promotion_code' => null,
                'renewal_coupon_code' => null,
                'single' => [[
                    'code' => 'M1',
                    'price' => 19.97,
                    'price_original' => 19.97,
                    'price_saved' => 0,
                    'currency' => 'AUD',
                    'recurring' => true,
                    'student_limit' => 1,
                    'billing_period' => ['interval' => 'month', 'count' => 1, 'extra_count' => 0],
                    'installment_count' => 0,
                ]],
                'family' => [[
                    'code' => 'M3',
                    'price' => 29.97,
                    'price_original' => 29.97,
                    'price_saved' => 0,
                    'currency' => 'AUD',
                    'recurring' => true,
                    'student_limit' => 5,
                    'billing_period' => ['interval' => 'month', 'count' => 1, 'extra_count' => 0],
                    'installment_count' => 0,
                ]],
            ],
        ]);
});

test('a pricing covering one student is single and one covering more is family', function () {
    $brand = Brand::factory()->create();
    $campaign = Campaign::create(['brand_id' => $brand->id, 'code' => Campaign::DEFAULT_CODE]);

    // M3 is a family pricing: the group comes from student_limit, never from the code.
    $campaign->plans()->attach([
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'M3', 'student_limit' => 5])->id,
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'M1', 'student_limit' => 1])->id,
    ]);

    $this->getJson(pricing($brand))
        ->assertOk()
        ->assertJsonPath('data.single.0.code', 'M1')
        ->assertJsonPath('data.family.0.code', 'M3');
});

test('pricings are ordered by billing period length', function () {
    $brand = Brand::factory()->create();
    $campaign = Campaign::create(['brand_id' => $brand->id, 'code' => Campaign::DEFAULT_CODE]);

    $campaign->plans()->attach([
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'Y1', 'billing_period_count' => 12])->id,
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'M1', 'billing_period_count' => 1])->id,
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'S1', 'billing_period_count' => 6])->id,
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'M3', 'student_limit' => 5])->id,
    ]);

    $this->getJson(pricing($brand))
        ->assertOk()
        ->assertJsonPath('data.single.*.code', ['M1', 'S1', 'Y1']);
});

test('two pricings differing only by installment count put the single payment first', function () {
    $brand = Brand::factory()->create();
    $campaign = Campaign::create(['brand_id' => $brand->id, 'code' => Campaign::DEFAULT_CODE]);

    $campaign->plans()->attach([
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'Y1_4X3', 'billing_period_count' => 12, 'installment_count' => 4])->id,
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'Y1', 'billing_period_count' => 12, 'installment_count' => 0])->id,
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'Y3', 'student_limit' => 5])->id,
    ]);

    $this->getJson(pricing($brand))
        ->assertOk()
        ->assertJsonPath('data.single.*.code', ['Y1', 'Y1_4X3']);
});

test('the extra billing period count counts toward the render order', function () {
    $brand = Brand::factory()->create();
    $campaign = Campaign::create(['brand_id' => $brand->id, 'code' => Campaign::DEFAULT_CODE]);

    // Twelve months plus six free runs longer than a plain twelve, so it renders after it.
    $campaign->plans()->attach([
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'Y1_PLUS6', 'billing_period_count' => 12, 'billing_period_extra_count' => 6])->id,
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'Y1', 'billing_period_count' => 12])->id,
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'Y3', 'student_limit' => 5])->id,
    ]);

    $this->getJson(pricing($brand))
        ->assertOk()
        ->assertJsonPath('data.single.*.code', ['Y1', 'Y1_PLUS6']);
});

test('currency crosses the wire upper-case', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['currency' => 'gbp']);

    $this->getJson(pricing($brand))
        ->assertOk()
        ->assertJsonPath('data.single.0.currency', 'GBP');
});

test('a pricing with nothing discounted reports a saving of zero', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['price' => 19.97, 'price_original' => 19.97, 'price_saved' => 0]);

    $this->getJson(pricing($brand))
        ->assertOk()
        ->assertJsonPath('data.single.0.price_saved', 0);
});

test('a saving that is not the difference between the prices crosses the wire untouched', function () {
    $brand = Brand::factory()->create();
    // Membership stores the saving against paying monthly, so it is not price_original - price.
    defaultCampaign($brand, ['price' => 119.00, 'price_original' => 119.00, 'price_saved' => 108.40]);

    $this->getJson(pricing($brand))
        ->assertOk()
        ->assertJsonPath('data.single.0.price_saved', 108.40);
});

test('a valid promotion prices the table and is echoed back', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, '50DISC', ['code' => 'HALF']);

    Promotion::factory()->create([
        'brand_id' => $brand->id,
        'campaign_id' => $campaign->id,
        'code' => 'XMAS',
    ]);

    $this->getJson(pricing($brand).'?promotion_code=XMAS')
        ->assertOk()
        ->assertJsonPath('data.promotion_code', 'XMAS')
        ->assertJsonPath('data.renewal_coupon_code', null)
        ->assertJsonPath('data.single.0.code', 'HALF');
});

test('a valid renewal coupon prices the table and is echoed back', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, 'RENEW', ['code' => 'LOYAL']);

    RenewalCoupon::factory()->create([
        'brand_id' => $brand->id,
        'campaign_id' => $campaign->id,
        'code' => 'RC123',
    ]);

    $this->getJson(pricing($brand).'?renewal_coupon_code=RC123')
        ->assertOk()
        ->assertJsonPath('data.renewal_coupon_code', 'RC123')
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.single.0.code', 'LOYAL');
});

test('a renewal coupon takes precedence over a promotion, which then echoes null', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);
    $promoted = campaignWith($brand, '50DISC', ['code' => 'HALF']);
    $renewal = campaignWith($brand, 'RENEW', ['code' => 'LOYAL']);

    Promotion::factory()->create(['brand_id' => $brand->id, 'campaign_id' => $promoted->id, 'code' => 'XMAS']);
    RenewalCoupon::factory()->create(['brand_id' => $brand->id, 'campaign_id' => $renewal->id, 'code' => 'RC123']);

    $this->getJson(pricing($brand).'?promotion_code=XMAS&renewal_coupon_code=RC123')
        ->assertOk()
        ->assertJsonPath('data.renewal_coupon_code', 'RC123')
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.single.0.code', 'LOYAL');
});

test('an unknown promotion code returns the default pricing and echoes null', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);

    $this->getJson(pricing($brand).'?promotion_code=NOPE')
        ->assertOk()
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('an expired promotion returns the default pricing', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, '50DISC', ['code' => 'HALF']);

    Promotion::factory()->expired()->create([
        'brand_id' => $brand->id,
        'campaign_id' => $campaign->id,
        'code' => 'XMAS',
    ]);

    $this->getJson(pricing($brand).'?promotion_code=XMAS')
        ->assertOk()
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('a promotion with no expiry still applies', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, '50DISC', ['code' => 'HALF']);

    Promotion::factory()->everlasting()->create([
        'brand_id' => $brand->id,
        'campaign_id' => $campaign->id,
        'code' => 'XMAS',
    ]);

    $this->getJson(pricing($brand).'?promotion_code=XMAS')
        ->assertOk()
        ->assertJsonPath('data.promotion_code', 'XMAS')
        ->assertJsonPath('data.single.0.code', 'HALF');
});

test('another brand\'s promotion does not apply', function () {
    $brand = Brand::factory()->create();
    $other = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($other, '50DISC', ['code' => 'HALF']);

    // Never expires, which is what membership's ungrouped OR leaks across brands.
    Promotion::factory()->everlasting()->create([
        'brand_id' => $other->id,
        'campaign_id' => $campaign->id,
        'code' => 'XMAS',
    ]);

    $this->getJson(pricing($brand).'?promotion_code=XMAS')
        ->assertOk()
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('a renewal coupon that was never synced does not apply', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);

    $this->getJson(pricing($brand).'?renewal_coupon_code=RC123')
        ->assertOk()
        ->assertJsonPath('data.renewal_coupon_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('an expired renewal coupon returns the default pricing', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, 'RENEW', ['code' => 'LOYAL']);

    RenewalCoupon::factory()->expired()->create([
        'brand_id' => $brand->id,
        'campaign_id' => $campaign->id,
        'code' => 'RC123',
    ]);

    $this->getJson(pricing($brand).'?renewal_coupon_code=RC123')
        ->assertOk()
        ->assertJsonPath('data.renewal_coupon_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('a redeemed renewal coupon returns the default pricing', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, 'RENEW', ['code' => 'LOYAL']);

    RenewalCoupon::factory()->redeemed()->create([
        'brand_id' => $brand->id,
        'campaign_id' => $campaign->id,
        'code' => 'RC123',
    ]);

    $this->getJson(pricing($brand).'?renewal_coupon_code=RC123')
        ->assertOk()
        ->assertJsonPath('data.renewal_coupon_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('another brand\'s renewal coupon does not apply', function () {
    $brand = Brand::factory()->create();
    $other = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($other, 'RENEW', ['code' => 'LOYAL']);

    RenewalCoupon::factory()->create([
        'brand_id' => $other->id,
        'campaign_id' => $campaign->id,
        'code' => 'RC123',
    ]);

    $this->getJson(pricing($brand).'?renewal_coupon_code=RC123')
        ->assertOk()
        ->assertJsonPath('data.renewal_coupon_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('no code parameter is ever a validation error', function (string $query) {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);

    $this->getJson(pricing($brand).'?'.$query)
        ->assertOk()
        ->assertJsonPath('data.single.0.code', 'FULL');
})->with([
    'garbage promotion' => 'promotion_code=%%%',
    'empty promotion' => 'promotion_code=',
    'array promotion' => 'promotion_code[]=XMAS',
    'array renewal coupon' => 'renewal_coupon_code[]=RC123',
    'array nonce' => 'nonce_code[]=x',
    'array testing token' => 'testing_token[]=x',
    'unreadable homeschool' => 'homeschool=maybe',
]);

test('the ORIG promotion does not apply without a nonce code', function () {
    $brand = Brand::factory()->create(['nonce_secret' => 'sh-sh-sh']);
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, 'ORIGINAL', ['code' => 'ORIGINAL']);

    Promotion::factory()->create([
        'brand_id' => $brand->id,
        'campaign_id' => $campaign->id,
        'code' => Promotion::NONCE_REQUIRED_CODE,
    ]);

    $this->getJson(pricing($brand).'?promotion_code='.Promotion::NONCE_REQUIRED_CODE)
        ->assertOk()
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('the ORIG promotion applies with a valid nonce code', function () {
    $this->freezeTime();

    $brand = Brand::factory()->create(['nonce_secret' => 'sh-sh-sh']);
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, 'ORIGINAL', ['code' => 'ORIGINAL']);

    Promotion::factory()->create([
        'brand_id' => $brand->id,
        'campaign_id' => $campaign->id,
        'code' => Promotion::NONCE_REQUIRED_CODE,
    ]);

    $maxTime = time() + 3600;
    $nonce = 'salt,'.$maxTime.','.sha1('salt'.'sh-sh-sh'.$maxTime);

    $this->getJson(pricing($brand).'?promotion_code='.Promotion::NONCE_REQUIRED_CODE.'&nonce_code='.$nonce)
        ->assertOk()
        ->assertJsonPath('data.promotion_code', Promotion::NONCE_REQUIRED_CODE)
        ->assertJsonPath('data.single.0.code', 'ORIGINAL');
});

test('a nonce code that does not verify leaves the ORIG promotion unapplied', function (callable $nonce) {
    $this->freezeTime();

    $brand = Brand::factory()->create(['nonce_secret' => 'sh-sh-sh']);
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, 'ORIGINAL', ['code' => 'ORIGINAL']);

    Promotion::factory()->create([
        'brand_id' => $brand->id,
        'campaign_id' => $campaign->id,
        'code' => Promotion::NONCE_REQUIRED_CODE,
    ]);

    $this->getJson(pricing($brand).'?promotion_code='.Promotion::NONCE_REQUIRED_CODE.'&nonce_code='.$nonce())
        ->assertOk()
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
})->with([
    'expired' => fn () => 'salt,'.($t = time() - 1).','.sha1('salt'.'sh-sh-sh'.$t),
    'tampered hash' => fn () => 'salt,'.($t = time() + 3600).','.sha1('pepper'.'sh-sh-sh'.$t),
    'wrong shape' => fn () => 'salt,'.(time() + 3600),
]);

test('a nonce code does not affect a promotion that needs none', function () {
    $brand = Brand::factory()->create(['nonce_secret' => 'sh-sh-sh']);
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, '50DISC', ['code' => 'HALF']);

    Promotion::factory()->create(['brand_id' => $brand->id, 'campaign_id' => $campaign->id, 'code' => 'XMAS']);

    $this->getJson(pricing($brand).'?promotion_code=XMAS&nonce_code=rubbish')
        ->assertOk()
        ->assertJsonPath('data.promotion_code', 'XMAS')
        ->assertJsonPath('data.single.0.code', 'HALF');
});

test('a brand with no nonce secret never accepts a nonce code', function () {
    $this->freezeTime();

    $brand = Brand::factory()->create(['nonce_secret' => null]);
    defaultCampaign($brand, ['code' => 'FULL']);
    $campaign = campaignWith($brand, 'ORIGINAL', ['code' => 'ORIGINAL']);

    Promotion::factory()->create([
        'brand_id' => $brand->id,
        'campaign_id' => $campaign->id,
        'code' => Promotion::NONCE_REQUIRED_CODE,
    ]);

    $maxTime = time() + 3600;
    $nonce = 'salt,'.$maxTime.','.sha1('salt'.$maxTime);

    $this->getJson(pricing($brand).'?promotion_code='.Promotion::NONCE_REQUIRED_CODE.'&nonce_code='.$nonce)
        ->assertOk()
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('homeschool returns the homeschool pricings of the winning campaign', function () {
    $brand = Brand::factory()->create();
    $campaign = defaultCampaign($brand, ['code' => 'STANDARD']);

    $campaign->plans()->attach([
        Plan::factory()->homeschool()->create(['brand_id' => $brand->id, 'code' => 'HS_M1', 'student_limit' => 1])->id,
        Plan::factory()->homeschool()->create(['brand_id' => $brand->id, 'code' => 'HS_M3', 'student_limit' => 5])->id,
    ]);

    $this->getJson(pricing($brand).'?homeschool=true')
        ->assertOk()
        ->assertJsonPath('data.single.*.code', ['HS_M1'])
        ->assertJsonPath('data.family.*.code', ['HS_M3']);
});

test('homeschool picks within the campaign a promotion won rather than selecting its own', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'STANDARD'], homeschool: true);
    $campaign = campaignWith($brand, '50DISC', ['code' => 'HS_HALF'], homeschool: true);

    Promotion::factory()->create(['brand_id' => $brand->id, 'campaign_id' => $campaign->id, 'code' => 'XMAS']);

    $this->getJson(pricing($brand).'?homeschool=true&promotion_code=XMAS')
        ->assertOk()
        ->assertJsonPath('data.promotion_code', 'XMAS')
        ->assertJsonPath('data.single.0.code', 'HS_HALF');
});

test('a campaign that cannot fill both groups falls back to the default pricing', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'FULL']);

    // A single-only campaign, as membership's instalment campaigns are for homeschoolers.
    $campaign = Campaign::create(['brand_id' => $brand->id, 'code' => 'SINGLEONLY']);
    $campaign->plans()->attach(
        Plan::factory()->create(['brand_id' => $brand->id, 'code' => 'LONELY', 'student_limit' => 1])->id
    );

    Promotion::factory()->create(['brand_id' => $brand->id, 'campaign_id' => $campaign->id, 'code' => 'XMAS']);

    $this->getJson(pricing($brand).'?promotion_code=XMAS')
        ->assertOk()
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('a homeschool request against a campaign with no homeschool pricings falls back to the default', function () {
    $brand = Brand::factory()->create();
    defaultCampaign($brand, ['code' => 'HS_FULL'], homeschool: true);
    $campaign = campaignWith($brand, '50DISC', ['code' => 'HALF']);

    Promotion::factory()->create(['brand_id' => $brand->id, 'campaign_id' => $campaign->id, 'code' => 'XMAS']);

    $this->getJson(pricing($brand).'?homeschool=true&promotion_code=XMAS')
        ->assertOk()
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.single.0.code', 'HS_FULL');
});

test('a valid testing token serves the brand testing plans', function () {
    $brand = brandWithTestingPlans();

    $this->getJson(pricing($brand).'?testing_token='.urlencode(TestingToken::generate()))
        ->assertOk()
        ->assertJsonPath('data.promotion_code', null)
        ->assertJsonPath('data.renewal_coupon_code', null)
        ->assertJsonPath('data.single.0.code', 'TEST1')
        ->assertJsonPath('data.family.0.code', 'TEST3');
});

test('a testing token that does not verify is ignored', function (string $token) {
    $brand = brandWithTestingPlans();

    $this->getJson(pricing($brand).'?testing_token='.urlencode($token))
        ->assertOk()
        ->assertJsonPath('data.single.0.code', 'FULL');
})->with([
    'garbage' => fn () => 'whatever',
    'empty' => fn () => '',
    'expired' => fn () => TestingToken::generate(-1),
    'tampered' => fn () => TestingToken::generate().'x',
]);

test('a testing token is ignored for a brand without testing pricings enabled', function () {
    $brand = Brand::factory()->create(['testing_plans_enabled' => false]);
    defaultCampaign($brand, ['code' => 'FULL']);

    $this->getJson(pricing($brand).'?testing_token=whatever')
        ->assertOk()
        ->assertJsonPath('data.single.0.code', 'FULL');
});

test('pricing for a code that matches no brand is not found', function () {
    $this->getJson('/api/v2/brands/MOL_XX/pricing')->assertNotFound();
});
