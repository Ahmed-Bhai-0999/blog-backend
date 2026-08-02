<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'title'     => $this->title,
            'url'       => $this->url,
            'page_id'   => $this->page_id,
            'parent_id' => $this->parent_id,
            'sort_order'=> $this->sort_order,
            'target'    => $this->target,
            'icon'      => $this->icon,
            'status'    => $this->status,
            'children'  => MenuItemResource::collection(
                        $this->whenLoaded('children')
                    ),
            'page'      => $this->whenLoaded('page', function () {
                            return [
                                'id'    => $this->page->id,
                                'title' => $this->page->title,
                                'slug'  => $this->page->slug,
                            ];
                        }),
        ];
    }
}
