<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('systems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->foreignId('support_scope_id')->nullable()->constrained('support_scopes')->nullOnDelete();
            $table->string('system_name', 180);
            $table->string('system_type', 40)->index();
            $table->text('description')->nullable();
            $table->string('live_url', 512)->nullable();
            $table->string('admin_url', 512)->nullable();
            $table->string('repository_link', 512)->nullable();
            $table->string('tech_stack', 255)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->date('support_start_date')->nullable();
            $table->date('support_end_date')->nullable();
            $table->string('support_status', 32)->default('inactive')->index();
            $table->timestamps();

            $table->unique(['project_id', 'system_name'], 'uq_system_name_per_project');
            $table->index(['project_id', 'status'], 'idx_system_project_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};
