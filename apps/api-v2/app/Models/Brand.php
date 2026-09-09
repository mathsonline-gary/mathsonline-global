<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'hash',
    'code',
    'name',
    'description',
    'market',
    'currency',
    'domain',
    'marketing_website',
    'info_email',
    'feedback_email',
    'noreply_email',
    'tech_emails',
    'sales_emails',
    'support_phone',
    'social_facebook',
    'social_instagram',
    'nonce_secret',
    'trial_mode',
    'testing_plans_enabled',
    'stripe_publishable_key',
    'stripe_secret_key',
    'stripe_webhook_secret',
    'stripe_test_mode',
    'paypal_url',
    'paypal_account_id',
    'paypal_notify_url',
    'keap_account_key',
    'google_recaptcha_site_key',
    'google_recaptcha_secret_key',
    'google_maps_api_key',
    'google_tag_manager_container_id',
    'google_analytics_tag_id',
    'google_ads_tag_id',
    'conversion_pixel_id',
    'hotjar_id',
    'facebook_pixel_id',
])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sales_emails' => 'array',
            'tech_emails' => 'array',
            'testing_plans_enabled' => 'boolean',
            'stripe_test_mode' => 'boolean',
        ];
    }
}
