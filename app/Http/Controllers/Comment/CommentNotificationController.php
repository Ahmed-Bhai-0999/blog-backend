<?php

namespace App\Http\Controllers\Comment;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentNotificationResource;
use App\Services\CommentNotificationService;
use Illuminate\Http\Request;

class CommentNotificationController extends Controller
{
    public function __construct(
        protected CommentNotificationService $service
    ) {}

    /**
     * Notification List
     */
    public function index(Request $request)
    {
        $notifications = $this->service->all(
            $request->search
        );

        return CommentNotificationResource::collection($notifications);
    }

    /**
     * Unread Count
     */
    public function unreadCount()
    {
        return response()->json([
            'success' => true,
            'count' => $this->service->unreadCount()
        ]);
    }

    /**
     * Mark Single Notification Read
     */
    public function markRead($id)
    {
        $this->service->markRead($id);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.'
        ]);
    }

    /**
     * Mark All Notifications Read
     */
    public function markAllRead()
    {
        $this->service->markAllRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.'
        ]);
    }

    /**
     * Delete Notification
     */
    public function destroy($id)
    {
        $this->service->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.'
        ]);
    }
}