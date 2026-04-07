<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\ZohoClient;
use App\Services\Zoho\ZohoClientSyncService;
use Throwable;

class ZohoClientsController extends Controller
{
    public function __construct(private readonly ZohoClientSyncService $syncService)
    {
    }

    public function index()
    {
        return view('cms.admin.tools.zoho_clients.index', [
            'clients' => ZohoClient::query()
                ->orderByDesc('last_synced_at')
                ->orderByDesc('id')
                ->paginate(25),
        ]);
    }

    public function sync()
    {
        try {
            $result = $this->syncService->syncClients();

            return redirect()
                ->route('admin.zoho_clients.index')
                ->with('status', "Zoho clients sync completed. Synced {$result['synced']} customer records across {$result['pages']} page(s).");
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.zoho_clients.index')
                ->withErrors([
                    'zoho_clients_sync' => 'Zoho client sync failed: ' . $e->getMessage(),
                ]);
        }
    }
}
