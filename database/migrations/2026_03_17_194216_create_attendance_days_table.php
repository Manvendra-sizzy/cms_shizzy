<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->date('work_date');
            $table->dateTime('punch_in_at')->nullable();
            $table->dateTime('punch_out_at')->nullable();
            $table->timestamps();
            $table->unique(['employee_profile_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
    }
};
