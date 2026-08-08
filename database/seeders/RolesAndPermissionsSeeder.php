<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the default system roles and a Super Administrator account.
     */
    public function run(): void
    {
        // Ensure the Super Administrator role is always present.
        $this->ensureRoleExists(RoleEnum::SUPER_ADMINISTRATOR);

        // Create every remaining default system role.
        foreach (RoleEnum::cases() as $roleEnum) {
            $this->ensureRoleExists($roleEnum);
        }

        // Create the bootstrap Super Administrator account (idempotent).
        $superAdmin = User::query()->firstOrCreate(
            ['email' => config('app.super_admin_email', 'admin@konexus.local')],
            [
                'name' => config('app.super_admin_name', 'KONEXUS Administrator'),
                'password' => config('app.super_admin_password', 'password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (! $superAdmin->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())) {
            $superAdmin->assignRole(RoleEnum::SUPER_ADMINISTRATOR->roleName());
        }
    }

    /**
     * Create the role when it does not already exist.
     */
    private function ensureRoleExists(RoleEnum $role): void
    {
        Role::query()->firstOrCreate(
            ['name' => $role->roleName(), 'guard_name' => 'web'],
            [
                'label' => $role->label(),
                'description' => $role->description(),
            ]
        );
    }
}
