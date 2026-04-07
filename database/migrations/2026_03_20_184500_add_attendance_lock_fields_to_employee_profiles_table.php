<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->timestamp('attendance_locked_at')->nullable()->after('status');
            $table->text('attendance_lock_reason')->nullable()->after('attendance_locked_at');
            $table->text('attendance_unlock_note')->nullable()->after('attendance_lock_reason');
            $table->foreignId('attendance_unlock_by_user_id')->nullable()->after('attendance_unlock_note')->constrained('users')->nullOnDelete();
            $table->timestamp('attendance_unlock_at')->nullable()->after('attendance_unlock_by_user_id');
            $table->timestamp('last_missed_punch_out_notice_at')->nullable()->after('attendance_unlock_at');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_unlock_by_user_id');
            $table->dropColumn([
                'attendance_locked_at',
                'attendance_lock_reason',
                'attendance_unlock_note',
                'attendance_unlock_at',
                'last_missed_punch_out_notice_at',
            ]);
        });
    }
};

