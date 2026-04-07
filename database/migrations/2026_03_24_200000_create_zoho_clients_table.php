<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoho_clients', function (Blueprint $table) {
            $table->id();
            $table->string('zoho_contact_id')->unique();
            $table->string('contact_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('contact_type')->nullable();
            $table->string('status')->nullable();
            $table->string('gst_no')->nullable();
            $table->string('gst_treatment')->nullable();
            $table->string('place_of_contact')->nullable();
            $table->decimal('outstanding_receivable_amount', 15, 2)->default(0);
            $table->json('raw_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('contact_name');
            $table->index('company_name');
            $table->index('email');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoho_clients');
    }
};
