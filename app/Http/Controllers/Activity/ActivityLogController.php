<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
                        ->when($request->module,function($q) use($request){
                            $q->where('module',$request->module);
                        })
                        ->when($request->action,function($q) use($request){
                            $q->where('action',$request->action);
                        })
                        ->when($request->search,function($q) use($request){
                            $q->where('description','like',"%{$request->search}%");
                        })
                        ->when($request->sort=='oldest',
                            fn($q)=>$q->oldest(),
                            fn($q)=>$q->latest()
                        )
                        ->paginate($request->per_page ?? 10);

        return ActivityLogResource::collection($logs);
    }

    public function edit($id)
    {
        $log = ActivityLog::with(['user'])->findOrFail($id);

        return new ActivityLogResource($log);
    }

    public function destroy($id)
    {
        $log = ActivityLog::findOrFail($id);

        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'ActivityLog deleted successfully.',
        ]);
    }
}
