<?php

namespace App\Models;

use Database\Factories\PromotionFactory;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'brand_id',
    'campaign_id',
    'code',
    'expires_at',
])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    /**
     * The one promotion that only applies to a customer holding a signed nonce code. Membership
     * hardcodes this too — there is no "requires a nonce" column to port.
     */
    public const NONCE_REQUIRED_CODE = 'ORIG';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the campaign this promotion prices the table with.
     *
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Find the promotion a code names, or null when it does not apply.
     *
     * Fails soft for every reason — absent, unknown, expired, another brand's, or nonce-gated
     * without a valid nonce. None of them is an error: the customer is always shown a price.
     */
    public static function resolve(Brand $brand, ?string $code, ?string $nonceCode): ?self
    {
        if ($code === null) {
            return null;
        }

        $promotion = $brand->promotions()
            ->where('code', $code)
            // Grouped deliberately. Membership's active() scope chains where()->orWhereNull()
            // unbracketed, which resolves as (brand AND code AND not expired) OR expires_at IS
            // NULL and hands back another brand's non-expiring promotion. That bug is not ported.
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();

        if ($promotion === null) {
            return null;
        }

        return $promotion->code === self::NONCE_REQUIRED_CODE && ! $brand->verifyNonceCode($nonceCode)
            ? null
            : $promotion;
    }
}
