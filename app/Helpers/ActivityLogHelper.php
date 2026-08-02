<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogHelper
{
    public static function log(string $module, string $action, string $description, $model = null): void
    {
        if (!Auth::check()) {
            return;
        }
        
        ActivityLog::create([
            'module'       => $module,
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $model ? get_class($model) : null,
            'subject_id'   => $model?->id,
            'ip_address'   => request()->ip(),
            'browser'      => request()->userAgent(),
            'platform'     => php_uname('s'),
            'user_id'      => Auth::id(),
        ]);
    }
}