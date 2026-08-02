<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
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
            'name'      => $this->name,
            'email'     => $this->email,
            'phone'     => $this->phone,
            'subject'   => $this->subject,
            'message'   => $this->message,
            'reply'     => $this->reply,
            'is_read'   => $this->is_read,
            'replied_at'=> $this->replied_at,
            'user'      => $this->whenLoaded('user'),
            'created_at'=> $this->created_at->format('d M Y h:i A'),
        ];
    }
}
