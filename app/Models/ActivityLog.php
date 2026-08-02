<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'module',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'ip_address',
        'browser',
        'platform',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
