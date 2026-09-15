<?php

namespace App\Http\Resources;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Plan
 */
class PricingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Every field the Pricing schema declares is required, and NOT NULL in the table, so nothing
     * here is conditional. The one transformation is the currency: membership stores "aud" and
     * the wire says ISO 4217 alpha-3 upper-case. The column keeps membership's casing so a
     * re-port stays a straight copy, and this is the single place the wire rule lives.
     *
     * price_saved crosses verbatim. It is stored rather than derived, so it is not always
     * price_original - price and must not be recomputed from them.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'price' => $this->price,
            'price_original' => $this->price_original,
            'price_saved' => $this->price_saved,
            'currency' => Str::upper($this->currency),
            'recurring' => $this->is_recurring,
            'student_limit' => $this->student_limit,
            'billing_period' => [
                'interval' => $this->billing_period_interval,
                'count' => $this->billing_period_count,
                'extra_count' => $this->billing_period_extra_count,
            ],
            'installment_count' => $this->installment_count,
        ];
    }
}
