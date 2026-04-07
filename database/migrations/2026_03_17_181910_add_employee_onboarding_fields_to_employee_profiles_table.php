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
            $table->foreignId('department_id')->nullable()->after('user_id')->constrained('organization_departments')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('department_id')->constrained('organization_teams')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->after('team_id')->constrained('organization_designations')->nullOnDelete();

            $table->string('personal_email')->nullable()->after('employee_id');
            $table->string('personal_mobile', 32)->nullable()->after('personal_email');
            $table->string('official_email')->nullable()->after('personal_mobile');
            $table->date('joining_date')->nullable()->after('official_email');
            $table->string('pan_card_path')->nullable()->after('joining_date');
            $table->string('id_card_path')->nullable()->after('pan_card_path');
            $table->string('bank_account_number', 64)->nullable()->after('id_card_path');
            $table->string('bank_ifsc_code', 32)->nullable()->after('bank_account_number');
            $table->string('bank_name')->nullable()->after('bank_ifsc_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('team_id');
            $table->dropConstrainedForeignId('designation_id');
            $table->dropColumn([
                'personal_email',
                'personal_mobile',
                'official_email',
                'joining_date',
                'pan_card_path',
                'id_card_path',
                'bank_account_number',
                'bank_ifsc_code',
                'bank_name',
            ]);
        });
    }
};
