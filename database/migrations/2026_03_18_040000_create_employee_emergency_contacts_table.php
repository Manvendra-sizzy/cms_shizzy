<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot')->default(1); // 1 or 2
            $table->string('name', 255)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('relation', 64)->nullable();
            $table->timestamps();
            $table->unique(['employee_profile_id', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_emergency_contacts');
    }
};

