<?php

namespace App\Modules\CMS\Providers;

use App\Modules\CMS\Infrastructure\EloquentZohoClientDirectory;
use App\Modules\CMS\Infrastructure\ZohoBillingInvoiceGateway;
use App\Modules\Projects\Contracts\BillingInvoiceGatewayContract;
use App\Modules\Projects\Contracts\ZohoClientDirectoryContract;
use Illuminate\Support\ServiceProvider;

class CmsModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ZohoClientDirectoryContract::class, EloquentZohoClientDirectory::class);
        $this->app->bind(BillingInvoiceGatewayContract::class, ZohoBillingInvoiceGateway::class);
    }

    public function boot(): void
    {
        // Load CMS-specific resources as module boundaries evolve.
    }
}
