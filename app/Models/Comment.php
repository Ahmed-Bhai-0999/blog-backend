<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'post_id',
        'parent_id',
        'comment',
        'guest_name',
        'guest_email',
        'status',
        'created_by',
        'updated_by',
        'user_id'
    ];  

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class,'parent_id');
    }

    public function reactions()
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function likes()
    {
        return $this->hasMany(CommentReaction::class)->where('reaction', 1);
    }

    public function dislikes()
    {
        return $this->hasMany(CommentReaction::class)->where('reaction', 0);
    }

    public function histories()
    {
        return $this->hasMany(CommentHistory::class)->orderBy('edited_at', 'desc');
    }

    public function reports()
    {
        return $this->hasMany(CommentReport::class);
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')
                    ->withTrashed()
                    ->where(function($q) {
                        $q->where('status', 'Approved')
                          ->orWhereNotNull('deleted_at');
                    })
                    ->with(['user', 'reactions', 'replies']);
    }
}
