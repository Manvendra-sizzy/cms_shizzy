<?php

use App\Models\CmsModule;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $module = CmsModule::query()->firstOrCreate(
            ['key' => 'systems'],
            ['name' => 'Systems', 'active' => true],
        );

        $adminIds = User::query()->where('role', User::ROLE_ADMIN)->pluck('id')->all();
        foreach ($adminIds as $userId) {
            $exists = DB::table('cms_user_modules')
                ->where('user_id', $userId)
                ->where('cms_module_id', $module->id)
                ->exists();

            if (! $exists) {
                DB::table('cms_user_modules')->insert([
                    'user_id' => $userId,
                    'cms_module_id' => $module->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $moduleId = CmsModule::query()->where('key', 'systems')->value('id');
        if ($moduleId) {
            DB::table('cms_user_modules')->where('cms_module_id', $moduleId)->delete();
            CmsModule::query()->where('id', $moduleId)->delete();
        }
    }
};
