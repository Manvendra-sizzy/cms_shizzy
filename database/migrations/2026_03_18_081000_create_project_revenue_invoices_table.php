<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_revenue_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_revenue_stream_id')
                ->constrained('project_revenue_streams')
                ->cascadeOnDelete();

            $table->string('invoice_number', 64)->nullable()->index();
            $table->date('invoice_date')->nullable()->index();
            $table->date('due_date')->nullable()->index();

            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 24)->default('sent')->index(); // draft, sent, paid, cancelled
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_revenue_stream_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_revenue_invoices');
    }
};

