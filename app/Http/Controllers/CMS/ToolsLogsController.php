<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Models\CmsActivityLog;
use Illuminate\Http\Request;

class ToolsLogsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $method = trim((string) $request->input('method', ''));
        $userEmail = trim((string) $request->input('user_email', ''));
        $fromDate = trim((string) $request->input('from_date', ''));
        $toDate = trim((string) $request->input('to_date', ''));

        $query = CmsActivityLog::query()->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('action_key', 'like', "%{$q}%")
                    ->orWhere('route_name', 'like', "%{$q}%")
                    ->orWhere('url', 'like', "%{$q}%")
                    ->orWhere('ip_address', 'like', "%{$q}%")
                    ->orWhere('user_email', 'like', "%{$q}%")
                    ->orWhere('user_agent', 'like', "%{$q}%")
                    ->orWhere('method', 'like', "%{$q}%");
            });
        }

        if ($userEmail !== '') {
            $query->where('user_email', 'like', "%{$userEmail}%");
        }

        if ($method !== '') {
            $query->where('method', strtoupper($method));
        }

        if ($fromDate !== '') {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate !== '') {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('cms.admin.tools.logs.index', [
            'logs' => $logs,
            'filters' => [
                'q' => $q,
                'method' => $method,
                'user_email' => $userEmail,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }
}

