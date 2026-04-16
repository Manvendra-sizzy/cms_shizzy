<?php

namespace App\Services;

use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use App\Modules\HRMS\Leaves\Models\LeavePolicy;
use App\Modules\HRMS\Leaves\Models\LeaveRequest;
use App\Modules\HRMS\Reimbursements\Models\ReimbursementRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramBotService
{
    public function isConfigured(): bool
    {
        $token = (string) config('services.telegram.bot_token', '');
        $chatId = (string) config('services.telegram.chat_id', '');

        return $token !== '' && $chatId !== '';
    }

    public function sendHtml(string $text): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $token = (string) config('services.telegram.bot_token');
        $chatId = (string) config('services.telegram.chat_id');
        $url = 'https://api.telegram.org/bot'.$token.'/sendMessage';

        try {
            $response = Http::timeout(25)->asForm()->post($url, [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if (! $response->successful()) {
                Log::warning('telegram.send_failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $data = $response->json();

            return ($data['ok'] ?? false) === true;
        } catch (\Throwable $e) {
            Log::error('telegram.send_exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Alert admin Telegram when an employee submits a leave request (pending).
     */
    public function sendLeaveAppliedNotice(LeaveRequest $leaveRequest): bool
    {
        if (! $this->isConfigured() || ! config('services.telegram.notify_leave_applied', true)) {
            return false;
        }

        $leaveRequest->loadMissing(['policy', 'employeeProfile.user']);

        $profile = $leaveRequest->employeeProfile;
        $user = $profile?->user;
        $employeeName = $user?->name ?? 'Unknown';
        $employeeId = $profile?->employee_id ?? '—';
        $policy = $leaveRequest->policy;
        $policyLabel = $policy
            ? ($policy->code.' — '.$policy->name)
            : 'Leave';

        $start = $leaveRequest->start_date?->format('Y-m-d') ?? '—';
        $end = $leaveRequest->end_date?->format('Y-m-d') ?? '—';
        $days = rtrim(rtrim(number_format((float) $leaveRequest->days, 2, '.', ''), '0'), '.');

        $reasonRaw = $leaveRequest->reason ? trim((string) $leaveRequest->reason) : '';
        $reason = $reasonRaw === '' ? '—' : Str::limit(preg_replace('/\s+/', ' ', $reasonRaw), 400);

        $lines = [
            '<b>Leave request (pending)</b>',
            'One employee has applied for leave.',
            '',
            '<b>Employee:</b> '.$this->escapeHtml($employeeName).' ('.$this->escapeHtml((string) $employeeId).')',
            '<b>Type of leave:</b> '.$this->escapeHtml($policyLabel),
            '<b>Dates:</b> '.$this->escapeHtml($start).' → '.$this->escapeHtml($end),
            '<b>Working days:</b> '.$this->escapeHtml($days),
            '<b>Reason:</b> '.$this->escapeHtml($reason),
            '<b>Request ID:</b> #'.$leaveRequest->id,
        ];

        $approvalsUrl = route('admin.hrms.leave_approvals.index', [], true);
        if (is_string($approvalsUrl) && $approvalsUrl !== '') {
            $lines[] = '<a href="'.$this->escapeHtml($approvalsUrl).'">Open leave approvals in CMS</a>';
        }

        return $this->sendHtml(implode("\n", $lines));
    }

    /**
     * Demo message for `cms:telegram:test` — uses a real employee from the DB (and their latest leave row for dates/type/days when available).
     */
    public function sendLeaveApplicationTestDemo(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $profile = EmployeeProfile::query()
            ->whereHas('user')
            ->with('user')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('employee_id')
            ->first();

        if (! $profile || ! $profile->user) {
            return $this->sendHtml(
                '<b>cms:telegram:test</b>'."\n"
                .'No active employee with a user account found. Add an employee first, or check <code>employee_profiles.status</code>.'
            );
        }

        $employeeName = $profile->user->name ?? 'Unknown';
        $employeeCode = (string) ($profile->employee_id ?? '—');

        $lastLeave = LeaveRequest::query()
            ->where('employee_profile_id', $profile->id)
            ->with('policy')
            ->orderByDesc('id')
            ->first();

        if ($lastLeave) {
            $lastLeave->loadMissing('policy');
            $pol = $lastLeave->policy;
            $policyLabel = $pol ? ($pol->code.' — '.$pol->name) : 'Leave';
            $start = $lastLeave->start_date?->format('Y-m-d') ?? '—';
            $end = $lastLeave->end_date?->format('Y-m-d') ?? '—';
            $workingDays = rtrim(rtrim(number_format((float) $lastLeave->days, 2, '.', ''), '0'), '.');
            $reasonRaw = $lastLeave->reason ? trim((string) $lastLeave->reason) : '';
            $reason = $reasonRaw === '' ? '—' : Str::limit(preg_replace('/\s+/', ' ', $reasonRaw), 400);
            $sourceRequestId = (int) $lastLeave->id;
        } else {
            $policy = LeavePolicy::query()->where('active', true)->orderBy('name')->first();
            $policyLabel = $policy
                ? ($policy->code.' — '.$policy->name)
                : 'CL — Casual Leave (example)';
            $start = '2026-04-07';
            $end = '2026-04-09';
            $workingDays = '3';
            $reason = '— (no past leave on file — example dates)';
            $sourceRequestId = null;
        }

        $lines = [
            '<b>Test: leave Telegram preview</b>',
            $lastLeave
                ? 'Real employee; leave lines match request <b>#'.$sourceRequestId.'</b> (their most recent).'
                : 'Real employee; leave lines use <b>example</b> dates (no leave history for this person).',
            '',
            '<b>Employee:</b> '.$this->escapeHtml($employeeName).' ('.$this->escapeHtml($employeeCode).')',
            '<b>Type of leave:</b> '.$this->escapeHtml($policyLabel),
            '<b>Dates:</b> '.$this->escapeHtml($start).' → '.$this->escapeHtml($end),
            '<b>Working days:</b> '.$this->escapeHtml($workingDays),
            '<b>Reason:</b> '.$this->escapeHtml($reason),
            '<b>Request ID:</b> '.($lastLeave ? '#'.$sourceRequestId.' <i>(from DB, test only)</i>' : '#0 <i>(demo)</i>'),
            '',
            '<i>Sent by <code>php artisan cms:telegram:test</code> — not a new application.</i>',
        ];

        $approvalsUrl = route('admin.hrms.leave_approvals.index', [], true);
        if (is_string($approvalsUrl) && $approvalsUrl !== '') {
            $lines[] = '<a href="'.$this->escapeHtml($approvalsUrl).'">Open leave approvals in CMS</a>';
        }

        return $this->sendHtml(implode("\n", $lines));
    }

    /**
     * Alert admin Telegram when an employee submits a reimbursement request (pending).
     */
    public function sendReimbursementAppliedNotice(ReimbursementRequest $reimbursementRequest): bool
    {
        if (! $this->isConfigured() || ! config('services.telegram.notify_reimbursement_applied', true)) {
            return false;
        }

        $reimbursementRequest->loadMissing(['employeeProfile.user']);

        $profile = $reimbursementRequest->employeeProfile;
        $user = $profile?->user;
        $employeeName = $user?->name ?? 'Unknown';
        $employeeId = $profile?->employee_id ?? '—';
        $title = trim((string) $reimbursementRequest->title);
        $title = $title === '' ? 'Reimbursement' : $title;
        $expenseDate = $reimbursementRequest->expense_date?->format('Y-m-d') ?? '—';
        $amount = number_format((float) $reimbursementRequest->amount, 2, '.', '');
        $category = $reimbursementRequest->category ? trim((string) $reimbursementRequest->category) : '';
        $descRaw = $reimbursementRequest->description ? trim((string) $reimbursementRequest->description) : '';
        $desc = $descRaw === '' ? '—' : Str::limit(preg_replace('/\s+/', ' ', $descRaw), 400);

        $lines = [
            '<b>Reimbursement request (pending)</b>',
            'An employee submitted a reimbursement claim.',
            '',
            '<b>Employee:</b> '.$this->escapeHtml($employeeName).' ('.$this->escapeHtml((string) $employeeId).')',
            '<b>Title:</b> '.$this->escapeHtml($title),
        ];

        if ($category !== '') {
            $lines[] = '<b>Category:</b> '.$this->escapeHtml($category);
        }

        $lines[] = '<b>Expense date:</b> '.$this->escapeHtml($expenseDate);
        $lines[] = '<b>Amount:</b> '.$this->escapeHtml($amount);
        $lines[] = '<b>Description:</b> '.$this->escapeHtml($desc);
        $lines[] = '<b>Request ID:</b> #'.$reimbursementRequest->id;

        $approvalsUrl = route('admin.hrms.reimbursement_approvals.index', [], true);
        if (is_string($approvalsUrl) && $approvalsUrl !== '') {
            $lines[] = '<a href="'.$this->escapeHtml($approvalsUrl).'">Open reimbursement approvals in CMS</a>';
        }

        return $this->sendHtml(implode("\n", $lines));
    }

    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
