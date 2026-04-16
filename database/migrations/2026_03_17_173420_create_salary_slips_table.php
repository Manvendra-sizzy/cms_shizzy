<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('salary_slips');

        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_run_id');
            $table->unsignedBigInteger('employee_profile_id');

            $table->string('slip_number', 64)->unique();
            $table->string('currency', 8)->default('INR');
            $table->decimal('gross', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->string('file_path')->nullable();
            $table->timestamp('issued_at')->nullable()->index();

            $table->timestamps();

            $table->foreign('payroll_run_id', 'fk_salary_slips_payroll_run')
                ->references('id')->on('payroll_runs')
                ->cascadeOnDelete();

            $table->foreign('employee_profile_id', 'fk_salary_slips_employee_profile')
                ->references('id')->on('employee_profiles')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};
