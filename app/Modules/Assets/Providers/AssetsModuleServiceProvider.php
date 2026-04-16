<?php

namespace App\Modules\Assets\Providers;

use Illuminate\Support\ServiceProvider;

class AssetsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Assets module contracts to implementations here.
    }

    public function boot(): void
    {
        // Load Assets-specific resources as module boundaries evolve.
    }
}
