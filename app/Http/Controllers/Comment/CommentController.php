<?php

namespace App\Http\Controllers\Comment;

use App\Enums\ActivityLogEnum;
use App\Enums\CommentStatusEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\CommentHistory;
use App\Repositories\CommentReactionRepository;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    protected CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        
        $this->commentService = $commentService;
    }

    /**
     * Standard listing (e.g. for backend management)
     */
    public function index(Request $request)
    {
        $comments = Comment::with(['post', 'user', 'creator', 'updater', 'parent'])
                        ->when($request->search, function ($q) use ($request) {
                            $q->where('comment', 'like', "%{$request->search}%");
                        })
                        ->when($request->status, function ($q) use ($request) {
                            $q->where('status', $request->status);
                        })
                        ->when($request->post_id, function ($q) use ($request) {
                            $q->where('post_id', $request->post_id);
                        })
                        ->latest()
                        ->paginate($request->per_page ?? 10);

        return CommentResource::collection($comments);
    }

    /**
     * Fetch paginated recursive comment tree for a post
     */
    public function tree(Request $request)
    {
        $request->validate([
            'post_id'   => 'required|integer|exists:posts,id',
            'sort'      => 'nullable|string|in:newest,oldest,popular,trending,replies',
            'per_page'  => 'nullable|integer|min:1|max:100',
            'cursor'    => 'nullable|string'
        ]);

        $postId     = $request->post_id;
        $sort       = $request->sort ?? 'newest';
        $perPage    = $request->per_page ?? 10;
        $cursor     = $request->cursor;

        $paginator = $this->commentService->getCachedCommentsTree($postId, $sort, $perPage, $cursor);

        return CommentResource::collection($paginator);
    }

    /**
     * Create new comment
     */
    public function store(StoreCommentRequest $request)
    {
        $comment = $this->commentService->store($request);

        return response()->json([
            'success' => true,
            'message' => 'Your comment is awaiting moderation.',
            'data' => new CommentResource($comment)
        ]);
    }

    /**
     * Create new reply
     */
    public function reply(Request $request)
    {
        // Honeypot check
        if ($request->filled('website_url')) {
            throw ValidationException::withMessages(['website_url' => 'Spam detected.']);
        }

        $request->validate([
            'post_id'      => 'required|exists:posts,id',
            'parent_id'    => 'required|exists:comments,id',
            'comment'      => 'required|string|max:5000',
            'guest_name'   => 'nullable|string|max:255',
            'guest_email'  => 'nullable|email|max:255',
        ]);

        // Guest validation
        if (!Auth::check()) {
            $request->validate([
                'guest_name'  => 'required|string|max:255',
                'guest_email' => 'required|email|max:255',
            ]);
        }

        $comment = $this->commentService->store($request);

        return response()->json([
            'success' => true,
            'message' => 'Your reply is awaiting moderation.',
            'data'    => new CommentResource($comment),
        ]);
    }

    public function edit($id)
    {
        $comment = Comment::with(['user', 'creator', 'updater', 'post'])->findOrFail($id);
        return new CommentResource($comment);
    }

    /**
     * Update own comment (15 minutes limit validation)
     */
    public function update(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        $request->validate([
            'comment' => 'required|string|max:5000',
        ]);

        try {
            $updated = $this->commentService->updateComment($comment, $request->only('comment'), Auth::id());
            return response()->json([
                'success' => true,
                'message' => 'Comment updated successfully. Waiting for approval.',
                'comment' => new CommentResource($updated)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * React (Like/Dislike) to comment
     */
    // public function react(Request $request, $id, CommentReactionRepository $reactionRepo)
    // {
    //     $request->validate([
    //         'reaction' => 'required|integer|in:0,1'
    //     ]);

    //     $comment = Comment::findOrFail($id);
        
    //     $userId = Auth::id();
    //     $guestToken = $request->header('X-Guest-Token') ?: $request->input('guest_token');

    //     if (!$userId && !$guestToken) {
    //         return response()->json(['message' => 'Unauthenticated or missing guest token.'], 401);
    //     }

    //     $result = $reactionRepo->react($comment->id, $userId, $guestToken, $request->reaction);

    //     // Clear cache
    //     $this->commentService->clearCache($comment->post_id);

    //     return response()->json([
    //         'success' => true,
    //         'status' => $result['status'],
    //         'reaction' => $result['reaction'],
    //         'likes_count' => $comment->likes()->count(),
    //         'dislikes_count' => $comment->dislikes()->count()
    //     ]);
    // }

    public function react(Request $request, $id)
    {
        $request->validate([
            'reaction' => 'required|boolean'
        ]);

        $comment = Comment::findOrFail($id);

        $response = $this->commentService->react($comment, $request->reaction);

        // refresh model
        $comment->loadCount(['likes', 'dislikes']);

        return response()->json([
            'success' => true,
            'message' => 'Reaction Updated',
            'likes_count' => $comment->likes_count,
            'dislikes_count' => $comment->dislikes_count,
            'guest_token' => $response['guest_token'],
            'user_reaction' => $response['result']['reaction'] ?? null
        ]);
    }

    /**
     * Report comment
     */
    public function report(Request $request, $id, \App\Repositories\CommentReportRepository $reportRepo)
    {
        $request->validate([
            'reason' => 'required|in:Spam,Harassment,Abuse,Other'
        ]);

        $comment = Comment::findOrFail($id);
        
        $userId = Auth::id();
        $guestToken = $request->header('X-Guest-Token') ?: $request->input('guest_token');

        $reportRepo->report([
            'comment_id' => $comment->id,
            'reason'     => $request->reason,
            'user_id'    => $userId,
            'guest_token' => $guestToken
        ]);

        ActivityLogHelper::log('Comment', ActivityLogEnum::CREATE->value,
                            'Comment report created successfully.', $comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment reported successfully.'
        ]);
    }

    /**
     * Fetch user notifications
     */
    public function notifications(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $notifications = \App\Models\CommentNotification::with(['comment.post'])
            ->where('user_id', $userId)
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    /**
     * Mark all user notifications as read
     */
    public function readNotifications(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        \App\Models\CommentNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read.'
        ]);
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Pending,Approved,Rejected'
        ]);

        $comment = Comment::findOrFail($id);
        $comment->status = $request->status;
        $comment->updated_by = Auth::id();
        $comment->user_id = Auth::id();
        $comment->save();

        // Clear post cache
        $this->commentService->clearCache($comment->post_id);

        ActivityLogHelper::log('Comment', ActivityLogEnum::STATUS_CHANGE->value,
                            "Comment status changed to {$comment->status}.", $comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment status updated successfully.',
            'status' => $comment->status
        ]);
    }

    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        ActivityLogHelper::log('Comment', ActivityLogEnum::DELETE->value,
                            'Comment deleted successfully.', $comment);
        
        $comment->delete();
        $this->commentService->clearCache($comment->post_id);

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
        ]);
    }

    public function deletedCommentList(Request $request)
    {
        $comments = Comment::onlyTrashed()->with(['post','user'])
                    ->when($request->search, function ($q) use ($request) {
                        $q->where('comment','like',"%{$request->search}%");
                    })->latest()->paginate(10);

        return CommentResource::collection($comments);
    }

    public function restore($id)
    {
        $comment = Comment::onlyTrashed()->findOrFail($id);
        $comment->restore();
        
        $this->commentService->clearCache($comment->post_id);

        ActivityLogHelper::log('Comment', ActivityLogEnum::RESTORE->value,
                            'Comment restored successfully.', $comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment restored successfully.',
            'comment' => $comment
        ]);
    }

    public function forceDelete($id)
    {
        $comment = Comment::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($comment) {
            $this->deleteReplies($comment);

            ActivityLogHelper::log('Comment', ActivityLogEnum::FORCE_DELETE->value,
                'Comment permanently deleted.', $comment);

            $comment->forceDelete();
        });

        $this->commentService->clearCache($comment->post_id);

        return response()->json([
            'success' => true,
            'message' => 'Comment permanently deleted.'
        ]);
    }

    private function deleteReplies(Comment $comment)
    {
        foreach ($comment->replies()->withTrashed()->get() as $reply) {
            $this->deleteReplies($reply);
            $reply->forceDelete();
        }
    }

    public function history($id)
    {
        $comment = Comment::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->commentService->history($comment->id)
        ]);
    }
}
