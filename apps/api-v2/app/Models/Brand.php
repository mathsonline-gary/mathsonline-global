<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Get the campaigns this brand prices with.
     *
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Get every plan this brand offers, in any campaign or none.
     *
     * @return HasMany<Plan, $this>
     */
    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    /**
     * Get the promotions advertising this brand's campaigns.
     *
     * @return HasMany<Promotion, $this>
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Get the renewal coupons issued against this brand.
     *
     * @return HasMany<RenewalCoupon, $this>
     */
    public function renewalCoupons(): HasMany
    {
        return $this->hasMany(RenewalCoupon::class);
    }

    /**
     * Verify a nonce code minted for this brand.
     *
     * "salt,maxTime,sha1(salt . secret . maxTime)", ported from membership's
     * OrderService::validateNonceCode. SHA-1 stays because whatever mints these still uses it.
     * The comparison is timing-safe here, where membership's is a plain `!=`.
     *
     * Nothing is consumed: the same code verifies until maxTime passes.
     */
    public function verifyNonceCode(?string $nonceCode): bool
    {
        if ($nonceCode === null || $nonceCode === '' || empty($this->nonce_secret)) {
            return false;
        }

        $parts = explode(',', $nonceCode);

        if (count($parts) !== 3) {
            return false;
        }

        [$salt, $maxTime, $hash] = $parts;

        return hash_equals(sha1($salt.$this->nonce_secret.$maxTime), $hash)
            && time() <= (int) $maxTime;
    }
}
