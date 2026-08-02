<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
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
            'content'       => $this->content,
            'slug'          => $this->slug,
            'template'      => $this->template,
            'meta_title'    => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'status'        => $this->status,
            'published_at'  => $this->published_at,
            'image'         => $this->getFirstMediaUrl('featured_image'),
            'creator'       => $this->creator?->name,
            'updater'       => $this->updater?->name,
            'author'        => $this->user?->name,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
