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
        Schema::table('organization_designations', function (Blueprint $table) {
            if (Schema::hasColumn('organization_designations', 'department_id')) {
                $table->dropColumn('department_id');
            }
            if (Schema::hasColumn('organization_designations', 'team_id')) {
                $table->dropColumn('team_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_designations', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('team_id')->nullable()->index();
        });
    }
};
