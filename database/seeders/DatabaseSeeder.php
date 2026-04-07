<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\CmsModule;
use App\Models\OrganizationDepartment;
use App\Models\OrganizationTeam;
use App\Models\OrganizationDesignation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $hrms = CmsModule::query()->firstOrCreate(
            ['key' => 'hrms'],
            ['name' => 'HRMS', 'active' => true],
        );

        // Ensure at least one default Admin exists.
        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ],
        )->modules()->syncWithoutDetaching([$hrms->id]);

        // Backfill: grant HRMS to all Admins (useful if older users were migrated to admin).
        User::query()
            ->where('role', User::ROLE_ADMIN)
            ->get()
            ->each(fn (User $admin) => $admin->modules()->syncWithoutDetaching([$hrms->id]));

        $ops = OrganizationDepartment::query()->firstOrCreate(
            ['code' => 'OPS'],
            ['name' => 'Operations', 'active' => true],
        );
        $hr = OrganizationDepartment::query()->firstOrCreate(
            ['code' => 'HR'],
            ['name' => 'Human Resources', 'active' => true],
        );

        $opsTeam = OrganizationTeam::query()->firstOrCreate(
            ['department_id' => $ops->id, 'code' => 'OPS-CORE'],
            ['name' => 'Operations Core', 'active' => true],
        );
        $hrTeam = OrganizationTeam::query()->firstOrCreate(
            ['department_id' => $hr->id, 'code' => 'HR-CORE'],
            ['name' => 'HR Core', 'active' => true],
        );

        OrganizationDesignation::query()->firstOrCreate(
            ['code' => 'PM'],
            ['name' => 'Project Manager', 'active' => true],
        );
        OrganizationDesignation::query()->firstOrCreate(
            ['code' => 'FM'],
            ['name' => 'Finance Manager', 'active' => true],
        );
        OrganizationDesignation::query()->firstOrCreate(
            ['code' => 'HRM'],
            ['name' => 'HR Manager', 'active' => true],
        );
    }
}
