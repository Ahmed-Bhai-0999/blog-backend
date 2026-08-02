<?php

namespace App\Http\Controllers\Page;

use App\Enums\ActivityLogEnum;
use App\Enums\PostStatusEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $pages = Page::with(['creator','updater','user','media'])
                    ->when($request->slug,function($q) use($request){
                        $q->where('slug',$request->slug);
                    })
                    ->when($request->search,function($q) use($request){
                        $q->where('title','like',"%{$request->search}%");
                    })
                    ->when($request->status,function($q) use($request){
                        $q->where('status',$request->status);
                    })
                    ->latest()
                    ->paginate($request->per_page ?? 10);

        return PageResource::collection($pages);
    }

    public function store(Request $request)
    {
        $data = DB::transaction(function () use ($request) {
            $request->validate([
                'title'             => 'required|string|max:255|unique:pages,title',
                'content'           => 'required|string',
                'template'          => 'required|in:Default,Full-Width,Contact,Landing',
                'status'            => 'required|in:Draft,Published,Scheduled,Archived',
                'published_at'      => 'nullable|date',
                'meta_title'        => 'nullable|string|max:255',
                'meta_description'  => 'nullable|string',
                'meta_keywords'     => 'nullable|string',
                'image'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            $page = Page::create([
                'title'             => $request->title,
                'content'           => $request->content,
                'template'          => $request->template,
                'meta_title'        => $request->meta_title,
                'meta_description'  => $request->meta_description,
                'meta_keywords'     => $request->meta_keywords,
                'published_at'      => $request->published_at,
                'status'            => $request->status,
                'created_by'        => Auth::id(),
                'updated_by'        => Auth::id(),
                'user_id'           => Auth::id(),
            ]);  

            if($request->hasFile('image')){
                $page->addMediaFromRequest('image')->toMediaCollection('featured_image');
            }  

            ActivityLogHelper::log('Page', ActivityLogEnum::CREATE->value,
                                'Page created successfully.', $page);
            return $page;
        });

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully.',
            'page'    => new PageResource($data)
        ]);
    }

    public function edit($id)
    {
        $page = Page::with(['user', 'creator', 'updater'])->findOrFail($id);

        // return response()->json($page);
        return new PageResource($page);
    }

    public function update(Request $request, $id)
    {
        $data = DB::transaction(function () use ($request, $id){
            $page = Page::findOrFail($id);
            $request->validate([
                    'title'     => ['required', 'string', 'max:255',
                                    Rule::unique('pages')->ignore($id)
                                ],
                'template'      => 'required|in:Default,Full-Width,Contact,Landing',
                'published_at'  => 'nullable|date',
                'meta_title'    => 'nullable|max:255',
                'meta_description'=> 'nullable|string',
                'meta_keywords' => 'nullable|string',
                'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'status'        => 'required|in:Draft,Published,Scheduled,Archived',
            ]);

            $page->update([
                'title'             => $request->title,
                'content'           => $request->content,
                'template'          => $request->template,
                'meta_title'        => $request->meta_title,
                'meta_description'  => $request->meta_description,
                'meta_keywords'     => $request->meta_keywords,
                'published_at'      => $request->published_at,
                'status'            => $request->status,
                'updated_by'        => Auth::id(),
                'user_id'           => Auth::id(),
            ]);       

            if($request->hasFile('image')){
                $page->clearMediaCollection('featured_image');
                $page->addMediaFromRequest('image')->toMediaCollection('featured_image');
            }

            ActivityLogHelper::log('Page', ActivityLogEnum::UPDATE->value,
                                'Page updated successfully.', $page);

            return $page->fresh()->load(['creator', 'updater', 'user', 'media']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully.',
            'page'    => new PageResource($data),
        ]);
    }

    public function changeStatus(Request $request,$id)
    {
        $request->validate([
            'status'=>'required|in:Draft,Published,Scheduled,Archived'
        ]);
        $page = Page::findOrFail($id);
        $page->status       = $request->status;
        $page->updated_by   = Auth::id();
        $page->user_id      = Auth::id();

        if($request->status=='Published' && !$page->published_at){
            $page->published_at=now();
        }
        $page->save();

        ActivityLogHelper::log('Page', ActivityLogEnum::STATUS_CHANGE->value,
                            'Page status updated successfully.', $page);

        return response()->json([
            'success'=>true,
            'message'=>'Status updated successfully.',
            'status' =>$page->status
        ]);

    }

    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        ActivityLogHelper::log('Page', ActivityLogEnum::DELETE->value,
                            'Page deleted successfully.', $page);

        $page->clearMediaCollection('featured_image');
        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully.',
        ]);
    }

    public function deletedPageList()
    {
        $page = Page::onlyTrashed()->latest()
                ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'page' => $page
        ]);
    }

    public function restore($id)
    {
        $page = Page::onlyTrashed()->findOrFail($id);
        $page->restore();

        ActivityLogHelper::log('Page', ActivityLogEnum::RESTORE->value,
                            'Page restored successfully.', $page);

        return response()->json([
            'success' => true,
            'message' => 'Page restored successfully',
            'page'    => $page
        ]);
    }

    public function forceDelete($id)
    {
        $page = Page::onlyTrashed()->findOrFail($id);
        ActivityLogHelper::log('Page', ActivityLogEnum::FORCE_DELETE->value,
                            'Page permanently deleted.', $page);

        $page->clearMediaCollection();
        $page->forceDelete();

        return response()->json([
            'success'=>true,
            'message'=>'Page permanently deleted.'
        ]);
    }
}
