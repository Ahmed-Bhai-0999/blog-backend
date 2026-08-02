<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'meta_title'        => $this->meta_title,
            'meta_description'  => $this->meta_description,
            'meta_keywords'     => $this->meta_keywords,
            'canonical_url'     => $this->canonical_url,
            'robots'            => $this->robots,
            'og_title'          => $this->og_title,
            'og_description'    => $this->og_description,
            'twitter_title'     => $this->twitter_title,
            'twitter_description'=> $this->twitter_description,
            'og_image'          => $this->getFirstMediaUrl('og_image'),
            'twitter_image'     => $this->getFirstMediaUrl('twitter_image'),
            'schema_markup'     => $this->schema_markup,
            'user'              => $this->whenLoaded('user'),
            'created_at'        => $this->created_at,
        ];
    }
}
