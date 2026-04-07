<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_infrastructure_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_id');
            $table->unsignedBigInteger('infrastructure_resource_id');
            $table->timestamps();

            $table->unique(['system_id', 'infrastructure_resource_id'], 'uq_system_infra_resource');

            $table->foreign('system_id', 'fk_sysinfra_system')
                ->references('id')
                ->on('systems')
                ->cascadeOnDelete();
            $table->foreign('infrastructure_resource_id', 'fk_sysinfra_resource')
                ->references('id')
                ->on('infrastructure_resources')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_infrastructure_resources');
    }
};
