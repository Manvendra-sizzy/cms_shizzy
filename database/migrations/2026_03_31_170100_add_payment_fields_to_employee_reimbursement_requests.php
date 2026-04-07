<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_reimbursement_requests', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('amount');
            $table->timestamp('last_paid_at')->nullable()->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('employee_reimbursement_requests', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'last_paid_at']);
        });
    }
};

