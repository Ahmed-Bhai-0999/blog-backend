<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [

            'post view',
            'post create',
            'post edit',
            'post delete',

            'category view',
            'category create',
            'category edit',
            'category delete',

            'comment view',
            'comment create',
            'comment delete',

            'user view',
            'user create',
            'user edit',
            'user delete',
        ];

        foreach ($permissions as $permission)
        {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
    }
}

