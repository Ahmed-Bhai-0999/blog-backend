<?php

namespace App\Services;

use App\Repositories\CommentRepository;
use App\Models\Comment;
use App\Models\CommentHistory;
use App\Models\CommentNotification;
use App\Models\User;
use App\Repositories\CommentReactionRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CommentService
{
    public function __construct(
        protected CommentReactionRepository $reactionRepository,
        protected CommentRepository $repository
    ){}

    /**
     * Store new comment or reply
     */
    public function store($request)
    {
        // 1. Spam Check: Honeypot field (e.g. 'website_url' must be empty)
        if ($request->filled('website_url')) {
            throw ValidationException::withMessages(['website_url' => 'Spam detected.']);
        }

        // 2. Spam Check: Duplicate comment detection (last 5 minutes)
        $cleanComment = $this->sanitizeComment($request->comment);
        $authorId = Auth::id();
        $guestEmail = $request->guest_email;

        $duplicateQuery = Comment::where('post_id', $request->post_id)
            ->where('comment', $cleanComment)
            ->where('created_at', '>=', now()->subMinutes(5));

        if ($authorId) {
            $duplicateQuery->where('user_id', $authorId);
        } else {
            $duplicateQuery->where('guest_email', $guestEmail);
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages(['comment' => 'Duplicate comment detected. You have already posted this.']);
        }

        // 3. Spam Check: URL restriction for guests
        if (!Auth::check()) {
            // If guests post more than 2 links, reject or flag as pending (default is Pending)
            if (preg_match_all('/https?:\/\/[^\s]+/', $cleanComment) > 1) {
                throw ValidationException::withMessages(['comment' => 'Guest posts cannot contain multiple links.']);
            }
        }

        // 4. Content Formatting & Profanity Filter
        $filteredComment = $this->filterProfanity($cleanComment);

        $data = [
            'post_id' => $request->post_id,
            'parent_id' => $request->parent_id,
            'comment' => $filteredComment,
            'status' => 'Pending', // All new comments default to Pending
        ];

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
        } else {
            $data['guest_name'] = $request->guest_name;
            $data['guest_email'] = $request->guest_email;
        }

        $comment = DB::transaction(function () use ($data) {
            $createdComment = $this->repository->create($data);

            // Clear Cache for this post
            $this->clearCache($createdComment->post_id);

            return $createdComment;
        });

        // 5. Parse mentions & dispatch notifications in background
        $this->parseMentions($comment);

        return $comment;
    }

    /**
     * Update comment with time-limit check (15 minutes) and history audit log
     */
    public function updateComment(Comment $comment, array $data, $userId)
    {
        // Check ownership
        if ($comment->user_id !== $userId) {
            throw new \Exception("Unauthorized to edit this comment.");
        }

        // Time limit check (15 minutes)
        if ($comment->created_at->diffInMinutes(now()) > 15) {
            throw new \Exception("Comments can only be edited within 15 minutes of posting.");
        }

        $oldComment = $comment->comment;
        $newComment = $this->filterProfanity($this->sanitizeComment($data['comment']));

        if ($oldComment === $newComment) {
            return $comment;
        }

        $updated = DB::transaction(function () use ($comment, $oldComment, $newComment, $userId) {
            // Log History
            CommentHistory::create([
                'comment_id' => $comment->id,
                'old_comment' => $oldComment,
                'new_comment' => $newComment,
                'edited_at' => now(),
                'edited_by' => $userId
            ]);

            $updatedComment = $this->repository->update($comment, [
                'comment' => $newComment,
                'status' => 'Pending', // Send back to pending on edit to prevent post-approval hijacking
                'updated_by' => $userId
            ]);

            // Clear Cache
            $this->clearCache($comment->post_id);

            return $updatedComment;
        });

        return $updated;
    }

    /**
     * Helper to load/cache comment trees using Redis/Cache
     */
    public function getCachedCommentsTree(int $postId, string $sort = 'newest', int $perPage = 10, ?string $cursor = null)
    {
        $cacheKey = "post_comments_tree_{$postId}_{$sort}_page_{$perPage}_cursor_" . ($cursor ?? 'root');

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($postId, $sort, $perPage) {
            return $this->repository->getCommentsTree($postId, $sort, $perPage);
        });
    }

    /**
     * Clear post cache tags/keys
     */
    public function clearCache(int $postId)
    {
        // Since different cache drivers are used, flush specific dynamic keys or all if tags are not supported
        try {
            Cache::tags(["post_comments_{$postId}"])->flush();
        } catch (\BadMethodCallException $e) {
            // Redis/Memcached support tags, fallback for files/db drivers:
            // Since we use cursor paginations and sorts, we clear cache keys matching pattern
            // For simplicity in fallback, we can use a versioned tag or clear a known list of sorts
            foreach (['newest', 'oldest', 'popular', 'trending', 'replies'] as $sort) {
                Cache::forget("post_comments_tree_{$postId}_{$sort}_page_10_cursor_root");
            }
        }
    }

    /**
     * Parse @username mentions and log notifications
     */
    protected function parseMentions(Comment $comment)
    {
        // Matches @username
        preg_match_all('/@([a-zA-Z0-9_]+)/', $comment->comment, $matches);

        if (!empty($matches[1])) {
            $usernames = array_unique($matches[1]);
            $users = User::whereIn('username', $usernames)->get();

            foreach ($users as $user) {
                // Skip notifying oneself
                if ($user->id === $comment->user_id) {
                    continue;
                }

                CommentNotification::create([
                    'user_id'    => $user->id,
                    'comment_id' => $comment->id,
                    'type'       => 'mention',
                    'is_read'    => false,
                    'data'       => [
                                    'message' => "You were mentioned in a comment by " 
                                    . ($comment->user?->name ?? $comment->guest_name ?? 'Guest')
                                ]
                ]);
            }
        }
    }

    /**
     * HTML Sanitization helper
     */
    protected function sanitizeComment(string $text): string
    {
        // Safe list of Markdown-related HTML tags
        return strip_tags($text, '<b><strong><i><em><blockquote><code><pre><a>');
    }

    /**
     * Profanity filter blacklist
     */
    protected function filterProfanity(string $text): string
    {
        $blacklist = ['spam', 'scam', 'badword1', 'badword2', 'abuse']; // Extendable list of terms
        $replace = '***';

        return str_ireplace($blacklist, $replace, $text);
    }

    public function react(Comment $comment, bool $reaction)
    {
        $guestToken = request()->header('X-Guest-Token') ?: request()->guest_token;
        $userId = Auth::id();

        return $this->reactionRepository->react(   
            $comment->id, 
            $userId, 
            $guestToken,
            $reaction);
    }

    public function history($commentId)
    {
        return $this->repository->histories($commentId);
    }
}