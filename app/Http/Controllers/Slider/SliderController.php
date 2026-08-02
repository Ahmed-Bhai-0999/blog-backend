<?php

namespace App\Http\Controllers\Slider;

use App\Enums\ActiveInactiveEnum;
use App\Enums\ActivityLogEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\SliderResource;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SliderController extends Controller
{
    public function index(Request $request)
    {
        $sliders = Slider::with(['creator','updater','user','media'])
                        ->when($request->search,function($q) use($request){
                            $q->where('title','like',"%{$request->search}%");
                        })
                        ->when($request->status,function($q) use($request){
                            $q->where('status',$request->status);
                        })
                        ->when($request->sort == 'oldest',
                            fn($q)=>$q->oldest(),
                            fn($q)=>$q->latest()
                        )
                        ->paginate($request->per_page ?? 10);

        return SliderResource::collection($sliders);
    }

    public function store(Request $request)
    {
        $data = DB::transaction(function () use ($request) {
            $request->validate([
                'title'         => 'required|string|max:255|unique:sliders,title',
                'subtitle'      => 'nullable|string|max:255',
                'description'   => 'nullable|string',
                'button_text'   => 'nullable|string|max:100',
                'button_url'    => 'nullable|url',
                'image'         => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
                'sort_order'    => 'nullable|integer',
                'status'        => 'required|in:Active,Inactive'
            ]);

            $slider = Slider::create([
                'title'         => $request->title,
                'subtitle'      => $request->subtitle,
                'description'   => $request->description,
                'button_text'   => $request->button_text,
                'button_url'    => $request->button_url,
                'sort_order'    => $request->sort_order,
                'status'        => $request->status,
                'published_at'  => $request->status == ActiveInactiveEnum::ACTIVE->value ? now(): null,
                'created_by'    => Auth::id(),
                'updated_by'    => Auth::id(),
                'user_id'       => Auth::id(),
            ]);

            if($request->hasFile('image')){
                $slider->addMediaFromRequest('image')->toMediaCollection('image');
            }

            ActivityLogHelper::log('Slider', ActivityLogEnum::CREATE->value,
                                'Slider created successfully.',$slider);
            return $slider;
        });

        return response()->json([
            'success' => true,
            'message' => 'Slider created successfully.',
            'slider'  => new SliderResource($data->fresh()->load('creator','updater','user','media'))
        ]);
    }

    public function edit($id)
    {
        $slider = Slider::with(['creator','updater','user','media'])->findOrFail($id);

        return new SliderResource($slider);
    }

    public function update(Request $request, $id)
    {
        $data = DB::transaction(function () use ($request, $id) {
            $slider = Slider::findOrFail($id);
            $request->validate([
                'title'         =>['required', 'string', 'max:255',
                                    Rule::unique('sliders')->ignore($slider->id),
                                ],
                'subtitle'      => 'nullable|max:255',
                'description'   => 'nullable',
                'button_text'   => 'nullable|max:100',
                'button_url'    => 'nullable|url',
                'image'         =>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'sort_order'    => 'nullable|integer',
                'status'        => 'required|in:Active,Inactive'
            ]);

            $published_at = $request->status == ActiveInactiveEnum::ACTIVE->value
                            ? now() : null;

            $slider->update([
                'title'         => $request->title,
                'subtitle'      => $request->subtitle,
                'description'   => $request->description,
                'button_text'   => $request->button_text,
                'button_url'    => $request->button_url,
                'sort_order'    => $request->sort_order,
                'status'        => $request->status,
                'published_at'  => $published_at,
                'updated_by'    => Auth::id(),
                'user_id'       => Auth::id(),
            ]);
            
            if($request->hasFile('image')){
                $slider->clearMediaCollection('image');
                $slider->addMediaFromRequest('image')->toMediaCollection('image');
            }

            ActivityLogHelper::log('Slider', ActivityLogEnum::UPDATE->value,
                                'Slider updated successfully.',$slider);
            return $slider;
        });

        return response()->json([
            'success' => true,
            'message' => 'Slider updated successfully.',
            'slider'  => $data
        ]); 
    }

    public function destroy($id)
    {
        $slider = Slider::findOrFail($id);
        ActivityLogHelper::log('Slider', ActivityLogEnum::DELETE->value,
                            'Slider deleted successfully.',$slider);
        $slider->delete();

        return response()->json([
            'success' => true,
            'message' => 'Slider deleted successfully.',
        ]);
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Active,Inactive'
        ]);

        $slider = Slider::findOrFail($id);

        $slider->update([
            'status'    => $request->status,
            'updated_by'=> Auth::id(),
            'user_id'   => Auth::id()
        ]);
        ActivityLogHelper::log('Slider', ActivityLogEnum::STATUS_CHANGE->value,
                            'Slider status changed.', $slider);

        return response()->json([
            'success'   => true,
            'message'   => 'Slider updated successfully.',
            'status'    => $slider->status
        ]);
    }

    public function deletedSliderList()
    {
        $slider = Slider::with(['creator','updater','user','media'])->onlyTrashed()->get();

        return SliderResource::collection($slider);

    }

    public function restore($id)
    {
        $slider = Slider::onlyTrashed()->findOrFail($id);
        ActivityLogHelper::log('Slider', ActivityLogEnum::RESTORE->value,
                            'Slider restored successfully.', $slider);
        $slider->restore();

        return response()->json([
            'success' => true,
            'message' => 'Slider restored successfully',
            'slider'  => $slider
        ]);
    }

    public function forceDelete($id)
    {
        $slider = Slider::onlyTrashed()->findOrFail($id);
        ActivityLogHelper::log('Slider', ActivityLogEnum::FORCE_DELETE->value,
                            'Slider permanently deleted.', $slider);

        $slider->clearMediaCollection();
        $slider->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Slider permanently deleted.'
        ]);
    }
}
