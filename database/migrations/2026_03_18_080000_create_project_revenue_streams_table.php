<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_revenue_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            $table->string('name', 255);
            $table->string('type', 32)->index(); // retainer, usage, reimbursement, fixed, installment, annual
            $table->string('billing_cycle', 32)->nullable()->index(); // monthly, quarterly, annual, one_time, custom

            $table->decimal('expected_total_value', 12, 2)->default(0);
            $table->decimal('rate_per_unit', 12, 2)->nullable();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('calculated_amount', 12, 2)->nullable();

            $table->date('start_date')->nullable();
            $table->date('next_billing_date')->nullable()->index();

            $table->boolean('active')->default(true)->index();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_revenue_streams');
    }
};

