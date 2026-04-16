<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->boolean('is_paid')->default(true)->after('active');
        });

        $codes = ['SL' => ['Sick Leave', 12], 'CL' => ['Casual Leave', 12], 'EL' => ['Earned Leave', 15], 'UL' => ['Unpaid Leave', 0]];
        foreach ($codes as $code => [$name, $allowance]) {
            $exists = DB::table('leave_policies')->where('code', $code)->exists();
            if (!$exists) {
                DB::table('leave_policies')->insert([
                    'name' => $name,
                    'code' => $code,
                    'annual_allowance' => $allowance,
                    'carry_forward' => $code === 'EL',
                    'max_carry_forward' => $code === 'EL' ? 5 : 0,
                    'requires_approval' => true,
                    'active' => true,
                    'is_paid' => $code !== 'UL',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->dropColumn('is_paid');
        });
    }
};
