<?php

namespace App\Http\Controllers;

use App\Enums\ActiveInactiveEnum;
use App\Enums\ActivityLogEnum;
use App\Helpers\ActivityLogHelper;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')
                    ->when($request->search, function ($q) use ($request) {
                        $q->where(function ($query) use ($request) {
                            $query->where('name', 'like', "%{$request->search}%")
                                ->orWhere('email', 'like', "%{$request->search}%")
                                ->orWhere('username', 'like', "%{$request->search}%");
                        });
                    })
                    ->when($request->status, function ($q) use ($request) {
                        $q->where('status', $request->status);
                    })
                    ->latest()
                    ->paginate($request->per_page ?? 10);

        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        $data = DB::transaction(function () use ($request) {
            $request->validate([
                'name'      => 'required|string|max:255',
                'username'  => 'nullable|string|max:255|unique:users,username',
                'email'     => 'required|email|unique:users,email',
                'phone'     => 'nullable|string|max:20',
                'password'  => 'required|min:8',
                'role'      => 'required|exists:roles,name',
            ]);

            $user = User::create([
                'name'      => $request->name,
                'username'  => $request->username,
                'email'     => $request->email,
                'phone'     => $request->phone,
                'password'  => bcrypt($request->password),
                'status'    => ActiveInactiveEnum::ACTIVE->value,
            ]);

            $user->assignRole($request->role);
            
            if ($request->hasFile('avatar')) {
                $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            }

            ActivityLogHelper::log('User', ActivityLogEnum::CREATE->value, 
                                'User created successfully.', $user);

            return $user->load('roles');
        });

        return response()->json([
            'success' => true,
            'message' => 'User created Successfully',
            'user'    => new UserResource($data)
        ], 201);
    }

    public function editUser($id)
    {
        return new UserResource(User::with('roles')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $data = DB::transaction(function () use ($request,$id){

            $user = User::findOrFail($id);

            $request->validate([
                'name'      => 'required|string|max:255',
                'username'  => ['required',
                                Rule::unique('users')->ignore($user->id)
                            ],
                'email'     => ['required','email',
                                Rule::unique('users')->ignore($user->id)
                            ],
                'phone'     => 'nullable|string|max:20',
                'password'  => 'nullable|min:8',
                'role'      => 'required|exists:roles,name',
                'status'    => 'required|in:Active,Inactive'
            ]);

            $user->update([
                'name'=>$request->name,
                'username'=>$request->username,
                'email'=>$request->email,
                'phone'=>$request->phone,
                'status'=>$request->status,
            ]);

            if($request->filled('password')){
                $user->password = Hash::make($request->password);
                $user->save();
            }

            $user->syncRoles([$request->role]);
                if ($request->hasFile('avatar')) {
                    $user->clearMediaCollection('avatar');
                    $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
                }

            ActivityLogHelper::log('User', ActivityLogEnum::UPDATE->value,
                                'User updated successfully.', $user);

            return $user->load('roles');
        });

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user'    => $data,
        ]);

    }

    public function changeStatus($id)
    {
        $data = DB::transaction(function () use ($id){

            $user = User::findOrFail($id);
            $user->status = $user->status == ActiveInactiveEnum::ACTIVE->value
                                ? ActiveInactiveEnum::INACTIVE->value
                                : ActiveInactiveEnum::ACTIVE->value;
            $user->save();

            ActivityLogHelper::log('User', ActivityLogEnum::STATUS_CHANGE->value,
                                    "User status changed to {$user->status}.", $user);
            return $user;
        });

        return response()->json([
            'success'=>true,
            'message'=>'User status updated successfully.',
            'status'=>$data->status
        ]);
    }

    public function destroy($id)
    {
        $data = DB::transaction(function () use ($id){

            $user = User::findOrFail($id);

            ActivityLogHelper::log('User', ActivityLogEnum::DELETE->value,
                                    'User deleted successfully.', $user);
            $user->delete();

            return $user;
        });

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
            'user'    => $data,
        ]);
    }

    public function deletedUsersList(Request $request)
    {
        $users = User::onlyTrashed()
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%")
                        ->orWhere('username', 'like', "%{$request->search}%");
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        return UserResource::collection($users);
    }

    public function restore($id)
    {
        $data = DB::transaction(function () use ($id){
            $user = User::onlyTrashed()->findOrFail($id);
            $user->restore();

            ActivityLogHelper::log('User', ActivityLogEnum::RESTORE->value,
                                    'User restored successfully.', $user);
            return $user;
        });

        return response()->json([
            'success' => true,
            'message' => 'User restored successfully',
            'user'    => $data
        ]);
    }

    public function forceDelete($id)
    {
        DB::transaction(function () use ($id){
            $user = User::onlyTrashed()->findOrFail($id);

            ActivityLogHelper::log('User', ActivityLogEnum::FORCE_DELETE->value,
                                'User permanently deleted.', $user);
            $user->forceDelete();
        
            return $user;
        });

        return response()->json([
            'success'=>true,
            'message'=>'User permanently deleted.'
        ]);
    }
}
