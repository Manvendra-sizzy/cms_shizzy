<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_documentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained('systems')->cascadeOnDelete();
            $table->longText('overview')->nullable();
            $table->longText('architecture')->nullable();
            $table->longText('infrastructure_mapping')->nullable();
            $table->longText('deployment_process')->nullable();
            $table->longText('recovery_instructions')->nullable();
            $table->longText('external_integrations')->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();

            $table->unique('system_id', 'uq_system_documentation_system');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_documentations');
    }
};
