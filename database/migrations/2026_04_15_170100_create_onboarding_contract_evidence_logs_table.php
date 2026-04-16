<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_contract_evidence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_onboarding_id')->constrained('employee_onboardings')->cascadeOnDelete();
            $table->string('event_type', 80)->index();
            $table->string('event_hash', 64)->index();
            $table->string('previous_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->longText('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['employee_onboarding_id', 'id'], 'onboarding_contract_evidence_logs_ob_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_contract_evidence_logs');
    }
};
