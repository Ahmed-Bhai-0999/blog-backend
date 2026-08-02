<?php

namespace App\Http\Controllers\Setting;

use App\Enums\ActivityLogEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::with('media')->first();

        return new SettingResource($setting);
    }

    public function store(Request $request)
    {
        $data = DB::transaction(function () use ($request) {
            if (Setting::exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Settings already exist.'
                ],422);
            }

            $request->merge([
                'maintenance_mode' => $request->boolean('maintenance_mode'),
                'allow_comments'   => $request->boolean('allow_comments'),
            ]);

            $request->validate([
                'site_name'             => 'required|string|max:255',
                'site_tagline'          => 'nullable|string|max:255',
                'site_description'      => 'nullable|string',
                'site_email'            => 'nullable|email|max:255',
                'site_phone'            => 'nullable|string|max:30',
                'site_address'          => 'nullable|string',
                'facebook_url'          => 'nullable|url',
                'twitter_url'           => 'nullable|url',
                'instagram_url'         => 'nullable|url',
                'linkedin_url'          => 'nullable|url',
                'youtube_url'           => 'nullable|url',
                'copyright'             => 'nullable|string',
                'maintenance_mode'      => 'required|boolean',
                'timezone'              => 'required|string',
                'language'              => 'required|string',
                'posts_per_page'        => 'required|integer|min:1|max:100',
                'allow_comments'        => 'required|boolean',
                'default_post_status'   => 'required|in:Draft,Published,Scheduled,Archived',
                'google_analytics'      => 'nullable|string',
                'google_search_console' => 'nullable|string',
                'site_logo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'site_favicon'          => 'nullable|image|mimes:png,ico|max:1024',
            ]);

            $setting = Setting::create([
                'site_name'         => $request->site_name,
                'site_tagline'      => $request->site_tagline,
                'site_description'  => $request->site_description,
                'site_email'        => $request->site_email,
                'site_phone'        => $request->site_phone,
                'site_address'      => $request->site_address,
                'facebook_url'      => $request->facebook_url,
                'twitter_url'       => $request->twitter_url,
                'instagram_url'     => $request->instagram_url,
                'linkedin_url'      => $request->linkedin_url,
                'youtube_url'       => $request->youtube_url,
                'copyright'         => $request->copyright,
                'maintenance_mode'  => $request->maintenance_mode,
                'timezone'          => $request->timezone,
                'language'          => $request->language,
                'posts_per_page'    => $request->posts_per_page,
                'allow_comments'    => $request->allow_comments,
                'default_post_status'=> $request->default_post_status,
                'google_analytics'   => $request->google_analytics,
                'google_search_console'=> $request->google_search_console,
                'user_id'            => Auth::id(),
            ]);  

            if ($request->hasFile('site_logo')) {
                $setting->addMediaFromRequest('site_logo')->toMediaCollection('site_logo');
            }

            if ($request->hasFile('site_favicon')) {
                $setting->addMediaFromRequest('site_favicon')->toMediaCollection('site_favicon');
            }

            ActivityLogHelper::log('Setting', ActivityLogEnum::CREATE->value,
                                'Website settings created successfully.', $setting);
            return $setting;
        });
        return response()->json([
            'success' => true,
            'message' => 'Setting created successfully.',
            'setting' => new SettingResource($data)
        ]);
    }

    public function edit($id)
    {
        $setting = Setting::with(['user', 'media'])->findOrFail($id);

        // return response()->json($setting);
        return new SettingResource($setting);
    }
    
    public function update(Request $request, $id)
    {
        $data = DB::transaction(function () use ($request, $id) {
            $setting = Setting::findOrFail($id);
            $request->merge([
                'maintenance_mode' => $request->boolean('maintenance_mode'),
                'allow_comments'   => $request->boolean('allow_comments'),
            ]);

            $request->validate([
                'site_name'             => 'required|string|max:255',
                'site_tagline'          => 'nullable|string|max:255',
                'site_description'      => 'nullable|string',
                'site_email'            => 'nullable|email|max:255',
                'site_phone'            => 'nullable|string|max:30',
                'site_address'          => 'nullable|string',
                'facebook_url'          => 'nullable|url',
                'twitter_url'           => 'nullable|url',
                'instagram_url'         => 'nullable|url',
                'linkedin_url'          => 'nullable|url',
                'youtube_url'           => 'nullable|url',
                'copyright'             => 'nullable|string',
                'maintenance_mode'      => 'required|boolean',
                'timezone'              => 'required|string',
                'language'              => 'required|string',
                'posts_per_page'        => 'required|integer|min:1|max:100',
                'allow_comments'        => 'required|boolean',
                'default_post_status'   => 'required|in:Draft,Published,Scheduled,Archived',
                'google_analytics'      => 'nullable|string',
                'google_search_console' => 'nullable|string',
                'site_logo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                'site_favicon'          => 'nullable|image|mimes:png,ico|max:1024',
            ]);

            $setting->update([
                'site_name'         => $request->site_name,
                'site_tagline'      => $request->site_tagline,
                'site_description'  => $request->site_description,
                'site_email'        => $request->site_email,
                'site_phone'        => $request->site_phone,
                'site_address'      => $request->site_address,
                'facebook_url'      => $request->facebook_url,
                'twitter_url'       => $request->twitter_url,
                'instagram_url'     => $request->instagram_url,
                'linkedin_url'      => $request->linkedin_url,
                'youtube_url'       => $request->youtube_url,
                'copyright'         => $request->copyright,
                'maintenance_mode'  => $request->maintenance_mode,
                'timezone'          => $request->timezone,
                'language'          => $request->language,
                'posts_per_page'    => $request->posts_per_page,
                'allow_comments'    => $request->allow_comments,
                'default_post_status'=> $request->default_post_status,
                'google_analytics'   => $request->google_analytics,
                'google_search_console'=> $request->google_search_console,
                'user_id'            => Auth::id(),
            ]);       

            if($request->hasFile('site_logo')){
                $setting->clearMediaCollection('site_logo');
                $setting->addMediaFromRequest('site_logo')->toMediaCollection('site_logo');
            }

            if($request->hasFile('site_favicon')){
                $setting->clearMediaCollection('site_favicon');
                $setting->addMediaFromRequest('site_favicon')->toMediaCollection('site_favicon');
            } 

            ActivityLogHelper::log('Setting', ActivityLogEnum::UPDATE->value,
                                'Website settings updated successfully.', $setting);
            return $setting;
        });
        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully.',
            'setting' => new SettingResource($data)
        ]);
    }
}