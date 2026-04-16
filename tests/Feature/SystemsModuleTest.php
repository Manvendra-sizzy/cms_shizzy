<?php

namespace Tests\Feature;

use App\Models\CmsModule;
use App\Models\User;
use App\Modules\Systems\Models\InfrastructureResource;
use App\Modules\Systems\Models\SupportExtension;
use App\Modules\Systems\Models\System as SystemModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_system_requires_project(): void
    {
        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'secret12345',
            'role' => User::ROLE_ADMIN,
        ]);

        $systemsModule = CmsModule::query()->firstOrCreate(
            ['key' => 'systems'],
            ['name' => 'Systems', 'active' => true]
        );
        DB::table('cms_user_modules')->insert([
            'user_id' => $user->id,
            'cms_module_id' => $systemsModule->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('systems.store'), [
                'system_name' => 'Main Website',
                'system_type' => 'wordpress',
                'status' => 'active',
                'support_status' => 'inactive',
            ])
            ->assertSessionHasErrors(['project_id']);
    }

    public function test_extend_support_updates_end_date_and_creates_log(): void
    {
        $system = SystemModel::query()->create([
            'project_id' => $this->seedProject(),
            'system_name' => 'Project App',
            'system_type' => 'laravel',
            'status' => 'active',
            'support_start_date' => '2026-03-01',
            'support_end_date' => '2026-03-31',
            'support_status' => 'active',
        ]);

        $user = User::query()->create([
            'name' => 'Finance',
            'email' => 'finance@example.com',
            'password' => 'secret12345',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($user)
            ->withoutMiddleware(\App\Http\Middleware\EnsureModuleAccess::class)
            ->post(route('systems.support_extensions.store', $system), [
                'new_end_date' => '2026-04-15',
                'reason' => 'Client renewed support agreement',
            ])
            ->assertRedirect(route('systems.show', $system));

        $this->assertDatabaseHas('systems', [
            'id' => $system->id,
            'support_end_date' => '2026-04-15',
        ]);

        $this->assertDatabaseHas('support_extensions', [
            'system_id' => $system->id,
            'previous_end_date' => '2026-03-31',
            'new_end_date' => '2026-04-15',
        ]);
    }

    public function test_infrastructure_resource_can_be_reused_across_systems(): void
    {
        $resource = InfrastructureResource::query()->create([
            'resource_type' => 'cdn',
            'name' => 'Cloudflare Main',
            'vendor' => 'Cloudflare',
            'status' => 'active',
        ]);

        $systemOne = SystemModel::query()->create([
            'project_id' => $this->seedProject('A'),
            'system_name' => 'System A',
            'system_type' => 'react',
            'status' => 'active',
            'support_status' => 'inactive',
        ]);
        $systemTwo = SystemModel::query()->create([
            'project_id' => $this->seedProject('B'),
            'system_name' => 'System B',
            'system_type' => 'laravel',
            'status' => 'active',
            'support_status' => 'inactive',
        ]);

        $systemOne->infrastructureResources()->sync([$resource->id]);
        $systemTwo->infrastructureResources()->sync([$resource->id]);

        $this->assertSame(2, $resource->systems()->count());
    }

    private function seedProject(string $suffix = 'X'): int
    {
        $clientId = DB::table('project_clients')->insertGetId([
            'name' => 'Client ' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('projects')->insertGetId([
            'project_client_id' => $clientId,
            'project_code' => 'SZ9' . $suffix . random_int(10, 99),
            'name' => 'Project ' . $suffix,
            'category' => 'Web Development',
            'project_type' => 'recurring',
            'billing_type' => 'fixed',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
