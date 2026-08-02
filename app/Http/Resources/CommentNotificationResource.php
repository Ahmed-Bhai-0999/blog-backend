<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type,
            'is_read'       => $this->is_read,
            'message'       => $this->data['message'] ?? null,
            'created_at'    => $this->created_at->diffForHumans(),
            'created_at_full' => $this->created_at->format('d M Y h:i A'),
            'comment'       => [
                                'id' => $this->comment?->id,
                                'comment' => $this->comment?->comment,
                                'post_id' => $this->comment?->post_id,
                            ],
            'user'          => [
                                'id' => $this->user?->id,
                                'name' => $this->user?->name,
                                'avatar' => $this->user?->avatar,
                            ]
        ];
    }
}