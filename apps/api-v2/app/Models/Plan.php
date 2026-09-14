<?php

namespace App\Models;

use App\Enums\PlanType;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'brand_id',
    'code',
    'price',
    'price_original',
    'price_saved',
    'currency',
    'is_recurring',
    'billing_period_interval',
    'billing_period_count',
    'billing_period_extra_count',
    'installment_count',
    'student_limit',
    'type',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * The three money columns cast to float, not decimal:2 — that cast returns a string, and the
     * Pricing schema says `type: number`.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PlanType::class,
            'is_recurring' => 'boolean',
            'price' => 'float',
            'price_original' => 'float',
            'price_saved' => 'float',
            'billing_period_count' => 'integer',
            'billing_period_extra_count' => 'integer',
            'installment_count' => 'integer',
            'student_limit' => 'integer',
        ];
    }

    /**
     * Get the campaigns that offer this plan.
     *
     * @return BelongsToMany<Campaign, $this>
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_plan', 'plan_id', 'campaign_id');
    }

    /**
     * Interact with how long one membership runs, in days.
     *
     * The count plus the free extra count, because that is the length the customer compares.
     * Days are a common denominator for ordering two plans against each other, not a claim
     * about calendars — every row membership has is in months.
     *
     * @return Attribute<int, never>
     */
    protected function periodInDays(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): int => ($attributes['billing_period_count'] + $attributes['billing_period_extra_count'])
                * match ($attributes['billing_period_interval']) {
                    'day' => 1,
                    'week' => 7,
                    'year' => 365,
                    default => 30,
                },
        );
    }
}
