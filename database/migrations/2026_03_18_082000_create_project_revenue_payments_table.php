<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('project_revenue_payments');

        Schema::create('project_revenue_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_revenue_stream_id')
                ->constrained('project_revenue_streams')
                ->cascadeOnDelete();

            $table->foreignId('project_revenue_invoice_id')
                ->nullable()
                ->constrained('project_revenue_invoices')
                ->nullOnDelete();

            $table->date('payment_date')->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);

            $table->string('method', 32)->nullable(); // bank, upi, cash, card, other
            $table->string('reference', 128)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // MySQL index name length can be limited; keep custom name short.
            $table->index(['project_revenue_stream_id', 'payment_date'], 'prp_stream_paydate_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_revenue_payments');
    }
};

