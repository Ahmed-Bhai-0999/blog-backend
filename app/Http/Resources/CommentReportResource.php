<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentReportResource extends JsonResource
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
            'reason'    => $this->reason,
            'status'    => $this->status,
            'admin_notes'=> $this->admin_notes,
            'created_at'=> $this->created_at->format('d M Y'),
            'comment'   => [
                            'id' => $this->comment?->id,
                            'comment' => $this->comment?->comment,
                        ],
            'reporter'  => [
                            'id' => $this->reporter?->id,
                            'name' => $this->reporter?->name,
                        ]   
        ];
    }
}
