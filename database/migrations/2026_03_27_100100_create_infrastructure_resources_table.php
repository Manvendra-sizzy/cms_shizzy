<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infrastructure_resources', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type', 40)->index();
            $table->string('name', 160);
            $table->string('vendor', 120)->nullable()->index();
            $table->text('description')->nullable();
            $table->string('access_url', 512)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->unique(['resource_type', 'name'], 'uq_infra_type_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infrastructure_resources');
    }
};
