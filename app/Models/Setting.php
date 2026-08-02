<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'site_name',
        'site_tagline',
        'site_description',
        'site_email',
        'site_phone',
        'site_address',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
        'youtube_url',
        'copyright',
        'maintenance_mode',
        'timezone',
        'language',
        'posts_per_page',
        'allow_comments',
        'default_post_status',
        'google_analytics',
        'google_search_console',
        'user_id'
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'allow_comments' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('favicon')->singleFile();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}