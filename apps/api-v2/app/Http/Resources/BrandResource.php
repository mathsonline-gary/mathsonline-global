<?php

namespace App\Http\Resources;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Brand
 */
class BrandResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * An allow-list: the table carries secrets and internal settings, the Brand schema in
     * packages/openapi-v2 does not.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $google = array_filter([
            'recaptcha_site_key' => $this->google_recaptcha_site_key,
            'maps_api_key' => $this->google_maps_api_key,
            'tag_manager_container_id' => $this->google_tag_manager_container_id,
        ], fn (?string $value) => $value !== null);

        return [
            'code' => $this->code,
            'name' => $this->name,
            'market' => $this->market,
            'currency' => $this->currency,
            'marketing_website' => $this->marketing_website,
            'info_email' => $this->info_email,
            'feedback_email' => $this->feedback_email,
            'support_phone' => $this->support_phone,
            'social_facebook' => $this->social_facebook,
            'social_instagram' => $this->social_instagram,

            // An unconfigured integration is absent rather than null — see the Brand schema.
            ...($this->stripe_publishable_key === null ? [] : [
                'stripe' => ['publishable_key' => $this->stripe_publishable_key],
            ]),
            ...($google === [] ? [] : ['google' => $google]),
        ];
    }
}
