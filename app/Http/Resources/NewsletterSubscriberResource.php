<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsletterSubscriberResource extends JsonResource
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
            'email'         => $this->email,
            'status'        => $this->status,
            'subscribed_at' => $this->subscribed_at,
            'unsubscribed_at'=> $this->unsubscribed_at,
            'user'           => $this->whenLoaded('user',function(){
                                    return [
                                        'id'    => $this->user->id,
                                        'name'  =>  $this->user->name,
                                    ];
                                }),
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,

        ];
    }
}
