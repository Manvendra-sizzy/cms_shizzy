<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Modules\HRMS\Onboarding\Models\EmployeeOnboarding;
use Illuminate\Http\Request;

class ZohoSignWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = (string) config('services.zoho_sign.webhook_secret');
        if ($secret !== '' && ! hash_equals($secret, (string) $request->header('X-Zoho-Sign-Secret'))) {
            abort(403);
        }

        $requestId = (string) data_get($request->all(), 'requests.request_id', $request->input('request_id', ''));
        if ($requestId === '') {
            return response()->json(['ok' => true]);
        }

        $status = (string) data_get($request->all(), 'requests.request_status', $request->input('request_status', ''));

        $onboarding = EmployeeOnboarding::query()->where('zoho_sign_request_id', $requestId)->first();
        if (! $onboarding) {
            return response()->json(['ok' => true]);
        }

        $isSigned = in_array(strtolower($status), ['completed', 'signed'], true);
        $onboarding->update([
            'zoho_sign_status' => $status,
            'zoho_sign_signed_at' => $isSigned ? now() : $onboarding->zoho_sign_signed_at,
            'zoho_sign_completed_at' => $isSigned ? now() : $onboarding->zoho_sign_completed_at,
            'zoho_sign_meta' => $request->all(),
            'status' => $isSigned ? EmployeeOnboarding::STATUS_AGREEMENT_SIGNED : $onboarding->status,
        ]);

        return response()->json(['ok' => true]);
    }
}

