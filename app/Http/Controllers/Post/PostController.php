<?php

namespace App\Http\Controllers\Post;

use App\Enums\ActivityLogEnum;
use App\Enums\PostStatusEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with(['category','tags','user','creator','updater','media'])
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $search = $request->search;
                        $query->where(function ($q) use ($search) {
                            $q->where('title', 'LIKE', "%{$search}%")
                            ->orWhere('excerpt', 'LIKE', "%{$search}%")
                            ->orWhere('content', 'LIKE', "%{$search}%");
                        });
                    })
                    ->when($request->category_id, function ($query) use ($request) {
                        $query->where('category_id', $request->category_id);
                    })
                    ->when($request->category_slug, function ($query) use ($request) {
                        $query->whereHas('category', function ($q) use ($request) {
                            $q->where('slug', $request->category_slug); });
                    })
                    ->when($request->tag_id, function ($query) use ($request) {
                        $query->whereHas('tags', function ($q) use ($request) {
                            $q->where('tags.id', $request->tag_id); });
                    })
                    ->when($request->filled('tag_slug'), function ($query) use ($request) {
                        $query->whereHas('tags', function ($q) use ($request) {
                            $q->where('slug', $request->tag_slug); });
                    })
                    ->when($request->status, function ($query) use ($request) {
                        $query->where('status', $request->status);
                    })
                    ->when($request->author_id, function ($query) use ($request) {
                        $query->where('user_id', $request->author_id);
                    });

                // Sorting
                switch ($request->sort) {
                    case 'oldest':
                        $posts->oldest();
                        break;
                        
                    case 'views':
                        $posts->orderByDesc('views');
                        break;

                    case 'az':
                        $posts->orderBy('title', 'asc');
                        break;

                    case 'za':
                        $posts->orderBy('title', 'desc');
                        break;

                    case 'latest':
                    default:
                        $posts->latest();
                        break;
                }

        $posts = $posts->paginate($request->per_page ?? 10);

        return PostResource::collection($posts);
    }

    public function store(Request $request)
    {
        try{
            $postCreate = DB::transaction(function () use ($request) {
                $request->validate([
                    'category_id'      => 'required|exists:categories,id',
                    'title'            => 'required|string|max:255|unique:posts,title',
                    'excerpt'          => 'nullable|string',
                    'content'          => 'required|string',
                    'status'           => 'required|in:Draft,Published,Scheduled,Archived',
                    'published_at'     => 'nullable|date',
                    'featured_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                    'meta_title'       => 'nullable|string|max:255',
                    'meta_description' => 'nullable|string',
                    'meta_keywords'    => 'nullable|string',
                    'tags'             => 'nullable|array',
                    'tags.*'           => 'exists:tags,id',
                ]);

                $published_at = $request->status == PostStatusEnum::PUBLISHED->value
                                    ? ($request->published_at ?? now())
                                    : $request->published_at;

                $post = Post::create([
                    'category_id'      => $request->category_id,
                    'title'            => $request->title,
                    'excerpt'          => $request->excerpt,
                    'content'          => $request->content,
                    'status'           => $request->status,
                    'published_at'     => $published_at,
                    'meta_title'       => $request->meta_title,
                    'meta_description' => $request->meta_description,
                    'meta_keywords'    => $request->meta_keywords,
                    'views'            => 0,
                    'created_by'       => Auth::id(),
                    'updated_by'       => Auth::id(),
                    'user_id'          => Auth::id(),
                ]);

                if ($request->hasFile('featured_image')) {
                    $post->addMediaFromRequest('featured_image')
                         ->usingFileName('post-'.$post->id.'.'.$request->file('featured_image')->extension()
                         )->toMediaCollection('featured_image');   
                }

                // Attach Tags
                if ($request->filled('tags')) {
                    $post->tags()->detach();
                    foreach ($request->tags as $tag) {
                        $post->tags()->attach($tag,[
                            'user_id'=>Auth::id()
                        ]);
                    }
                }

                ActivityLogHelper::log('Post', ActivityLogEnum::CREATE->value,
                                    'Post created successfully.', $post);
                return $post;
            });

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully.',
                'post'    => new PostResource($postCreate->load(
                            ['category','tags','creator','updater','user','media'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message'   => $e->getMessage(),
                'line'      => $e->getLine(),
                'file'      => $e->getFile(),
            ],500);
        }
    }

    public function edit($id)
    {
        $post = Post::with(['category','tags','user','creator','updater','media'])->findOrFail($id);

        return new PostResource($post);
    }

    public function update(Request $request, $id)
    {
        try{
            $postUpdate = DB::transaction(function () use ($request, $id) {
                $post = Post::findOrFail($id);
                $request->validate([
                    'category_id' => 'required|exists:categories,id',
                    'title' => ['required','string','max:255',
                        Rule::unique('posts')->ignore($post->id),
                    ],
                    'excerpt'       => 'nullable|string',
                    'content'       => 'required|string',
                    'status'        => 'required|in:Draft,Published,Scheduled,Archived',
                    'published_at'  => 'nullable|date',
                    'featured_image'=> 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                    'meta_title'    => 'nullable|string|max:255',
                    'meta_description' => 'nullable|string',
                    'meta_keywords' => 'nullable|string',
                    'tags'          => 'nullable|array',
                    'tags.*'        => 'exists:tags,id',
                ]);

                $published_at = $request->status == PostStatusEnum::PUBLISHED->value
                                    ? ($request->published_at ?? now())
                                    : $request->published_at;

                $post->update([
                    'category_id'      => $request->category_id,
                    'title'            => $request->title,
                    'excerpt'          => $request->excerpt,
                    'content'          => $request->content,
                    'status'           => $request->status,
                    'published_at'     => $published_at,
                    'meta_title'       => $request->meta_title,
                    'meta_description' => $request->meta_description,
                    'meta_keywords'    => $request->meta_keywords,
                    'updated_by'       => Auth::id(),
                    'user_id'          => Auth::id(),
                ]);

                if ($request->hasFile('featured_image')) {
                    $post->clearMediaCollection('featured_image');
                    $post->addMediaFromRequest('featured_image')->toMediaCollection('featured_image');
                }

                // Sync Tags
                 $post->tags()->detach();

                if($request->filled('tags')){
                    foreach($request->tags as $tag){
                        $post->tags()->attach($tag,['user_id'=>Auth::id()]);
                    }
                }

                ActivityLogHelper::log('Post', ActivityLogEnum::UPDATE->value,
                                    'Post updated successfully.',$post);
                return $post;
            });

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully.',
                'data' => new PostResource($postUpdate),
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Draft,Published,Scheduled,Archived',
        ]);

        $post = Post::findOrFail($id);

        $post->status     = $request->status;
        $post->updated_by = Auth::id();
        $post->user_id    = Auth::id();

        if ($request->status === PostStatusEnum::PUBLISHED->value && !$post->published_at) {
            $post->published_at = now();
        }
        ActivityLogHelper::log('Post', ActivityLogEnum::STATUS_CHANGE->value,
                            'Post status updated successfully.', $post);
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post status updated successfully.',
            'status' => $post->status,
        ]);
    }

    public function incrementView($slug)
    {
        $post = Post::whereSlug($slug)->firstOrFail();
        $post->increment('views');

        return new PostResource(
            $post->load(['category','tags','user','media'])
        );
    }

    public function relatedPosts($categoryId, $postId)
    {
        $posts = Post::with(['category','tags','user','media'])
                    ->where('category_id', $categoryId)
                    ->where('id', '!=', $postId)
                    ->where('status', 'Published')
                    ->latest()
                    ->take(3)
                    ->get();

        return PostResource::collection($posts);
    }

    public function postNavigation($id)
    {
        $previous = Post::where('id', '<', $id)
                    ->where('status', 'Published')
                    ->latest('id')
                    ->first();

        $next = Post::where('id', '>', $id)
                    ->where('status', 'Published')
                    ->oldest('id')
                    ->first();

        return response()->json([
            'previous' => $previous ? new PostResource($previous->load(['category','user','media'])) : null,
            'next'     => $next ? new PostResource($next->load(['category','user','media'])) : null,
        ]);
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        ActivityLogHelper::log('Post', ActivityLogEnum::DELETE->value,
                            'Post deleted successfully.', $post);
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }

    public function deletedPostList(Request $request)
    {
        $posts = Post::onlyTrashed()->latest()->paginate(10);

        return PostResource::collection($posts);
    }

    public function restore($id)
    {
        $post = Post::onlyTrashed()->findOrFail($id);
        ActivityLogHelper::log('Post', ActivityLogEnum::RESTORE->value,
                            'Post restored successfully.', $post);
        $post->restore();

        return response()->json([
            'success' => true,
            'message' => 'Post restored successfully',
            'post'    => $post
        ]);
    }

    public function forceDelete($id)
    {
        DB::transaction(function () use ($id) {
            $post = Post::onlyTrashed()->findOrFail($id);
            $post->comments()->forceDelete();
            $post->tags()->detach();
            $post->clearMediaCollection('featured_image');

            ActivityLogHelper::log('Post', ActivityLogEnum::FORCE_DELETE->value,
                'Post permanently deleted.', $post);

            $post->forceDelete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Post permanently deleted.'
        ]);
    }
}
