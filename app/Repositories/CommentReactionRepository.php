<?php

namespace App\Repositories;

use App\Models\CommentReaction;

class CommentReactionRepository
{
    /**
     * Handle reaction (like/dislike) trigger
     */
    public function react(int $commentId, ?int $userId, ?string $guestToken, int $reactionVal): array
    {
        // Find existing reaction
        $query = CommentReaction::where('comment_id', $commentId);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('guest_token', $guestToken);
        }

        $existing = $query->first();

        if ($existing) {
            if ($existing->reaction === $reactionVal) {
                // Toggle off: Delete reaction if clicking the same button
                $existing->delete();
                return ['status' => 'removed', 'reaction' => null];
            } else {
                // Change reaction: Update value (e.g. from Like to Dislike)
                $existing->update(['reaction' => $reactionVal]);
                return ['status' => 'updated', 'reaction' => $reactionVal];
            }
        }

        // Create new reaction
        $newReaction = CommentReaction::create([
            'comment_id'    => $commentId,
            'user_id'       => $userId,
            'guest_token'   => $userId ? null : $guestToken,
            'reaction'      => $reactionVal
        ]);

        return ['status' => 'added', 'reaction' => $reactionVal];
    }
}
