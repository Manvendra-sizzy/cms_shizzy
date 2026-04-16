<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrms_employment_agreement_contents', function (Blueprint $table) {
            $table->id();
            $table->longText('body_html');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrms_employment_agreement_contents');
    }
};
