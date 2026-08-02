<?php

namespace App\Http\Controllers\Tag;

use App\Enums\ActiveInactiveEnum;
use App\Enums\ActivityLogEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $tags = Tag::with(['creator','updater','user','posts'])
                        ->when($request->search, function ($query) use ($request) {
                            $query->where('name', 'like', "%{$request->search}%");
                        })
                        ->when($request->status, function ($query) use ($request) {
                            $query->where('status', $request->status);
                        })
                        ->when(
                            $request->sort == 'oldest',
                            fn($q) => $q->oldest(),
                            fn($q) => $q->latest()
                        )
                        ->paginate($request->per_page ?? 10);

        return TagResource::collection($tags);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255|unique:tags,name',
            'status' => 'required|in:Active,Inactive',
        ]);

        $tag = Tag::create([
            'name'       => $request->name,
            'status'     => $request->status,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'user_id'    => Auth::id(),
        ]);

        ActivityLogHelper::log('Tag', ActivityLogEnum::CREATE->value,
                            'Tag created successfully.', $tag);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully.',
            'tag'     => new TagResource($tag),
        ]);
    }

    public function edit($id)
    {
        $tag = Tag::with(['user','creator','updater','posts'])->findOrFail($id);

        return new TagResource($tag);
    }

    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);

        $request->validate([
            'name'      => ['required', 'string', 'max:255',
                            Rule::unique('tags')->ignore($tag->id),
                        ],
            'status' => 'required|in:Active,Inactive',
        ]);

        $tag->update([
            'name'       => $request->name,
            'status'     => $request->status,
            'updated_by' => Auth::id(),
            'user_id'    => Auth::id(),
        ]);

        ActivityLogHelper::log('Tag', ActivityLogEnum::UPDATE->value,
                            'Tag updated successfully.', $tag);

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully.',
            'tag'     => new TagResource($tag),
        ]);
    }

    public function changeStatus($id)
    {
        $tag = Tag::findOrFail($id);

        $tag->status = $tag->status == ActiveInactiveEnum::ACTIVE->value
                                    ? ActiveInactiveEnum::INACTIVE->value
                                    : ActiveInactiveEnum::ACTIVE->value;

        $tag->updated_by = Auth::id();
        $tag->user_id = Auth::id();

        $tag->save();

        ActivityLogHelper::log('Tag', ActivityLogEnum::STATUS_CHANGE->value,
                            'Tag status updated successfully.', $tag);

        return response()->json([
            'success' => true,
            'message' => 'Tag status updated successfully.',
            'status'  => $tag->status,
        ]);
    }

    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);

        ActivityLogHelper::log('Tag', ActivityLogEnum::DELETE->value,
                            'Tag deleted successfully.', $tag);
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully.',
        ]);
    }

    public function deletedTagList()
    {
        $tag = Tag::onlyTrashed()->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'tag'     => $tag
        ]);
    }

    public function restore($id)
    {
        $tag = Tag::onlyTrashed()->findOrFail($id);
        $tag->restore();

        ActivityLogHelper::log('Tag', ActivityLogEnum::RESTORE->value,
                            'Tag restored successfully.', $tag);

        return response()->json([
            'success' => true,
            'message' => 'Tag restored successfully.',
            'tag'     => new TagResource($tag),
        ]);
    }

    public function forceDelete($id)
    {
        $tag = Tag::onlyTrashed()->findOrFail($id);

        if ($tag->posts()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this tag because it is assigned to posts.'
            ], 422);
        }

        ActivityLogHelper::log('Tag', ActivityLogEnum::FORCE_DELETE->value,
                            'Tag permanently deleted.', $tag);
        $tag->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Tag permanently deleted.',
        ]);
    }
}
