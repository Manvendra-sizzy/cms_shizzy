<?php

namespace App\Modules\Employee\Providers;

use Illuminate\Support\ServiceProvider;

class EmployeeModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Employee module contracts to implementations here.
    }

    public function boot(): void
    {
        // Load Employee-specific resources as module boundaries evolve.
    }
}
