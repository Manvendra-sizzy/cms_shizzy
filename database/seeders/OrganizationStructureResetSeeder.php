<?php

namespace Database\Seeders;

use App\Models\OrganizationDepartment;
use App\Models\OrganizationTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationStructureResetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        // Detach existing employee assignments so we can safely rebuild structure.
        DB::table('employee_profiles')->update(['department_id' => null, 'team_id' => null]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('organization_teams')->truncate();
        DB::table('organization_departments')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $departments = [
            'Business Strategy & Growth' => [
                'Business Growth & Operations',
            ],
            'Business Operations' => [
            ],
            'Marketing' => [
                'Performance Marketing',
                'Content & Social Media',
                'Email & WhatsApp Marketing',
                'SEO',
                'Creative Production',
            ],
            'Development' => [
                'Development',
            ],
            'Sales' => [
                'Sales',
            ],
        ];

        foreach ($departments as $departmentName => $teams) {
            $department = OrganizationDepartment::query()->create([
                'name' => $departmentName,
                'code' => $this->deptCode($departmentName),
                'active' => true,
            ]);

            foreach ($teams as $teamName) {
                OrganizationTeam::query()->create([
                    'department_id' => $department->id,
                    'name' => $teamName,
                    'code' => $this->teamCode($teamName),
                    'active' => true,
                ]);
            }
        }
    }

    private function deptCode(string $name): string
    {
        $base = preg_replace('/[^A-Z]/', '', strtoupper($name));
        $base = substr($base, 0, 8) ?: 'DEPT';
        $code = $base;
        $i = 1;
        while (OrganizationDepartment::query()->where('code', $code)->exists()) {
            $code = $base . $i;
            $i++;
        }
        return $code;
    }

    private function teamCode(string $name): string
    {
        $base = preg_replace('/[^A-Z]/', '', strtoupper($name));
        $base = substr($base, 0, 12) ?: 'TEAM';
        return $base;
    }
}
