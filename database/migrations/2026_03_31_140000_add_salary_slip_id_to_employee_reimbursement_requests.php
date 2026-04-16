<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_reimbursement_requests', function (Blueprint $table) {
            $table->foreignId('salary_slip_id')->nullable()->after('admin_note')->constrained('salary_slips')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_reimbursement_requests', function (Blueprint $table) {
            $table->dropForeign(['salary_slip_id']);
        });
    }
};
