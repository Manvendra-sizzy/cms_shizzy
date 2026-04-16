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
        Schema::create('hr_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained('employee_profiles')->cascadeOnDelete()->index();
            $table->foreignId('issued_by_user_id')->constrained('users')->restrictOnDelete();

            $table->string('type', 64)->index(); // offer_letter, relieving_letter, employment_certificate, other
            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('file_path')->nullable(); // storage path if uploaded/generated
            $table->json('meta')->nullable();
            $table->timestamp('issued_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_documents');
    }
};
