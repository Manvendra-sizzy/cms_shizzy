<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cms_role_id');
            $table->boolean('all_projects')->default(false)->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['user_id', 'cms_role_id'], 'uq_cms_user_role');
            $table->foreign('user_id', 'fk_cms_user_roles_user')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('cms_role_id', 'fk_cms_user_roles_role')
                ->references('id')->on('cms_roles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_user_roles');
    }
};
