<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Settings every seeded brand shares.
     *
     * Membership varies none of these across brands, so they are stated once. Every third-party
     * key and secret is a placeholder — membership commits real-format ones, we do not.
     *
     * @var array<string, string|bool>
     */
    private const SHARED = [
        'domain' => 'localhost:3000',
        'nonce_secret' => 'placeholder-nonce-secret',
        'testing_plans_enabled' => true,
        'stripe_test_mode' => true,
        'google_recaptcha_site_key' => 'placeholder-recaptcha-site-key',
        'google_recaptcha_secret_key' => 'placeholder-recaptcha-secret-key',
        'google_maps_api_key' => 'placeholder-maps-api-key',
    ];

    /**
     * Seed the 'brands' table with the project's reference data.
     *
     * Membership's nine brands less its id 6, which repeats id 1's code MOL_AU — 'brands.code' is
     * unique here because route binding needs it to be. Only three of the remaining codes are in
     * BrandCode; the other five are seeded so a brand outside the enum exists to test against.
     *
     * Ported from membership's BrandSeeder and BrandSettingSeeder, which split across a 1:1 pair
     * of tables this schema does not have. Columns membership leaves null for every brand
     * (analytics, ads, pixels, hotjar) are omitted — the column default is already null.
     *
     * The ids are membership's, force-filled because 'id' is deliberately not fillable.
     */
    public function run(): void
    {
        foreach ($this->brands() as $brand) {
            $payments = array_pop($brand);

            Brand::forceCreate([
                ...self::SHARED,
                ...$payments ? $this->paymentKeys($brand['code'], $brand['id']) : [],
                ...$brand,
            ]);
        }
    }

    /**
     * The placeholder payment configuration of a brand membership has set up to take money.
     *
     * @return array<string, string>
     */
    private function paymentKeys(string $code, int $id): array
    {
        return [
            'stripe_publishable_key' => "pk_test_{$code}_placeholder",
            'stripe_secret_key' => "sk_test_{$code}_placeholder",
            'stripe_webhook_secret' => "whsec_{$code}_placeholder",
            'paypal_url' => 'https://www.sandbox.paypal.com',
            'paypal_account_id' => 'placeholder-paypal-account-id',
            'paypal_notify_url' => "http://localhost/api/v2/webhooks/paypal/{$id}",
            'keap_account_key' => 'placeholder-keap-account-key',
        ];
    }

    /**
     * Membership's brands, in its own ids, with its own copy and contact points.
     *
     * 'marketing_website' is the purchase app's market slug. The five brands outside BrandCode
     * have no front end, so theirs is nominal — membership points each at a local port instead.
     *
     * The last element of each row says whether membership configures the brand for payments. It
     * is positional rather than keyed to keep it out of the column list, and popped off before
     * the row is written.
     *
     * @return list<array<string, mixed>>
     */
    private function brands(): array
    {
        return [
            [
                'id' => 1,
                'hash' => 'bb970d07d6d8bbf876da0728e146387d',
                'code' => 'MOL_AU',
                'name' => 'MathsOnline',
                'description' => 'MathsOnline (Australia)',
                'market' => 'Australia',
                'currency' => 'AUD',
                'marketing_website' => 'http://localhost:3000/au',
                'info_email' => 'info@mathsonline.com.au',
                'feedback_email' => 'feedback@mathsonline.com.au',
                'noreply_email' => 'noreply@mathsonline.com.au',
                'tech_emails' => ['tech.1@mathsonline.com.au'],
                'sales_emails' => ['sales.1.1@mathsonline.com.au', 'sales.1.2@mathsonline.com.au'],
                'support_phone' => '123-456-7890',
                // Membership seeds both null for every brand. One brand carries its real public
                // accounts so a non-null social link is visible in seeded data, not only in a test.
                'social_facebook' => 'https://www.facebook.com/mathsonline',
                'social_instagram' => 'https://www.instagram.com/mathsonline',
                'google_tag_manager_container_id' => 'GTM-AU00000',
                true,
            ],
            [
                'id' => 2,
                'hash' => '23e2017a4a563f9f5ad69cf4295ec7a8',
                'code' => 'MOL_UK',
                'name' => 'MathsOnline',
                'description' => 'MathsOnline (United Kingdom)',
                'market' => 'United Kingdom',
                'currency' => 'GBP',
                'marketing_website' => 'http://localhost:3000/uk',
                'info_email' => 'info@mathsonline.co',
                'feedback_email' => 'feedback@mathsonline.co',
                'noreply_email' => 'noreply@mathsonline.co',
                'tech_emails' => ['tech.2@mathsonline.com.au'],
                'sales_emails' => ['sales.2.1@mathsonline.com.au', 'sales.2.2@mathsonline.com.au'],
                'support_phone' => null,
                'google_tag_manager_container_id' => 'GTM-UK00000',
                true,
            ],
            [
                'id' => 3,
                'hash' => '2bcaed3265cf9a87df1c9a358d4a9e06',
                'code' => 'MB_NZ',
                'name' => 'MathsBuddy',
                'description' => 'MathsBuddy (New Zealand)',
                'market' => 'New Zealand',
                'currency' => 'NZD',
                'marketing_website' => 'http://localhost:3000/nz',
                'info_email' => 'info@mathsbuddy.co.nz',
                'feedback_email' => 'feedback@mathsbuddy.co.nz',
                'noreply_email' => 'noreply@mathsbuddy.co.nz',
                'tech_emails' => ['tech.3@mathsonline.com.au'],
                'sales_emails' => ['sales.3.1@mathsonline.com.au', 'sales.3.2@mathsonline.com.au'],
                'support_phone' => '123-456-7890',
                true,
            ],
            [
                'id' => 4,
                'hash' => '306212fdc3d090f71e359ac605caf504',
                'code' => 'MOL_US',
                'name' => 'MathOnline',
                'description' => 'MathOnline (United States)',
                'market' => 'United States',
                'currency' => 'USD',
                'marketing_website' => 'http://localhost:3000/us',
                'info_email' => 'info@mathonline.com',
                'feedback_email' => 'feedback@mathonline.com',
                'noreply_email' => 'noreply@mathonline.com',
                'tech_emails' => ['tech.4@mathsonline.com.au'],
                'sales_emails' => ['sales.4.1@mathsonline.com.au', 'sales.4.2@mathsonline.com.au'],
                'support_phone' => '123-456-7890',
                'google_tag_manager_container_id' => 'GTM-US00000',
                true,
            ],
            [
                'id' => 5,
                'hash' => '7cc28171da5841414019681474d0fc00',
                'code' => 'MOL_KE',
                'name' => 'MathsOnline',
                'description' => 'MathsOnline (Kenya)',
                'market' => 'Kenya',
                'currency' => 'KES',
                'marketing_website' => 'http://localhost:3000/ke',
                'info_email' => 'info@mathsonline.co.ke',
                'feedback_email' => 'feedback@mathsonline.co.ke',
                'noreply_email' => 'noreply@mathsonline.co.ke',
                'tech_emails' => ['tech.5@mathsonline.com.au'],
                'sales_emails' => ['sales.5.1@mathsonline.com.au', 'sales.5.2@mathsonline.com.au'],
                'support_phone' => '123-456-7890',
                false,
            ],
            [
                'id' => 7,
                'hash' => 'f49a1b662de210fcbd3f20017c7cef6c',
                'code' => 'MB_SA',
                'name' => 'MathsBuddy',
                'description' => 'MathsBuddy (South Africa)',
                'market' => 'South Africa',
                'currency' => 'ZAR',
                'marketing_website' => 'http://localhost:3000/za',
                'info_email' => 'info@mathsbuddy.co.za',
                'feedback_email' => 'feedback@mathsbuddy.co.za',
                'noreply_email' => 'noreply@mathsbuddy.co.za',
                'tech_emails' => ['tech.7@mathsonline.com.au'],
                'sales_emails' => ['sales.7.1@mathsonline.com.au', 'sales.7.2@mathsonline.com.au'],
                'support_phone' => '123-456-7890',
                false,
            ],
            [
                'id' => 8,
                'hash' => 'e1958fda9b517208dfca737a365db786',
                'code' => 'CTC_US',
                'name' => 'CTCMath',
                'description' => 'CTCMath (United States)',
                'market' => 'United States',
                'currency' => 'USD',
                'marketing_website' => 'http://localhost:3000/ctc',
                'info_email' => 'info@ctcmath.com',
                'feedback_email' => 'feedback@ctcmath.com',
                'noreply_email' => 'noreply@ctcmath.com',
                'tech_emails' => ['tech.8@mathsonline.com.au'],
                'sales_emails' => ['sales.8.1@mathsonline.com.au', 'sales.8.2@mathsonline.com.au'],
                'support_phone' => '123-456-7890',
                true,
            ],
            [
                'id' => 9,
                'hash' => 'd9f0bc7c38c78728f9a4932843db2df0',
                'code' => 'MOL_IN',
                'name' => 'MathsOnline',
                'description' => 'MathsOnline (India)',
                'market' => 'India',
                'currency' => 'INR',
                'marketing_website' => 'http://localhost:3000/in',
                'info_email' => 'info@mathsonline.co.in',
                'feedback_email' => 'feedback@mathsonline.co.in',
                'noreply_email' => 'noreply@mathsonline.co.in',
                'tech_emails' => ['tech.9@mathsonline.com.au'],
                'sales_emails' => ['sales.9.1@mathsonline.com.au', 'sales.9.2@mathsonline.com.au'],
                'support_phone' => '123-456-7890',
                true,
            ],
        ];
    }
}
