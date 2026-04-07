<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profile_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->foreignId('organization_team_id')->constrained('organization_teams')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['employee_profile_id', 'organization_team_id'], 'employee_profile_team_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profile_team');
    }
};

