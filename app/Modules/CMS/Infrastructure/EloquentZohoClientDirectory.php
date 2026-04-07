<?php

namespace App\Modules\CMS\Infrastructure;

use App\Models\ZohoClient;
use App\Modules\Projects\Contracts\ZohoClientDirectoryContract;
use Illuminate\Support\Collection;

class EloquentZohoClientDirectory implements ZohoClientDirectoryContract
{
    public function getSelectableClients(): Collection
    {
        return ZohoClient::query()
            ->where(function ($query) {
                $query->whereNull('contact_type')
                    ->orWhereIn('contact_type', ['customer', 'customer_vendor']);
            })
            ->orderByRaw('COALESCE(NULLIF(contact_name, ""), NULLIF(company_name, ""), NULLIF(first_name, ""), zoho_contact_id) asc')
            ->get();
    }

    public function existsById(int $id): bool
    {
        return ZohoClient::query()->whereKey($id)->exists();
    }
}
