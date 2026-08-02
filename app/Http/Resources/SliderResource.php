<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SliderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'subtitle'      => $this->subtitle,
            'description'   => $this->description,
            'button_text'   => $this->button_text,
            'button_url'    => $this->button_url,
            'image'         => $this->getFirstMediaUrl('image'),
            'sort_order'    => $this->sort_order,
            'status'        => $this->status,
            'published_at'  => $this->published_at,
            'created_by'    => $this->creator?->name,
            'updated_by'    => $this->updater?->name,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
