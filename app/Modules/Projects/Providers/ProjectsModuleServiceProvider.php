<?php

namespace App\Modules\Projects\Providers;

use Illuminate\Support\ServiceProvider;

class ProjectsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Projects module contracts to implementations here.
    }

    public function boot(): void
    {
        // Load Projects-specific resources as module boundaries evolve.
    }
}
