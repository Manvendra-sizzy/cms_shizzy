<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_onboarding_id')->constrained('employee_onboardings')->cascadeOnDelete();
            $table->string('doc_key', 80);
            $table->string('title', 180);
            $table->string('file_path', 500);
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();

            $table->index(['employee_onboarding_id', 'doc_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_documents');
    }
};

