<?php

namespace App\Http\Resources;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Every pricing a brand offers, grouped as the customer sees it.
 *
 * Wraps the array PricingController assembled. Grouping and render order are decided there,
 * because the controller needs the grouped result to choose which campaign priced the table.
 *
 * @property array{promotion_code: ?string, renewal_coupon_code: ?string, single: list<Plan>, family: list<Plan>} $resource
 */
class PricingTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * promotion_code and renewal_coupon_code echo what actually took effect, so a client finds
     * out that a code did not apply by finding it absent here rather than by reading an error.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'promotion_code' => $this->resource['promotion_code'],
            'renewal_coupon_code' => $this->resource['renewal_coupon_code'],
            'single' => PricingResource::collection($this->resource['single']),
            'family' => PricingResource::collection($this->resource['family']),
        ];
    }
}
