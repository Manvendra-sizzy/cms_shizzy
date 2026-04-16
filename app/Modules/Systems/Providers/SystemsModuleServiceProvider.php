<?php

namespace App\Modules\Systems\Providers;

use Illuminate\Support\ServiceProvider;

class SystemsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Systems module contracts to implementations here.
    }

    public function boot(): void
    {
        // Load Systems-specific resources as module boundaries evolve.
    }
}
