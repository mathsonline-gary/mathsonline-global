<?php

namespace App\Models;

use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'brand_id',
    'code',
])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    /**
     * The code of the campaign a brand prices with when no promotion or renewal coupon applies.
     * Membership carries no flag for it; the code is the convention.
     */
    public const DEFAULT_CODE = 'DEFAULT';

    public $timestamps = false;

    /**
     * Get the plans this campaign offers.
     *
     * @return BelongsToMany<Plan, $this>
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'campaign_plan', 'campaign_id', 'plan_id');
    }
}
