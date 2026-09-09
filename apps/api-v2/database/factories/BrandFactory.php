<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = fake()->unique()->domainName();

        return [
            'hash' => Str::random(32),
            'code' => Str::upper(fake()->unique()->lexify('???_??')),
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'market' => fake()->country(),
            'currency' => fake()->currencyCode(),
            'domain' => $domain,
            'marketing_website' => "https://www.{$domain}",
            'info_email' => "info@{$domain}",
            'feedback_email' => "feedback@{$domain}",
            'noreply_email' => "noreply@{$domain}",
            'tech_email' => "tech@{$domain}",
            'sales_emails' => ["sales.1@{$domain}", "sales.2@{$domain}"],
            'support_phone' => fake()->phoneNumber(),
            'social_facebook' => 'https://www.facebook.com/'.fake()->userName(),
            'social_instagram' => 'https://www.instagram.com/'.fake()->userName(),

            // Placeholders. Never copy a real key into a factory or a seeder.
            'stripe_publishable_key' => 'pk_test_'.Str::random(24),
            'google_recaptcha_site_key' => 'test-recaptcha-site-key',
            'google_maps_api_key' => 'test-maps-api-key',
            'google_tag_manager_container_id' => 'GTM-TEST123',
        ];
    }
}
