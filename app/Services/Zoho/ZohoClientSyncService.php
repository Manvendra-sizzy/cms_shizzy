<?php

namespace App\Services\Zoho;

use App\Models\ZohoClient;
use Carbon\CarbonImmutable;
use RuntimeException;

class ZohoClientSyncService
{
    public function __construct(private readonly ZohoBooksService $zohoBooksService)
    {
    }

    /**
     * @return array{synced:int,pages:int}
     */
    public function syncClients(): array
    {
        $page = 1;
        $perPage = 200;
        $synced = 0;
        $pages = 0;

        do {
            $payload = $this->zohoBooksService->fetchContactsPage($page, $perPage);
            $pages++;

            $contacts = $payload['contacts'] ?? [];

            if (! is_array($contacts)) {
                throw new RuntimeException('Zoho contacts payload is missing contacts array.');
            }

            foreach ($contacts as $contact) {
                if (! is_array($contact)) {
                    continue;
                }

                if (($contact['contact_type'] ?? null) !== 'customer') {
                    continue;
                }

                $zohoContactId = (string) ($contact['contact_id'] ?? '');

                if ($zohoContactId === '') {
                    continue;
                }

                ZohoClient::updateOrCreate(
                    ['zoho_contact_id' => $zohoContactId],
                    [
                        'contact_name' => $this->toNullableString($contact['contact_name'] ?? null),
                        'company_name' => $this->toNullableString($contact['company_name'] ?? null),
                        'first_name' => $this->toNullableString($contact['first_name'] ?? null),
                        'last_name' => $this->toNullableString($contact['last_name'] ?? null),
                        'email' => $this->toNullableString($contact['email'] ?? null),
                        'phone' => $this->toNullableString($contact['phone'] ?? null),
                        'mobile' => $this->toNullableString($contact['mobile'] ?? null),
                        'contact_type' => $this->toNullableString($contact['contact_type'] ?? null),
                        'status' => $this->toNullableString($contact['status'] ?? null),
                        'gst_no' => $this->toNullableString($contact['gst_no'] ?? null),
                        'gst_treatment' => $this->toNullableString($contact['gst_treatment'] ?? null),
                        'place_of_contact' => $this->toNullableString($contact['place_of_contact'] ?? null),
                        'outstanding_receivable_amount' => (float) ($contact['outstanding_receivable_amount'] ?? 0),
                        'raw_payload' => $contact,
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
