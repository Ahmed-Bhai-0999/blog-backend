<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\CommentNotification;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        if ($comment->status === 'Approved') {
            $this->syncCommentCount($comment);

            // Reply Notification
            if ($comment->parent_id) {
                $parent = $comment->parent;
                if ($parent &&
                    $parent->user_id &&
                    $parent->user_id != $comment->user_id) {
                    CommentNotification::create([
                        'user_id'       => $parent->user_id,
                        'comment_id'    => $comment->id,
                        'type'          => 'reply',
                        'is_read'       => false,
                        'data'          => [
                                            'message'=>($comment->user?->name ?? "Guest")
                                                ." replied to your comment."
                                        ]
                    ]);
                }
            }

        }
    }

    public function updated(Comment $comment): void
    {
        // Status transition from Pending/Rejected to Approved
        if ($comment->isDirty('status')) {
            $oldStatus = $comment->getOriginal('status');
            $newStatus = $comment->status;

            if ($oldStatus !== 'Approved' && $newStatus === 'Approved') {
                $this->syncCommentCount($comment);

                // Trigger Reply Notification for parent comment author (if authenticated user)
                if ($comment->parent_id) {
                    $parent = $comment->parent;
                    if ($parent && $parent->user_id && $parent->user_id !== $comment->user_id) {
                        CommentNotification::create([
                            'user_id'    => $parent->user_id,
                            'comment_id' => $comment->id,
                            'type'       => 'reply',
                            'is_read'    => false,
                            'data'       => [
                                                'message' => ($comment->user?->name ?? $comment->guest_name ?? 'Guest')
                                                 . " replied to your comment."
                                            ]
                        ]);
                    }
                }
            } elseif ($oldStatus === 'Approved' && $newStatus !== 'Approved') {
                $this->syncCommentCount($comment);
            }
        }
    }

    public function deleted(Comment $comment): void
    {
        if ($comment->status === 'Approved') {
            $this->syncCommentCount($comment);
        }
    }

    public function restored(Comment $comment): void
    {
        if ($comment->status === 'Approved') {
            $this->syncCommentCount($comment);
        }
    }

    private function syncCommentCount(Comment $comment): void
    {
        $post = $comment->post;

        if (!$post) {
            return;
        }

        $count = Comment::where('post_id', $post->id)
            ->whereNull('deleted_at')
            ->where('status', 'Approved')
            ->count();

        $post->update([
            'comments_count' => $count
        ]);
    }
}
