<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'status'     => $this->status,
            'posts_count'=> $this->posts_count ?? $this->posts->count(),
            'creator'    => [
                                'id'   => $this->creator?->id,
                                'name' => $this->creator?->name,
                            ],
            'updater'    => [
                                'id'   => $this->updater?->id,
                                'name' => $this->updater?->name,
                            ],
            'posts'      => $this->posts->map(function ($post) {
                                return [
                                    'id'    => $post->id,
                                    'title' => $post->title,
                                    'slug'  => $post->slug,
                                ];
                            }),
            'created_at'  => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'  => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at'  => $this->deleted_at,
        ];
    }
}