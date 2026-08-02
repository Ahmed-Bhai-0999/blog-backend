<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'site_name'             => $this->site_name,
            'site_tagline'          => $this->site_tagline,
            'site_description'      => $this->site_description,
            'site_email'            => $this->site_email,
            'site_phone'            => $this->site_phone,
            'site_address'          => $this->site_address,

            'facebook_url'          => $this->facebook_url,
            'twitter_url'           => $this->twitter_url,
            'instagram_url'         => $this->instagram_url,
            'linkedin_url'          => $this->linkedin_url,
            'youtube_url'           => $this->youtube_url,
            'copyright'             => $this->copyright,
            'maintenance_mode'      => $this->maintenance_mode,
            'timezone'              => $this->timezone,
            'language'              => $this->language,
            'posts_per_page'        => $this->posts_per_page,
            'allow_comments'        => $this->allow_comments,
            'default_post_status'   => $this->default_post_status,
            'google_analytics'      => $this->google_analytics,
            'google_search_console' => $this->google_search_console,
            'site_logo'             => $this->getFirstMediaUrl('site_logo'),
            'site_favicon'          => $this->getFirstMediaUrl('site_favicon'),
            'creator'               => $this->whenLoaded('creator'),
            'updater'               => $this->whenLoaded('updater'),
            'user'                  => $this->whenLoaded('user'),
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
