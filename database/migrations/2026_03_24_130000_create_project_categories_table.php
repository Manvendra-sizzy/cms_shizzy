<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        $legacyCategories = DB::table('projects')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        $now = now();
        foreach ($legacyCategories as $category) {
            $name = trim((string) $category);
            if ($name === '') {
                continue;
            }

            DB::table('project_categories')->updateOrInsert(
                ['name' => $name],
                ['active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_categories');
    }
};

