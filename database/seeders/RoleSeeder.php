<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Roles
        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web'
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web'
        ]);

        $author = Role::firstOrCreate([
            'name' => 'Author',
            'guard_name' => 'web'
        ]);

        $user = Role::firstOrCreate([
            'name' => 'User',
            'guard_name' => 'web'
        ]);

        // Permissions
        $permissions = Permission::all();

        // Super Admin => All Permissions
        $superAdmin->syncPermissions($permissions);

        // Admin Permissions
        $admin->syncPermissions([
            'post view',
            'post create',
            'post edit',
            'post delete',

            'category view',
            'category create',
            'category edit',
            'category delete',

            'comment view',
            'comment delete',

            'user view',
        ]);

        // Author Permissions
        $author->syncPermissions([
            'post view',
            'post create',
            'post edit',

            'comment view',
        ]);

        // User Permissions
        $user->syncPermissions([
            'post view',
            'comment create',
        ]);
    }
}
