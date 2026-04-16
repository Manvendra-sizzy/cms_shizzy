<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('employee_type', 32)->nullable()->after('status')->index();
            $table->string('employee_badge', 64)->nullable()->after('employee_type')->index();

            $table->unsignedSmallInteger('internship_period_months')->nullable()->after('employee_badge');
            $table->date('internship_start_date')->nullable()->after('internship_period_months');
            $table->date('internship_end_date')->nullable()->after('internship_start_date');

            $table->unsignedSmallInteger('probation_period_months')->nullable()->after('internship_end_date');
            $table->date('probation_start_date')->nullable()->after('probation_period_months');
            $table->date('probation_end_date')->nullable()->after('probation_start_date');

            $table->timestamp('converted_to_permanent_at')->nullable()->after('probation_end_date');
        });

        // Backward compatibility: treat existing employees as permanent employees.
        DB::table('employee_profiles')
            ->whereNull('employee_type')
            ->update([
                'employee_type' => 'permanent_employee',
                'employee_badge' => 'permanent_employee_pe',
            ]);
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'employee_type',
                'employee_badge',
                'internship_period_months',
                'internship_start_date',
                'internship_end_date',
                'probation_period_months',
                'probation_start_date',
                'probation_end_date',
                'converted_to_permanent_at',
            ]);
        });
    }
};
