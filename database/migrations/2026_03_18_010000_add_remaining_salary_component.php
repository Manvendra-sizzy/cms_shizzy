<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('salary_components')->updateOrInsert(
            ['code' => 'REMAINING'],
            [
                'name' => 'Remaining',
                'type' => 'remaining',
                'value' => 0,
                'base_component_code' => null,
                'sequence' => 999,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('salary_components')->where('code', 'REMAINING')->delete();
    }
};

