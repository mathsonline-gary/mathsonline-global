<?php

use App\Models\Brand;

test('a brand is returned by its code', function () {
    $brand = Brand::factory()->create([
        'code' => 'MOL_AU',
        'name' => 'MathsOnline',
        'market' => 'Australia',
        'currency' => 'AUD',
        'marketing_website' => 'https://www.mathsonline.co/au',
        'info_email' => 'info@mathsonline.com.au',
        'feedback_email' => 'feedback@mathsonline.com.au',
        'support_phone' => '123-456-7890',
        'social_facebook' => 'https://www.facebook.com/mathsonline',
        'social_instagram' => 'https://www.instagram.com/mathsonline',
        'stripe_publishable_key' => 'pk_test_example',
        'google_recaptcha_site_key' => 'recaptcha-site-key',
        'google_maps_api_key' => 'maps-api-key',
        'google_tag_manager_container_id' => 'GTM-EXAMPLE',
    ]);

    $this->getJson("/api/v2/brands/{$brand->code}")
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'code' => 'MOL_AU',
                'name' => 'MathsOnline',
                'market' => 'Australia',
                'currency' => 'AUD',
                'marketing_website' => 'https://www.mathsonline.co/au',
                'info_email' => 'info@mathsonline.com.au',
                'feedback_email' => 'feedback@mathsonline.com.au',
                'support_phone' => '123-456-7890',
                'social_facebook' => 'https://www.facebook.com/mathsonline',
                'social_instagram' => 'https://www.instagram.com/mathsonline',
                'stripe' => [
                    'publishable_key' => 'pk_test_example',
                ],
                'google' => [
                    'recaptcha_site_key' => 'recaptcha-site-key',
                    'maps_api_key' => 'maps-api-key',
                    'tag_manager_container_id' => 'GTM-EXAMPLE',
                ],
            ],
        ]);
});

test('an unconfigured integration is absent rather than null', function () {
    $brand = Brand::factory()->create([
        'stripe_publishable_key' => null,
        'google_recaptcha_site_key' => null,
        'google_maps_api_key' => null,
        'google_tag_manager_container_id' => null,
    ]);

    $this->getJson("/api/v2/brands/{$brand->code}")
        ->assertOk()
        ->assertJsonMissingPath('data.stripe')
        ->assertJsonMissingPath('data.google');
});

test('a partly configured integration carries only the keys that are set', function () {
    $brand = Brand::factory()->create([
        'google_recaptcha_site_key' => 'recaptcha-site-key',
        'google_maps_api_key' => null,
        'google_tag_manager_container_id' => null,
    ]);

    $this->getJson("/api/v2/brands/{$brand->code}")
        ->assertOk()
        ->assertJsonPath('data.google', ['recaptcha_site_key' => 'recaptcha-site-key']);
});

test('no secret or internal setting leaves the table', function () {
    $brand = Brand::factory()->create([
        'nonce_secret' => 'nonce-secret-value',
        'stripe_secret_key' => 'sk_test_secret-value',
        'stripe_webhook_secret' => 'whsec_secret-value',
        'google_recaptcha_secret_key' => 'recaptcha-secret-value',
        'keap_account_key' => 'keap-secret-value',
    ]);

    $response = $this->getJson("/api/v2/brands/{$brand->code}");

    $response->assertOk();

    expect($response->getContent())
        ->not->toContain('secret-value')
        ->not->toContain($brand->hash)
        ->not->toContain($brand->noreply_email);
});

test('a code that matches no brand is not found', function () {
    $this->getJson('/api/v2/brands/MOL_XX')->assertNotFound();
});

test('a market slug is not a brand code', function () {
    Brand::factory()->create(['code' => 'MOL_AU']);

    $this->getJson('/api/v2/brands/au')->assertNotFound();
});
