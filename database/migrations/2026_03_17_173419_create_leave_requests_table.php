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
        Schema::dropIfExists('leave_requests');

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_profile_id');
            $table->unsignedBigInteger('leave_policy_id');

            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->decimal('days', 5, 2)->default(0);
            $table->text('reason')->nullable();

            $table->string('status', 32)->default('pending')->index(); // pending, approved, rejected, cancelled
            $table->unsignedBigInteger('decision_by_user_id')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_profile_id', 'fk_leave_requests_employee_profile')
                ->references('id')->on('employee_profiles')
                ->cascadeOnDelete();

            $table->foreign('leave_policy_id', 'fk_leave_requests_leave_policy')
                ->references('id')->on('leave_policies')
                ->restrictOnDelete();

            $table->foreign('decision_by_user_id', 'fk_leave_requests_decision_user')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
