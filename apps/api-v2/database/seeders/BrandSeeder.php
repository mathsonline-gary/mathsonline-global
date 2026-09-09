<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Seed the 'brands' table with the project's reference data.
     *
     * The three brands BrandCode declares in packages/openapi-v2, ported from membership's own
     * BrandSeeder. A code outside that enum is not a brand, so the rest are not seeded. Every
     * third-party key here is a placeholder — membership commits real-format ones, we do not.
     */
    public function run(): void
    {
        Brand::create([
            'hash' => 'bb970d07d6d8bbf876da0728e146387d',
            'code' => 'MOL_AU',
            'name' => 'MathsOnline',
            'description' => 'MathsOnline (Australia)',
            'market' => 'Australia',
            'currency' => 'AUD',
            'domain' => 'localhost:8001',
            'marketing_website' => 'http://localhost:8001',
            'info_email' => 'info@mathsonline.com.au',
            'feedback_email' => 'feedback@mathsonline.com.au',
            'noreply_email' => 'noreply@mathsonline.com.au',
            'tech_email' => 'tech.1@mathsonline.com.au',
            'sales_emails' => [
                'sales.1.1@mathsonline.com.au',
                'sales.1.2@mathsonline.com.au',
            ],
            'support_phone' => '123-456-7890',
            'stripe_publishable_key' => 'pk_test_MOL_AU_placeholder',
            'stripe_test_mode' => true,
            'google_recaptcha_site_key' => 'placeholder-recaptcha-site-key',
            'google_maps_api_key' => 'placeholder-maps-api-key',
            'google_tag_manager_container_id' => 'GTM-AU00000',
        ]);

        Brand::create([
            'hash' => '23e2017a4a563f9f5ad69cf4295ec7a8',
            'code' => 'MOL_UK',
            'name' => 'MathsOnline',
            'description' => 'MathsOnline (United Kingdom)',
            'market' => 'United Kingdom',
            'currency' => 'GBP',
            'domain' => 'localhost:8002',
            'marketing_website' => 'http://localhost:8002',
            'info_email' => 'info@mathsonline.co',
            'feedback_email' => 'feedback@mathsonline.co',
            'noreply_email' => 'noreply@mathsonline.co',
            'tech_email' => 'tech.2@mathsonline.com.au',
            'sales_emails' => [
                'sales.2.1@mathsonline.com.au',
                'sales.2.2@mathsonline.com.au',
            ],
            'support_phone' => null,
            'stripe_publishable_key' => 'pk_test_MOL_UK_placeholder',
            'stripe_test_mode' => true,
            'google_recaptcha_site_key' => 'placeholder-recaptcha-site-key',
            'google_maps_api_key' => 'placeholder-maps-api-key',
            'google_tag_manager_container_id' => 'GTM-UK00000',
        ]);

        Brand::create([
            'hash' => '306212fdc3d090f71e359ac605caf504',
            'code' => 'MOL_US',
            'name' => 'MathOnline',
            'description' => 'MathOnline (United States)',
            'market' => 'United States',
            'currency' => 'USD',
            'domain' => 'localhost:8004',
            'marketing_website' => 'http://localhost:8004',
            'info_email' => 'info@mathonline.com',
            'feedback_email' => 'feedback@mathonline.com',
            'noreply_email' => 'noreply@mathonline.com',
            'tech_email' => 'tech.4@mathsonline.com.au',
            'sales_emails' => [
                'sales.4.1@mathsonline.com.au',
                'sales.4.2@mathsonline.com.au',
            ],
            'support_phone' => '123-456-7890',
            'stripe_publishable_key' => 'pk_test_MOL_US_placeholder',
            'stripe_test_mode' => true,
            'google_recaptcha_site_key' => 'placeholder-recaptcha-site-key',
            'google_maps_api_key' => 'placeholder-maps-api-key',
            'google_tag_manager_container_id' => 'GTM-US00000',
        ]);
    }
}
