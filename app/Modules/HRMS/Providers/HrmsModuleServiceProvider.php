<?php

namespace App\Modules\HRMS\Providers;

use App\Modules\HRMS\Infrastructure\EloquentEmployeeDirectory;
use App\Modules\Projects\Contracts\EmployeeDirectoryContract;
use Illuminate\Support\ServiceProvider;

class HrmsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmployeeDirectoryContract::class, EloquentEmployeeDirectory::class);
    }

    public function boot(): void
    {
        // Load HRMS-specific resources as module boundaries evolve.
    }
}
