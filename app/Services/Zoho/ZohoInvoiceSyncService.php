<?php

namespace App\Services\Zoho;

use App\Models\ZohoInvoice;
use Carbon\CarbonImmutable;
use RuntimeException;

class ZohoInvoiceSyncService
{
    public function __construct(private readonly ZohoBooksService $zohoBooksService)
    {
    }

    /**
     * @return array{synced:int,pages:int}
     */
    public function syncInvoices(): array
    {
        $page = 1;
        $perPage = 200;
        $synced = 0;
        $pages = 0;

        do {
            $payload = $this->zohoBooksService->fetchInvoicesPage($page, $perPage);
            $pages++;

            $invoices = $payload['invoices'] ?? [];

            if (! is_array($invoices)) {
                throw new RuntimeException('Zoho invoices payload is missing invoices array.');
            }

            foreach ($invoices as $invoice) {
                if (! is_array($invoice)) {
                    continue;
                }

                $zohoInvoiceId = $this->toNullableString($invoice['invoice_id'] ?? null);
                if ($zohoInvoiceId === null) {
                    continue;
                }

                ZohoInvoice::updateOrCreate(
                    ['zoho_invoice_id' => $zohoInvoiceId],
                    [
                        'zoho_customer_id' => $this->toNullableString($invoice['customer_id'] ?? null),
                        'project_id' => $this->zohoBooksService->extractInvoiceProjectId($invoice),
                        'invoice_number' => $this->toNullableString($invoice['invoice_number'] ?? null),
                        'status' => $this->toNullableString($invoice['status'] ?? null),
                        'total' => (float) ($invoice['total'] ?? 0),
                        'balance' => (float) ($invoice['balance'] ?? 0),
                        'invoice_date' => $this->toNullableString($invoice['date'] ?? null),
                        'due_date' => $this->toNullableString($invoice['due_date'] ?? null),
                        'raw_payload' => $invoice,
                        'last_synced_at' => CarbonImmutable::now(),
                    ]
                );

                $synced++;
            }

            $pageContext = $payload['page_context'] ?? [];
            $hasMorePage = (bool) ($pageContext['has_more_page'] ?? false);
            $page++;
        } while ($hasMorePage);

        return [
            'synced' => $synced,
            'pages' => $pages,
        ];
    }

    private function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
