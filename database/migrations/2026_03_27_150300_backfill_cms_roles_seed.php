<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = [
            ['key' => 'project_manager', 'name' => 'Project Manager'],
            ['key' => 'finance_manager', 'name' => 'Finance Manager'],
            ['key' => 'hr_manager', 'name' => 'HR Manager'],
            ['key' => 'developer', 'name' => 'Developer'],
        ];

        foreach ($roles as $role) {
            $exists = DB::table('cms_roles')->where('key', $role['key'])->exists();
            if (! $exists) {
                DB::table('cms_roles')->insert([
                    'key' => $role['key'],
                    'name' => $role['name'],
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('cms_roles')->whereIn('key', [
            'project_manager',
            'finance_manager',
            'hr_manager',
            'developer',
        ])->delete();
    }
};
