<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_attendance_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->date('work_date');
            $table->string('type', 64)->default('missed_punch_out');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['employee_profile_id', 'work_date', 'type'], 'employee_attendance_reminders_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_reminders');
    }
};

