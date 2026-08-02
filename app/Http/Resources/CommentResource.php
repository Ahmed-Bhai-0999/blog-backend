<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = auth()->id();
        $guestToken = $request->header('X-Guest-Token') ?: $request->input('guest_token');

        // Check visitor reaction
        $visitorReaction = null;
        if ($userId) {
            $reactionModel = $this->reactions()->where('user_id', $userId)->first();
        } elseif ($guestToken) {
            $reactionModel = $this->reactions()->where('guest_token', $guestToken)->first();
        } else {
            $reactionModel = null;
        }

        if ($reactionModel) {
            $visitorReaction = $reactionModel->reaction; // 0 = Dislike, 1 = Like
        }

        // Badges calculation
        $badges = [];
        if ($this->user) {
            if ($this->user_id === $this->post?->user_id) {
                $badges[] = 'AUTHOR';
            }
            
            // Check roles via Spatie or ID fallback
            if ($this->user->id === 1 || (method_exists($this->user, 'hasRole') && $this->user->hasRole('Admin'))) {
                $badges[] = 'ADMIN';
            } elseif (method_exists($this->user, 'hasRole') && $this->user->hasRole('Moderator')) {
                $badges[] = 'MODERATOR';
            }
            
            if ($this->user->status === 'Active') {
                $badges[] = 'VERIFIED';
            }
        }

        // Edit/Delete Permissions (15 minute window check)
        $canEdit = false;
        $canDelete = false;

        if ($userId && $this->user_id === $userId) {
            $canDelete = true; // Users can delete their own comments
            if ($this->created_at && $this->created_at->diffInMinutes(now()) <= 15) {
                $canEdit = true; // Only within 15 mins
            }
        }

        return [
            'id'             => $this->id,
            'comment'        => $this->comment,
            'status'         => $this->status,
            'post'           => [
                'id'    => $this->post?->id,
                'title' => $this->post?->title,
                'slug'  => $this->post?->slug,
            ],
            'guest_name'     => $this->guest_name,
            'guest_email'    => $this->guest_email,
            'author'         => $this->user ? [
                'id'     => $this->user->id,
                'name'   => $this->user->name,
                'avatar' => $this->user->avatar,
            ] : null,
            'parent_id'      => $this->parent_id,
            'replies_count'  => $this->replies_count ?? $this->replies()->count(),
            
            // Reactions
            'likes_count'    => $this->likes_count ?? $this->likes()->count(),
            'dislikes_count' => $this->dislikes_count ?? $this->dislikes()->count(),
            'user_reaction'  => $visitorReaction,

            // Badges & Permissions
            'badges'         => $badges,
            'can_edit'       => $canEdit,
            'can_delete'     => $canDelete,
            'is_edited'      => $this->histories()->exists(),
            'histories_count'=> $this->histories()->count(),

            // Recursive approved child replies
            'replies'        => CommentResource::collection($this->whenLoaded('replies')),

            'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'     => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}