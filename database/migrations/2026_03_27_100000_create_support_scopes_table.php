<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('scope_name', 150)->unique();
            $table->text('description')->nullable();
            $table->text('included_services')->nullable();
            $table->text('excluded_services')->nullable();
            $table->string('sla_response_time', 120)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_scopes');
    }
};
