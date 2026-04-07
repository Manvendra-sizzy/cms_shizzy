<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class SystemsModularityTest extends TestCase
{
    public function test_systems_controller_uses_projects_model_only_for_required_project_relation(): void
    {
        $controllerPath = dirname(__DIR__, 3) . '/app/Http/Controllers/Systems/SystemsController.php';
        $code = (string) file_get_contents($controllerPath);

        $this->assertStringContainsString(
            'use App\Modules\Projects\Models\Project;',
            $code,
            'SystemsController should explicitly depend on Projects model for mandatory project relation.'
        );
        $this->assertStringNotContainsString(
            'use App\Modules\HRMS',
            $code,
            'SystemsController must not depend on HRMS module internals.'
        );
        $this->assertStringNotContainsString(
            'use App\Models\ZohoClient;',
            $code,
            'SystemsController must not depend on Zoho client directly.'
        );
    }

    public function test_support_end_date_changes_are_gated_to_extension_flow(): void
    {
        $controllerPath = dirname(__DIR__, 3) . '/app/Http/Controllers/Systems/SystemsController.php';
        $code = (string) file_get_contents($controllerPath);

        $this->assertStringContainsString(
            'Use Extend Support action to change support end date.',
            $code
        );
    }
}
