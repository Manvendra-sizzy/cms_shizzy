<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class ProjectsModularityTest extends TestCase
{
    public function test_projects_controller_uses_directory_contracts_instead_of_cross_module_models(): void
    {
        $controllerPath = dirname(__DIR__, 3) . '/app/Http/Controllers/Projects/ProjectsController.php';
        $code = (string) file_get_contents($controllerPath);

        $this->assertStringContainsString(
            'use App\Modules\Projects\Contracts\EmployeeDirectoryContract;',
            $code,
            'ProjectsController must depend on EmployeeDirectoryContract.'
        );
        $this->assertStringContainsString(
            'use App\Modules\Projects\Contracts\ZohoClientDirectoryContract;',
            $code,
            'ProjectsController must depend on ZohoClientDirectoryContract.'
        );

        $this->assertStringNotContainsString(
            'use App\Modules\HRMS\Employees\Models\EmployeeProfile;',
            $code,
            'ProjectsController must not directly import EmployeeProfile.'
        );
        $this->assertStringNotContainsString(
            'use App\Models\ZohoClient;',
            $code,
            'ProjectsController must not directly import ZohoClient.'
        );
    }

    public function test_project_finances_controller_uses_billing_gateway_contract(): void
    {
        $controllerPath = dirname(__DIR__, 3) . '/app/Http/Controllers/Projects/ProjectFinancesController.php';
        $code = (string) file_get_contents($controllerPath);

        $this->assertStringContainsString(
            'use App\Modules\Projects\Contracts\BillingInvoiceGatewayContract;',
            $code,
            'ProjectFinancesController must depend on BillingInvoiceGatewayContract.'
        );

        $this->assertStringNotContainsString(
            'use App\Services\Zoho\ZohoBooksService;',
            $code,
            'ProjectFinancesController must not directly import ZohoBooksService.'
        );
    }
}
