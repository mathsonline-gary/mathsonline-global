<?php

namespace App\Models;

use Database\Factories\RenewalCouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'brand_id',
    'campaign_id',
    'code',
    'expires_at',
    'redeemed_at',
])]
class RenewalCoupon extends Model
{
    /** @use HasFactory<RenewalCouponFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    /**
     * Get the campaign this coupon prices the table with.
     *
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Determine whether this coupon can still be spent.
     */
    public function isRedeemable(): bool
    {
        return $this->expires_at->isFuture() && $this->redeemed_at === null;
    }

    /**
     * Find the renewal coupon a code names, or null when it does not apply.
     *
     * Only this brand's own table is consulted. Membership falls back to syncing the coupon from
     * the MathsOnline core system; that read-through is not migrated, so a coupon which has never
     * been synced simply does not apply — which the description permits, every code failing soft.
     */
    public static function resolve(Brand $brand, ?string $code): ?self
    {
        $coupon = $code === null
            ? null
            : $brand->renewalCoupons()->where('code', $code)->first();

        return $coupon?->isRedeemable() ? $coupon : null;
    }
}
