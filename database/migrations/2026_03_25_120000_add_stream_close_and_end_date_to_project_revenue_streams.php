<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_revenue_streams', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('next_billing_date');
            $table->date('closed_at')->nullable()->after('active');
            $table->text('closed_remark')->nullable()->after('closed_at');
        });

        DB::table('project_revenue_streams')->where('type', 'annual')->update(['type' => 'lifetime']);
    }

    public function down(): void
    {
        DB::table('project_revenue_streams')->where('type', 'lifetime')->update(['type' => 'annual']);

        Schema::table('project_revenue_streams', function (Blueprint $table) {
            $table->dropColumn(['end_date', 'closed_at', 'closed_remark']);
        });
    }
};
