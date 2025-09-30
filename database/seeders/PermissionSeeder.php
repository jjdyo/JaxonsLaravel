<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view about page url',
            'view functions page',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);

        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        $adminRole->syncPermissions(Permission::all());

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => 'password',
                'role' => $adminRole,
                'guard_name' => 'web',
            ],
            [
                'name' => 'Normal User',
                'email' => 'user@example.com',
                'password' => 'password',
                'role' => $userRole,
                'guard_name' => 'web',
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                ]
            );

            $user->assignRole($data['role']);
        }
    }
}
