<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'comment_id',
        'old_comment',
        'new_comment',
        'edited_at',
        'edited_by'
    ];

    protected $casts = [
        'edited_at' => 'datetime'
    ];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
