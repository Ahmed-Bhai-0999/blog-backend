<?php

namespace App\Http\Controllers\Notification;

use App\Enums\ActivityLogEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::with('user')
                        ->when($request->search, function ($q) use ($request) {
                            $q->where('title', 'like', "%{$request->search}%");
                        })
                        ->when($request->type, function ($q) use ($request) {
                            $q->where('type', $request->type);
                        })
                        ->when($request->is_read !== null, function ($q) use ($request) {
                            $q->where('is_read', $request->is_read);
                        })
                        ->when(
                            $request->sort == 'oldest',
                            fn($q) => $q->oldest(),
                            fn($q) => $q->latest()
                        )
                        ->paginate($request->per_page ?? 10);

        return NotificationResource::collection($notifications);
    }
 
    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'message'  => 'nullable|string',
            'type'     => 'required|in:Success,Info,Warning,Error',
            'is_read'  => 'nullable|boolean',
        ]);

        $notification = Notification::create([
            'title'     => $request->title,
            'message'   => $request->message,
            'type'      => $request->type,
            'is_read'   => $request->boolean('is_read'),
            'user_id'   => Auth::id(),
        ]);  

        ActivityLogHelper::log('Notification', ActivityLogEnum::CREATE->value,
                            'Notification created successfully.', $notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully.',
            'notification' => $notification
        ]);
    }

    public function edit($id)
    {
        $notification = Notification::with(['user'])->findOrFail($id);

        // return response()->json($notification);
        return new NotificationResource($notification);
    }
    
    public function update(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);
        $request->validate([
            'title'    => 'required|string|max:255',
            'message'  => 'nullable|string',
            'type'     => 'required|in:Success,Info,Warning,Error',
            'is_read'  => 'nullable|boolean',
        ]);

        $notification->update([
            'title'     => $request->title,
            'message'   => $request->message,
            'type'      => $request->type,
            'is_read'   => $request->boolean('is_read'),
            'user_id'   => Auth::id(),
        ]);       

        ActivityLogHelper::log('Notification', ActivityLogEnum::UPDATE->value,
                            'Notification updated successfully.', $notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification updated successfully.',
            'notification' => $notification
        ]);
    }

    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);

        ActivityLogHelper::log('Notification', ActivityLogEnum::DELETE->value,
                                'Notification deleted successfully.', $notification);
        $notification->delete();

        return response()->json([
            'success'=>true,
            'message'=>'Notification deleted successfully.'
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data'    => $notification
        ]);
    }

    public function markAllAsRead()
    {
        $data = Notification::where('user_id', Auth::id())
                        ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'data'    => $data
        ]);
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => Notification::where('user_id', Auth::id())
                        ->where('is_read', false)
                        ->count()
        ]);
    }

    public function clearAll()
    {
        Notification::where('user_id', Auth::id())->delete();

        return response()->json([
            'success'=>true,
            'message'=>'All notifications deleted.'
        ]);
    }

    public function markAsUnread($id)
    {
        $notification=Notification::findOrFail($id);

        $notification->update(['is_read'=>false]);

        return response()->json([
            'success'=>true,
            'message'=>'Marked as unread.'
        ]);
    }
}