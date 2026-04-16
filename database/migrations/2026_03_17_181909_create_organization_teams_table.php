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
        Schema::dropIfExists('organization_teams');
        Schema::create('organization_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('organization_departments')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->boolean('active')->default(true)->index();
            $table->unique(['department_id', 'code']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_teams');
    }
};
