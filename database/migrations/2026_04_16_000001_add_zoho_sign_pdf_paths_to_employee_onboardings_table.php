<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_onboardings', function (Blueprint $table) {
            $table->string('zoho_sign_agreement_pdf_path', 512)->nullable()->after('zoho_sign_meta');
            $table->string('zoho_sign_signed_pdf_path', 512)->nullable()->after('zoho_sign_agreement_pdf_path');
            $table->timestamp('zoho_sign_completed_at')->nullable()->after('zoho_sign_signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('employee_onboardings', function (Blueprint $table) {
            $table->dropColumn(['zoho_sign_agreement_pdf_path', 'zoho_sign_signed_pdf_path', 'zoho_sign_completed_at']);
        });
    }
};
