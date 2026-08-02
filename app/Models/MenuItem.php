<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'page_id',
        'sort_order',
        'target',
        'icon',
        'status',
        'user_id'
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function parent()
    {
        return $this->belongsTo(MenuItem::class,'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MenuItem::class,'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}