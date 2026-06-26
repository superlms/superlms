<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $roles = [
             [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Administrator with All access'
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrator with School access'
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
                'description' => 'Regular application Student'
            ],
            [
                'name' => 'Teacher',
                'slug' => 'teacher',
                'description' => 'Regular application For Teacher'
            ]
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
        $this->command->info('Roles seeding.');
    }
}
