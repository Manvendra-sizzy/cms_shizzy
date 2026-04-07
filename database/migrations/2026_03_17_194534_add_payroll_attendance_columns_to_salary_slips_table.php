<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->decimal('base_salary', 12, 2)->nullable()->after('currency');
            $table->unsignedSmallInteger('working_days')->nullable();
            $table->decimal('paid_leave_days', 8, 2)->nullable();
            $table->decimal('lop_days', 8, 2)->nullable();
            $table->decimal('lop_deduction', 12, 2)->nullable();
            $table->json('earning_lines')->nullable();
            $table->json('deduction_lines')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->dropColumn([
                'base_salary',
                'working_days',
                'paid_leave_days',
                'lop_days',
                'lop_deduction',
                'earning_lines',
                'deduction_lines',
            ]);
        });
    }
};
