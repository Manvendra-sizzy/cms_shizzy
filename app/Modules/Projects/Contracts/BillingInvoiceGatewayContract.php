<?php

namespace App\Modules\Projects\Contracts;

use Illuminate\Support\Collection;

interface BillingInvoiceGatewayContract
{
    public function getProjectInvoices(string $projectCode): Collection;

    public function getInvoicesForProjectCodesBetween(array $projectCodes, string $startDate, string $endDate): Collection;

    public function downloadInvoicePdf(string $zohoInvoiceId): string;
}
