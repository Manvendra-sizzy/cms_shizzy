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
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('status', 32)->default('active')->change();
            $table->date('inactive_at')->nullable()->after('status');
            $table->text('inactive_remarks')->nullable()->after('inactive_at');
            $table->string('separation_type', 32)->nullable()->after('inactive_remarks'); // resigned, terminated, retired
            $table->date('separation_effective_at')->nullable()->after('separation_type');
            $table->text('separation_remarks')->nullable()->after('separation_effective_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('status', 32)->default('active')->change();
            $table->dropColumn([
                'inactive_at',
                'inactive_remarks',
                'separation_type',
                'separation_effective_at',
                'separation_remarks',
            ]);
        });
    }
};
