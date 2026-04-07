<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoho_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('zoho_invoice_id')->unique();
            $table->string('zoho_customer_id')->nullable();
            $table->string('project_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('status')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('zoho_customer_id');
            $table->index('invoice_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoho_invoices');
    }
};
