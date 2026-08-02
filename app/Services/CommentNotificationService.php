<?php

namespace App\Services;

use App\Repositories\CommentNotificationRepository;
use Illuminate\Support\Facades\Auth;

class CommentNotificationService
{
    public function __construct(
        protected CommentNotificationRepository $repository
    ){}

    /**
     * Notification List
     */
    public function all(?string $search = null)
    {
        return $this->repository->all(Auth::id(), $search);
    }

    /**
     * Unread Count
     */
    public function unreadCount()
    {
        return $this->repository->unreadCount(Auth::id());
    }

    /**
     * Mark Single Notification Read
     */
    public function markRead(int $id)
    {
        $notification = $this->repository->find($id);

        if ($notification->user_id != Auth::id()) {
            abort(403, 'Unauthorized.');
        }

        return $this->repository->markRead($notification);
    }

    /**
     * Mark All Notifications Read
     */
    public function markAllRead()
    {
        return $this->repository->markAllRead(Auth::id());
    }

    /**
     * Delete Notification
     */
    public function delete(int $id)
    {
        $notification = $this->repository->find($id);

        if ($notification->user_id != Auth::id()) {
            abort(403, 'Unauthorized.');
        }

        return $this->repository->delete($notification);
    }
}