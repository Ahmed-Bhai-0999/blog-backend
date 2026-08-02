<?php

namespace App\Repositories;

use App\Models\CommentNotification;

class CommentNotificationRepository
{
    /**
     * Get all notifications for logged-in user
     */
    public function all(int $userId, ?string $search = null)
    {
        return CommentNotification::with(['comment.post', 'comment.user', 'user'])
                ->where('user_id', $userId)
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('type', 'LIKE', "%{$search}%")
                        ->orWhereJsonContains('data->message', $search);
                    });
                })
                ->latest()
                ->paginate(10);
    }

    /**
     * Unread notifications
     */
    public function unread(int $userId)
    {
        return CommentNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->latest()
            ->get();
    }

    /**
     * Unread Count
     */
    public function unreadCount(int $userId): int
    {
        return CommentNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Find notification
     */
    public function find(int $id)
    {
        return CommentNotification::findOrFail($id);
    }

    /**
     * Mark one notification as read
     */
    public function markRead(CommentNotification $notification)
    {
        $notification->update([
            'is_read' => true
        ]);

        return $notification->fresh();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead(int $userId)
    {
        return CommentNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true
            ]);
    }

    /**
     * Delete notification
     */
    public function delete(CommentNotification $notification)
    {
        return $notification->delete();
    }
}