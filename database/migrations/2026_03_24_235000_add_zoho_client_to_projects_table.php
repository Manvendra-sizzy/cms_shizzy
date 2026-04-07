<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy manual clients remain in data, but new projects will use Zoho clients.
        try {
            DB::statement('ALTER TABLE projects DROP FOREIGN KEY fk_projects_client');
        } catch (\Throwable $e) {
            // Ignore if key name differs/missing.
        }

        try {
            DB::statement('ALTER TABLE projects MODIFY project_client_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Fallback for environments where column may already be nullable.
        }

        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'zoho_client_id')) {
                $table->unsignedBigInteger('zoho_client_id')->nullable()->after('project_client_id');
                $table->index('zoho_client_id', 'idx_projects_zoho_client_id');
            }
        });

        try {
            DB::statement('ALTER TABLE projects ADD CONSTRAINT fk_projects_zoho_client FOREIGN KEY (zoho_client_id) REFERENCES zoho_clients(id) ON DELETE SET NULL');
        } catch (\Throwable $e) {
            // Ignore if already exists.
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE projects DROP FOREIGN KEY fk_projects_zoho_client');
        } catch (\Throwable $e) {
        }

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'zoho_client_id')) {
                $table->dropIndex('idx_projects_zoho_client_id');
                $table->dropColumn('zoho_client_id');
            }
        });
    }
};

