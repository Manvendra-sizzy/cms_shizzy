<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\ZohoClient;

class ProjectClientsController extends Controller
{
    public function index()
    {
        $zohoClients = ZohoClient::query()
            ->where(function ($query) {
                $query->whereNull('contact_type')
                    ->orWhereIn('contact_type', ['customer', 'customer_vendor']);
            })
            ->orderByRaw('COALESCE(NULLIF(contact_name, ""), NULLIF(company_name, ""), NULLIF(first_name, ""), zoho_contact_id) asc')
            ->paginate(25, ['*'], 'zoho_page');

        return view('projects.clients.index', [
            'zohoClients' => $zohoClients,
        ]);
    }
}
