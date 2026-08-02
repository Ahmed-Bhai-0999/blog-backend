<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'username'   => $this->username,
            'email'      => $this->email,
            'avatar'     => $this->getFirstMediaUrl('avatar'),
            'phone'      => $this->phone,
            'status'     => $this->status,
            'roles'      => $this->getRoleNames(),
            'created_at' => $this->created_at,
            'deleted_at'  => $this->deleted_at,
        ];
    }
}
