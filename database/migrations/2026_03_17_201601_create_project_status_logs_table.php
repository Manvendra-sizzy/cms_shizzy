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
        Schema::create('project_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->date('effective_date')->index();
            $table->text('remark');
            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('project_id', 'fk_project_status_logs_project')
                ->references('id')->on('projects')
                ->cascadeOnDelete();

            $table->foreign('changed_by_user_id', 'fk_project_status_logs_changed_by_user')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_status_logs');
    }
};
