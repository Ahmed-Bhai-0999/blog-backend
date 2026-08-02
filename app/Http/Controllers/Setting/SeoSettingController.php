<?php

namespace App\Http\Controllers\Setting;

use App\Enums\ActivityLogEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\SeoSettingResource;
use App\Models\SeoSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeoSettingController extends Controller
{
    public function index()
    {
        $setting = SeoSetting::with('user')->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'SEO Setting not found.'
            ], 404);
        }

        return new SeoSettingResource($setting);
    }

    public function store(Request $request)
    {
        if (SeoSetting::exists()) {
            return response()->json([
                'success'=>false,
                'message'=>'SEO Settings already exist.'
            ],422);
        }

        $request->validate([
            'meta_title'            => 'required|string|max:255',
            'meta_description'      => 'nullable|string',
            'meta_keywords'         => 'nullable|string',
            'canonical_url'         => 'nullable|url',
            'robots'                => 'nullable|string|max:255',
            'og_title'              => 'nullable|string|max:255',
            'og_description'        => 'nullable|string',
            'twitter_title'         => 'nullable|string|max:255',
            'twitter_description'   => 'nullable|string',
            'schema_markup'         => 'nullable|string',
            'og_image'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'twitter_image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $setting = SeoSetting::create([
            'meta_title'            => $request->meta_title,
            'meta_description'      => $request->meta_description,
            'meta_keywords'         => $request->meta_keywords,
            'canonical_url'         => $request->canonical_url,
            'robots'                => $request->robots,
            'og_title'              => $request->og_title,
            'og_description'        => $request->og_description,
            'twitter_title'         => $request->twitter_title,
            'twitter_description'   => $request->twitter_description,
            'schema_markup'         => $request->schema_markup,
            'user_id'               => Auth::id(),
        ]);  

        if ($request->hasFile('og_image')) {
                $setting->addMediaFromRequest('og_image')->toMediaCollection('og_image');
            }

        if ($request->hasFile('twitter_image')) {
            $setting->addMediaFromRequest('twitter_image')->toMediaCollection('twitter_image');
        }

        ActivityLogHelper::log('SEO Setting', ActivityLogEnum::CREATE->value,
                            'SEO Setting created successfully.', $setting);

        return response()->json([
            'success' => true,
            'message' => 'SEO Setting created successfully.',
            'setting' => new SeoSettingResource($setting->load('user'))
        ]);
    }

    public function edit($id)
    {
        $setting = SeoSetting::with(['user'])->findOrFail($id);

        // return response()->json($setting);
        return new SeoSettingResource($setting);
    }
    
    public function update(Request $request, $id)
    {
        $setting = SeoSetting::findOrFail($id);
        $request->validate([
            'meta_title'            => 'required|string|max:255',
            'meta_description'      => 'nullable|string',
            'meta_keywords'         => 'nullable|string',
            'canonical_url'         => 'nullable|url',
            'robots'                => 'nullable|string|max:255',
            'og_title'              => 'nullable|string|max:255',
            'og_description'        => 'nullable|string',
            'twitter_title'         => 'nullable|string|max:255',
            'twitter_description'   => 'nullable|string',
            'schema_markup'         => 'nullable|string',
            'og_image'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'twitter_image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $setting->update([
            'meta_title'            => $request->meta_title,
            'meta_description'      => $request->meta_description,
            'meta_keywords'         => $request->meta_keywords,
            'canonical_url'         => $request->canonical_url,
            'robots'                => $request->robots,
            'og_title'              => $request->og_title,
            'og_description'        => $request->og_description,
            'twitter_title'         => $request->twitter_title,
            'twitter_description'   => $request->twitter_description,
            'schema_markup'         => $request->schema_markup,
            'user_id'               => Auth::id(),
        ]); 
        
        if($request->hasFile('og_image')){
            $setting->clearMediaCollection('og_image');
            $setting->addMediaFromRequest('og_image')->toMediaCollection('og_image');
        }

        if($request->hasFile('twitter_image')){
            $setting->clearMediaCollection('twitter_image');
            $setting->addMediaFromRequest('twitter_image')->toMediaCollection('twitter_image');
        } 

        ActivityLogHelper::log('SEO Setting', ActivityLogEnum::UPDATE->value,
                            'SEO Setting updated successfully.', $setting);

        return response()->json([
            'success' => true,
            'message' => 'SEO Setting updated successfully.',
            'setting' => new SeoSettingResource($setting->load('user'))
        ]);
    }

    public function destroy($id)
    {
        $setting = SeoSetting::findOrFail($id);

        ActivityLogHelper::log('SEO Setting', ActivityLogEnum::DELETE->value,
                                'SEO Setting deleted successfully.', $setting);
        $setting->delete();

        return response()->json([
            'success'=>true,
            'message'=>'SEO Setting deleted successfully.'
        ]);
    }
}
