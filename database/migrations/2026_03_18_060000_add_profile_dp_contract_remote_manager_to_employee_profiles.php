<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('profile_image_path')->nullable()->after('id_card_path');
            $table->string('signed_contract_path')->nullable()->after('profile_image_path');
            $table->boolean('is_remote')->default(false)->index()->after('status');
            $table->foreignId('reporting_manager_employee_profile_id')
                ->nullable()
                ->after('designation_id')
                ->constrained('employee_profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reporting_manager_employee_profile_id');
            $table->dropColumn(['profile_image_path', 'signed_contract_path', 'is_remote']);
        });
    }
};

