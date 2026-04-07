<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_development_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained('systems')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description');
            $table->string('change_type', 32)->index();
            $table->string('version', 64)->nullable();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('change_date')->index();
            $table->string('deployment_status', 32)->default('planned')->index();
            $table->timestamps();

            $table->index(['system_id', 'change_date'], 'idx_system_dev_logs_system_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_development_logs');
    }
};
