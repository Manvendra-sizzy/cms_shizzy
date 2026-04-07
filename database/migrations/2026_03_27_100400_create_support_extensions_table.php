<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained('systems')->cascadeOnDelete();
            $table->date('previous_end_date');
            $table->date('new_end_date');
            $table->foreignId('extended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('extended_at')->useCurrent();
            $table->timestamps();

            $table->index(['system_id', 'extended_at'], 'idx_support_extensions_system_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_extensions');
    }
};
