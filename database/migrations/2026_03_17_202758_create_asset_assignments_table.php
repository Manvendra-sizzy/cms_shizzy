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
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('employee_profile_id');
            $table->date('assigned_at')->index();
            $table->date('returned_at')->nullable()->index();
            $table->string('action_type', 32)->default('assigned');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('asset_id', 'fk_asset_assignments_asset')
                ->references('id')->on('assets')
                ->cascadeOnDelete();

            $table->foreign('employee_profile_id', 'fk_asset_assignments_employee_profile')
                ->references('id')->on('employee_profiles')
                ->cascadeOnDelete();

            $table->foreign('created_by_user_id', 'fk_asset_assignments_created_by_user')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index(['asset_id', 'returned_at']);
            $table->index(['employee_profile_id', 'returned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
