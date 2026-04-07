<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_user_role_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cms_user_role_id');
            $table->unsignedBigInteger('project_id');
            $table->timestamps();

            $table->unique(['cms_user_role_id', 'project_id'], 'uq_user_role_project');
            $table->foreign('cms_user_role_id', 'fk_user_role_projects_user_role')
                ->references('id')->on('cms_user_roles')->cascadeOnDelete();
            $table->foreign('project_id', 'fk_user_role_projects_project')
                ->references('id')->on('projects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_user_role_projects');
    }
};
