<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_revenue_invoices', function (Blueprint $table) {
            $table->string('invoice_file_path', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_revenue_invoices', function (Blueprint $table) {
            $table->dropColumn('invoice_file_path');
        });
    }
};

