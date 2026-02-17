<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleIds = Role::query()->pluck('id', 'name');

        $adminRoleId = $roleIds->get('Admin');
        if ($adminRoleId === null) {
            return;
        }

        $plannerRoleId = $roleIds->get('Planner', $adminRoleId);
        $managerRoleId = $roleIds->get('Manager', $adminRoleId);
        $contributorRoleId = $roleIds->get('Contributor', $adminRoleId);
        $viewerRoleId = $roleIds->get('Viewer', $adminRoleId);

        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
            'password' => 'password',
            'role_id' => $adminRoleId,
        ]);

        User::factory()->create([
            'name' => 'Mitarbeiter',
            'email' => 'user@user.com',
            'password' => 'password',
            'role_id' => $contributorRoleId,
        ]);

        User::factory()->count(1)->create(['role_id' => $plannerRoleId]);
        User::factory()->count(2)->create(['role_id' => $managerRoleId]);
        User::factory()->count(4)->create(['role_id' => $contributorRoleId]);
        User::factory()->count(2)->create(['role_id' => $viewerRoleId]);
    }
}
