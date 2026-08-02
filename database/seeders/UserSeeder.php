<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Role
        $superAdminRole = Role::firstOrCreate([
            'name' => 'Super Admin'
        ]);

        // Create User
        $user = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'phone' => null,
                'avatar' => null,
                'status' => 'Active',
                'last_login_at' => null,
                'password' => Hash::make('admin123'),
            ]
        );

        // Assign Role
        $user->assignRole($superAdminRole);
    }
}
