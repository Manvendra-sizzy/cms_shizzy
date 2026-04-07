<?php

namespace App\Modules\CMS\Infrastructure;

use App\Models\ZohoInvoice;
use App\Modules\Projects\Contracts\BillingInvoiceGatewayContract;
use App\Services\Zoho\ZohoBooksService;
use Illuminate\Support\Collection;

class ZohoBillingInvoiceGateway implements BillingInvoiceGatewayContract
{
    public function __construct(
        private readonly ZohoBooksService $zohoBooksService
    ) {
    }

    public function getProjectInvoices(string $projectCode): Collection
    {
        return ZohoInvoice::query()
            ->where('project_id', $projectCode)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->get();
    }

    public function getInvoicesForProjectCodesBetween(array $projectCodes, string $startDate, string $endDate): Collection
    {
        $query = ZohoInvoice::query();

        if (!empty($projectCodes)) {
            $query->whereIn('project_id', $projectCodes);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->get();
    }

    public function downloadInvoicePdf(string $zohoInvoiceId): string
    {
        return $this->zohoBooksService->downloadInvoicePdf($zohoInvoiceId);
    }
}
