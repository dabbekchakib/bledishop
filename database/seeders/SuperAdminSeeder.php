<?php

namespace Database\Seeders;

use App\Enums\Role as UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the development super-admin account. Idempotent.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => config('superadmin.email')],
            [
                'name' => config('superadmin.name'),
                'password' => config('superadmin.password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $user->syncRoles([UserRole::SuperAdmin->value]);
    }
}
