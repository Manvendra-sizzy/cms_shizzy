<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_reimbursement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('category', 120)->nullable();
            $table->date('expense_date');
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->string('receipt_path', 512)->nullable();
            $table->string('status', 32)->default('pending'); // pending, approved, rejected
            $table->foreignId('decision_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_reimbursement_requests');
    }
};
