<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\CommentHistory;
use App\Models\CommentReaction;

class CommentRepository
{
    public function create(array $data)
    {
        return Comment::create($data);
    }

    public function find($id)
    {
        return Comment::findOrFail($id);
    }

    public function update(Comment $comment, array $data)
    {
        $comment->update($data);
        return $comment->fresh();
    }

    public function delete(Comment $comment)
    {
        return $comment->delete();
    }

    /**
     * Get cursor-paginated approved comments (top-level only) for a post
     */
    public function getCommentsTree(int $postId, string $sort = 'newest', int $perPage = 10)
    {
        $query = Comment::withTrashed()
                    ->with(['user', 'reactions', 'replies'])
                    ->withCount(['likes', 'replies'])
                    ->where('post_id', $postId)
                    ->whereNull('parent_id')
                    ->where(function($q) {
                        $q->where('status', 'Approved')
                        ->orWhereNotNull('deleted_at');
                    });

        // Apply Sorting
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'popular':
                $query->orderBy('likes_count', 'desc')->latest();
                break;
            case 'replies':
                $query->orderBy('replies_count', 'desc')->latest();
                break;
            case 'trending':
                // Trending formula: likes * 2 + replies * 3 + weight of recency
                $query->orderByRaw('(likes_count * 2 + replies_count * 3) DESC')->latest();
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        return $query->cursorPaginate($perPage);
    }

    /**
     * Fetch approved flat lists or admin logs
     */
    public function approved($postId)
    {
        return Comment::with(['user', 'creator', 'replies.user'])
            ->wherePostId($postId)
            ->whereStatus('Approved')
            ->whereNull('parent_id')
            ->latest()
            ->get();
    }

    public function react($commentId, $userId, $guestToken, $reaction)
    {
        $query = CommentReaction::where("comment_id", $commentId);

        if ($userId) {
            $query->where("user_id", $userId);
        } else {
            $query->where("guest_token", $guestToken);
        }

        $record = $query->first();

        if ($record) {
            if ($record->reaction == $reaction) {
                $record->delete();
                return [
                    "result" => [
                        "reaction" => null
                    ],
                    "guest_token" => $guestToken
                ];
            }

            $record->update(["reaction" => $reaction]);

            return [
                "result" => $record,
                "guest_token" => $guestToken
            ];
        }

        if (!$userId && !$guestToken) {
            $guestToken = (string) \Illuminate\Support\Str::uuid();
        }

        $record = CommentReaction::create([
            "comment_id"  => $commentId,
            "user_id"     => $userId,
            "guest_token" => $guestToken,
            "reaction"    => $reaction
        ]);

        return [
            "result" => $record,
            "guest_token" => $guestToken
        ];
    }

    public function histories($commentId)
    {
        $history = CommentHistory::with('editor')
                    ->where('comment_id',$commentId)
                    ->latest('edited_at')
                    ->get();

        return $history;
    }
}