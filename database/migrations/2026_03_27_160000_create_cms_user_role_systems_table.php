<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_user_role_systems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cms_user_role_id');
            $table->unsignedBigInteger('system_id');
            $table->timestamps();

            $table->unique(['cms_user_role_id', 'system_id'], 'uq_user_role_system');
            $table->foreign('cms_user_role_id', 'fk_user_role_systems_user_role')
                ->references('id')->on('cms_user_roles')->cascadeOnDelete();
            $table->foreign('system_id', 'fk_user_role_systems_system')
                ->references('id')->on('systems')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_user_role_systems');
    }
};
