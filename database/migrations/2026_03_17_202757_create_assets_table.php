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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_category_id');
            $table->string('name', 255);
            $table->string('condition', 64)->nullable();
            $table->string('asset_code', 128)->nullable()->unique();
            $table->string('serial_number', 255)->nullable();
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_value', 12, 2)->nullable();
            $table->string('status', 32)->default('in_stock')->index(); // in_stock, assigned, retired, lost
            $table->timestamps();

            $table->foreign('asset_category_id', 'fk_assets_asset_category')
                ->references('id')->on('asset_categories')
                ->restrictOnDelete();

            $table->index(['asset_category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
