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
        Schema::create('project_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('employee_profile_id');
            $table->string('role_title', 255)->nullable();
            $table->unsignedBigInteger('added_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'employee_profile_id']);

            $table->foreign('project_id', 'fk_project_team_members_project')
                ->references('id')->on('projects')
                ->cascadeOnDelete();

            $table->foreign('employee_profile_id', 'fk_project_team_members_employee_profile')
                ->references('id')->on('employee_profiles')
                ->cascadeOnDelete();

            $table->foreign('added_by_user_id', 'fk_project_team_members_added_by_user')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_team_members');
    }
};
