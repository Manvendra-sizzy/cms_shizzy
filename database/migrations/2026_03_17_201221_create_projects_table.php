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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_client_id');
            $table->string('project_code', 16)->unique(); // SZ001...
            $table->string('name', 255);
            $table->string('category', 64)->index(); // Social Media, SEO...
            $table->string('project_type', 16)->index(); // one_time, recurring
            $table->string('billing_type', 16)->index(); // fixed, prorata
            $table->text('description')->nullable();
            $table->unsignedBigInteger('project_manager_employee_profile_id')->nullable();
            $table->unsignedBigInteger('account_manager_employee_profile_id')->nullable();
            $table->string('project_folder', 255)->nullable();
            $table->string('status', 32)->default('active')->index(); // active, hold, cancelled, delivered
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('project_client_id', 'fk_projects_client')
                ->references('id')->on('project_clients')
                ->restrictOnDelete();

            $table->foreign('project_manager_employee_profile_id', 'fk_projects_pm_employee_profile')
                ->references('id')->on('employee_profiles')
                ->nullOnDelete();

            $table->foreign('account_manager_employee_profile_id', 'fk_projects_am_employee_profile')
                ->references('id')->on('employee_profiles')
                ->nullOnDelete();

            $table->foreign('created_by_user_id', 'fk_projects_created_by_user')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
