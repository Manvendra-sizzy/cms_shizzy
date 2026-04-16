<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\ZohoInvoice;
use App\Services\Zoho\ZohoBooksService;
use App\Services\Zoho\ZohoInvoiceSyncService;
use Throwable;

class ZohoInvoicesController extends Controller
{
    public function index()
    {
        return view('cms.admin.tools.zoho_invoices.index', [
            'invoices' => ZohoInvoice::query()
                ->orderByDesc('invoice_date')
                ->orderByDesc('id')
                ->paginate(25),
        ]);
    }

    public function sync()
    {
        try {
            $result = app(ZohoInvoiceSyncService::class)->syncInvoices();

            return redirect()
                ->route('admin.zoho_invoices.index')
                ->with('status', "Zoho invoices sync completed. Synced {$result['synced']} invoice records across {$result['pages']} page(s).");
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.zoho_invoices.index')
                ->withErrors([
                    'zoho_invoices_sync' => 'Zoho invoice sync failed: ' . $e->getMessage(),
                ]);
        }
    }

    public function download(ZohoInvoice $zohoInvoice)
    {
        try {
            $pdfBytes = app(ZohoBooksService::class)->downloadInvoicePdf($zohoInvoice->zoho_invoice_id);
            $fileBase = $zohoInvoice->invoice_number ?: $zohoInvoice->zoho_invoice_id;
            $safeFileBase = preg_replace('/[^A-Za-z0-9\-_]+/', '_', (string) $fileBase);
            $filename = trim((string) $safeFileBase, '_') . '.pdf';

            return response($pdfBytes, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . ($filename !== '.pdf' ? $filename : 'zoho-invoice.pdf') . '"',
            ]);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'zoho_invoice_download' => 'Invoice download failed: ' . $e->getMessage(),
            ]);
        }
    }
}
