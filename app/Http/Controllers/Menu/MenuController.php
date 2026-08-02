<?php

namespace App\Http\Controllers\Menu;

use App\Enums\ActivityLogEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Menu_ItemResource;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $menus = Menu::with(['items.page','items.children','user','creator','updater'])
                        ->when($request->search,function($q) use($request){
                            $q->where('name','like',"%{$request->search}%");
                        })
                        ->when($request->status,function($q) use($request){
                            $q->where('status',$request->status);
                        })
                        ->when($request->sort == 'oldest',
                            fn($q)=>$q->oldest(),
                            fn($q)=>$q->latest()
                        )
                        ->paginate($request->per_page ?? 10);

        return MenuResource::collection($menus);
    }

    public function store(Request $request)
    {
        try{
            $data = DB::transaction(function () use ($request) {
                $request->validate([
                    'name'       => 'required|string|max:255|unique:menus,name',
                    'location'   => 'required|in:Header,Footer,Sidebar,Mobile',
                    'status'     => 'required|in:Active,Inactive',
                    'title'      => 'required|string|max:255',
                    'page_id'    => 'nullable|exists:pages,id|required_without:url',
                    'url'        => 'nullable|string|max:500|required_without:page_id',
                    'parent_id'  => 'nullable|exists:menu_items,id',
                    'sort_order' => 'nullable|integer|min:0',
                    'target'     => 'nullable|in:_self,_blank',
                    'icon'       => 'nullable|string|max:255',
                ]);

                $menu = Menu::create([
                    'name'          => $request->name,
                    'location'      => $request->location,
                    'created_by'    => Auth::id(),
                    'updated_by'    => Auth::id(),
                    'status'        => $request->status,
                    'user_id'       => Auth::id(),
                ]);
                
                $menuItem = MenuItem::create([
                    'menu_id'       => $menu->id,
                    'parent_id'     => $request->parent_id,
                    'title'         => $request->title,
                    'url'           => $request->url,
                    'page_id'       => $request->page_id,
                    'target'        => $request->target ?? '_self',
                    'sort_order'    => $request->sort_order ?? 0,
                    'icon'          => $request->icon,
                    'status'        => $request->status,
                    'user_id'       => Auth::id(),
                ]);

                ActivityLogHelper::log('Menu', ActivityLogEnum::CREATE->value,
                                    'Menu created successfully.', $menu);

                return $menu->load(['items.page','items.children.page','user','creator','updater']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Menu created successfully.',
                'menu'    => new MenuResource($data)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success'=>false,
                'message'=>$e->getMessage()
            ],500);
        }
    }

    public function edit($id)
    {
        $menu = Menu::with(['items.children','user','creator','updater'])->findOrFail($id);

        return new MenuResource($menu);
    }

    public function update(Request $request, $menuId, $itemId)
    {
        try {
            $data = DB::transaction(function () use ($request, $menuId, $itemId) {
                $request->validate([
                    'name'          => ['required','string','max:255',
                                        Rule::unique('menus', 'name')->ignore($menuId),
                                    ],
                    'location'      => 'required|in:Header,Footer,Sidebar,Mobile',
                    'status'        => 'required|in:Active,Inactive',
                    'title'         => 'required|string|max:255',
                    'url'           => 'nullable|string|max:500',
                    'page_id'       => 'nullable|exists:pages,id',
                    'parent_id'     => ['nullable','exists:menu_items,id',Rule::notIn([$itemId]),],
                    'sort_order'    => 'nullable|integer|min:0',
                    'target'        => 'nullable|in:_self,_blank',
                    'icon'          => 'nullable|string|max:255',
                ]);

                $menu = Menu::findOrFail($menuId);
                $item = MenuItem::where('menu_id', $menuId)->where('id', $itemId)->firstOrFail();
                
                if ($request->parent_id) {
                    $childIds = $item->children()->pluck('id')->toArray();
                    if (in_array($request->parent_id, $childIds)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You cannot assign a child as parent.'
                        ],422);
                    }
                }

                $menu->update([
                    'name'      => $request->name,
                    'location'  => $request->location,
                    'status'    => $request->status,
                    'updated_by'=> Auth::id(),
                    'user_id'   => Auth::id(),
                ]);

                $item->update([
                    'parent_id' => $request->parent_id,
                    'title'     => $request->title,
                    'url'       => $request->url,
                    'page_id'   => $request->page_id,
                    'sort_order'=> $request->sort_order ?? 0,
                    'target'    => $request->target ?? '_self',
                    'icon'      => $request->icon,
                    'status'    => $request->status,
                    'user_id'   => Auth::id(),
                ]);

                ActivityLogHelper::log('Menu', ActivityLogEnum::UPDATE->value,
                                    'Menu updated successfully.', $menu);

                return $menu->fresh()->load(['items.children','items.page','user','creator','updater']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Menu updated successfully.',
                'menu' => new MenuResource($data),
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);

        DB::transaction(function() use($menu){
            ActivityLogHelper::log('Menu', ActivityLogEnum::DELETE->value,
                                'Menu deleted successfully.', $menu);
            $menu->items()->delete();
            $menu->delete();
        }); 

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully.',
        ]);
    }

    public function changeStatus(Request $request,$id)
    {
        $request->validate([
            'status'=>'required|in:Active,Inactive'
        ]);

        $menu=Menu::findOrFail($id);
        $menu->update([
            'status'    => $request->status,
            'updated_by'=> Auth::id(),
            'user_id'   => Auth::id()
        ]);

        ActivityLogHelper::log('Menu', ActivityLogEnum::STATUS_CHANGE->value,
                            'Menu status updated successfully.', $menu);

        return response()->json([
            'success'   => true,
            'message'   => 'Status updated successfully.',
            'status'    => $menu->status
        ]);
    }

    public function deletedMenuList()
    {
        $menu = Menu::with('items')->onlyTrashed()->get();

        return response()->json([
            'success'   => true,
            'menu'      => $menu
        ]);
    }

    public function restore($id)
    {
        $menu = Menu::onlyTrashed()->findOrFail($id);
        ActivityLogHelper::log('Menu', ActivityLogEnum::RESTORE->value,
                            'Menu restored successfully.', $menu);
        MenuItem::onlyTrashed()->where('menu_id',$menu->id)
                    ->restore();
        $menu->restore();

        return response()->json([
            'success' => true,
            'message' => 'Menu restored successfully',
            'menu'    => $menu
        ]);
    }

    public function forceDelete($id)
    {
        $menu = Menu::onlyTrashed()->findOrFail($id);
        ActivityLogHelper::log('Menu', ActivityLogEnum::FORCE_DELETE->value,
                            'Menu permanently deleted.', $menu);
        MenuItem::onlyTrashed()->where('menu_id',$menu->id)->forceDelete();

        $menu->forceDelete();

        return response()->json([
            'success'=>true,
            'message'=>'Menu permanently deleted.'
        ]);
    }
}
