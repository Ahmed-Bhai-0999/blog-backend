<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
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
            'module'        => $this->module,
            'action'        => $this->action,
            'description'   => $this->description,
            'subject_type'  => $this->subject_type,
            'subject_id'    => $this->subject_id,
            'ip_address'    => $this->ip_address,
            'browser'       => $this->browser,
            'platform'      => $this->platform,
            'user'          =>[
                                'id'    => $this->user?->id,
                                'name'  => $this->user?->name,
                                'email' => $this->user?->email,
                            ],
            'created_at'    => $this->created_at,
        ];
    }
}
