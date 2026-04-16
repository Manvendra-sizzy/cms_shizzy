<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_onboardings', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 255);
            $table->string('email', 255);
            $table->string('phone', 32)->nullable();
            $table->string('employee_type', 40);
            $table->foreignId('designation_id')->nullable()->constrained('organization_designations')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('organization_departments')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('organization_teams')->nullOnDelete();
            $table->date('joining_date')->nullable();

            $table->string('status', 40)->default('draft')->index();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable()->index();
            $table->timestamp('link_sent_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('hr_notes')->nullable();
            $table->json('employee_payload')->nullable();

            $table->string('zoho_sign_request_id', 120)->nullable()->index();
            $table->string('zoho_sign_status', 60)->nullable()->index();
            $table->timestamp('zoho_sign_sent_at')->nullable();
            $table->timestamp('zoho_sign_signed_at')->nullable();
            $table->json('zoho_sign_meta')->nullable();

            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('final_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('final_employee_profile_id')->nullable()->constrained('employee_profiles')->nullOnDelete();
            $table->timestamps();

            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_onboardings');
    }
};

