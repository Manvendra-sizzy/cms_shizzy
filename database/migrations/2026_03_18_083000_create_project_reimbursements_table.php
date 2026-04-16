<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_reimbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('project_revenue_stream_id')
                ->nullable()
                ->constrained('project_revenue_streams')
                ->nullOnDelete();

            $table->date('spent_date')->nullable()->index();
            $table->string('description', 255)->nullable();

            $table->decimal('spend_amount', 12, 2)->default(0);
            $table->string('markup_type', 16)->default('percent'); // percent, fixed
            $table->decimal('markup_value', 12, 2)->default(0);
            $table->decimal('final_billable_amount', 12, 2)->default(0);

            $table->string('status', 24)->default('not_billed')->index(); // not_billed, billed, recovered
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_reimbursements');
    }
};

