<?php

namespace App\Http\Controllers\Category;

use App\Enums\ActiveInactiveEnum;
use App\Enums\ActivityLogEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::with(['user','creator','updater'])
                        ->when($request->search,function($q) use($request){
                            $q->where('name','like',"%{$request->search}%");
                        })
                        ->when($request->status,function($q) use($request){
                            $q->where('status',$request->status);
                        })
                        ->when($request->sort=='oldest',
                            fn($q)=>$q->oldest(),
                            fn($q)=>$q->latest()
                        )
                        ->paginate($request->per_page ?? 10);

        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $data = DB::transaction(function () use ($request) {

            $request->validate([
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string',
                'status'      => 'required|in:Active,Inactive',
            ]);

            $category = Category::create([
                'name'        => $request->name,
                'description' => $request->description,
                'status'      => $request->status,
                'created_by'  => Auth::id(),
                'updated_by'  => Auth::id(),
                'user_id'     => Auth::id(),
            ]);

            ActivityLogHelper::log('Category', ActivityLogEnum::CREATE->value,
                                'Category created successfully.', $category);

            return $category->load(['creator', 'updater']);
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Category created successfully.',
            'category' => new CategoryResource($data)
        ], 201);
    }

    public function edit($id)
    {
        $category = Category::with(['user', 'creator', 'updater'])->findOrFail($id);

        return new CategoryResource($category);
    }

    public function update(Request $request, $id)
    {
        $data = DB::transaction(function () use ($request, $id) {
            $category = Category::findOrFail($id);
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('categories')->ignore($category->id)
                ],
                'description' => 'nullable|string',
                'status' => 'required|in:Active,Inactive',
            ]);

            $category->update([
                'name'        => $request->name,
                'description' => $request->description,
                'status'      => $request->status,
                'updated_by'  => Auth::id(),
                'user_id'     => Auth::id(),
            ]);       

            ActivityLogHelper::log('Category', ActivityLogEnum::UPDATE->value, 
                                'Category updated successfully.', $category);

            return $category;
        });

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'category' => new CategoryResource($data)
        ]);
    }

    public function changeStatus($id)
    {
        // $data = DB::transaction(function () use ($id) {
            $category = Category::findOrFail($id);

            $category->status = $category->status == ActiveInactiveEnum::ACTIVE->value
                                ? ActiveInactiveEnum::INACTIVE->value
                                : ActiveInactiveEnum::ACTIVE->value;
            $category->updated_by = Auth::id();
            $category->user_id = Auth::id();

            $category->save();

            ActivityLogHelper::log('Category', ActivityLogEnum::STATUS_CHANGE->value,
                                "Category status changed to {$category->status}.", $category);
            return $category;
        // });

        return response()->json([
            'success' => true,
            'message' => 'Category status updated successfully',
            'status'  => $category->status
        ]);
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $category = Category::findOrFail($id);

            ActivityLogHelper::log('Category', ActivityLogEnum::DELETE->value, 
                                'Category deleted successfully.', $category);
            $category->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function deletedCategoryList()
    {
        $categories = Category::onlyTrashed()
                    ->latest()
                    ->paginate(10);

        return CategoryResource::collection($categories);
    }

    public function restore($id)
    {
        $data = DB::transaction(function () use ($id) {
            $category = Category::onlyTrashed()->findOrFail($id);

            ActivityLogHelper::log('Category', ActivityLogEnum::RESTORE->value, 
                                'Category restored successfully.', $category);
            $category->restore();
        });

        return response()->json([
            'success' => true,
            'message' => 'Category restored successfully',
            'category' => $data
        ]);
    }

    public function forceDelete($id)
    {
       $category = Category::onlyTrashed()->findOrFail($id);

        if ($category->posts()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this category because it is assigned to posts.'
            ], 422);
        }
        
        DB::transaction(function () use ($category) {
            ActivityLogHelper::log('Category', ActivityLogEnum::FORCE_DELETE->value,
                                'Category permanently deleted.', $category);

            $category->forceDelete();
            
            return $category;
        });

        return response()->json([
            'success' => true,
            'message' => 'Category permanently deleted.'
        ]);
    }
}