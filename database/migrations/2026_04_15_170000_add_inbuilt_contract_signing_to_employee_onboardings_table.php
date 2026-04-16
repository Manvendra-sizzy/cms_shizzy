<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_onboardings', function (Blueprint $table) {
            $table->string('contract_status', 40)->nullable()->index();
            $table->string('contract_token_hash', 64)->nullable()->unique();
            $table->timestamp('contract_token_expires_at')->nullable()->index();
            $table->timestamp('contract_sent_at')->nullable();
            $table->timestamp('contract_opened_at')->nullable();
            $table->timestamp('contract_agreed_at')->nullable();
            $table->timestamp('contract_signed_at')->nullable();
            $table->string('contract_signature_path', 512)->nullable();
            $table->string('contract_selfie_path', 512)->nullable();
            $table->string('contract_signed_pdf_path', 512)->nullable();
            $table->string('contract_document_hash', 64)->nullable();
            $table->json('contract_sign_meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employee_onboardings', function (Blueprint $table) {
            $table->dropColumn([
                'contract_status',
                'contract_token_hash',
                'contract_token_expires_at',
                'contract_sent_at',
                'contract_opened_at',
                'contract_agreed_at',
                'contract_signed_at',
                'contract_signature_path',
                'contract_selfie_path',
                'contract_signed_pdf_path',
                'contract_document_hash',
                'contract_sign_meta',
            ]);
        });
    }
};
