<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'slug'          => $this->slug,
            'excerpt'       => $this->excerpt
                                ?: Str::limit(strip_tags($this->content), 120),
            'content'       => $this->content,
            'status'        => $this->status,
            'published_at'  => $this->published_at,
            'views'         => $this->views,
            'featured_image' => $this->getFirstMediaUrl('featured_image') ?: null,
            'category'      => [
                                'id' => $this->category?->id,
                                'name' => $this->category?->name,
                            ],
            'author'        => [
                                'id' => $this->user?->id,
                                'name' => $this->user?->name,
                            ],
            'tags' => $this->tags,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}