<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cms_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('user_email', 190)->nullable();

            $table->string('action_key', 255);
            $table->string('route_name', 255)->nullable();
            $table->string('method', 10);
            $table->text('url')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedSmallInteger('status_code')->default(200);

            $table->json('context')->nullable();

            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['action_key']);
            $table->index(['route_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_activity_logs');
    }
};

