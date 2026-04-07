<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectRevenueInvoice;
use App\Modules\Projects\Models\ProjectRevenuePayment;
use App\Modules\Projects\Models\ProjectRevenueStream;
use App\Modules\Projects\Models\ProjectReimbursement;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProjectRevenueController extends Controller
{
    private const STREAM_TYPES = 'retainer,usage,reimbursement,fixed,installment,lifetime';

    public function editStream(Project $project, ProjectRevenueStream $stream)
    {
        abort_unless((int) $stream->project_id === (int) $project->id, 404);

        return view('projects.revenue.streams.edit', [
            'project' => $project,
            'stream' => $stream,
        ]);
    }

    public function updateStream(Request $request, Project $project, ProjectRevenueStream $stream)
    {
        abort_unless((int) $stream->project_id === (int) $project->id, 404);

        $validated = $this->validateStreamPayload($request);
        $type = $validated['type'];
        $name = trim((string) ($validated['name'] ?? ''));

        $payload = $this->buildStreamPayload($validated, $type);
        $payload['name'] = $name;

        $stream->update($payload);

        return redirect()->route('projects.finances.show', $project)->with('status', 'Revenue stream updated.');
    }

    public function storeStream(Request $request, Project $project)
    {
        if ($project->is_internal) {
            return back()->withErrors(['stream' => 'Revenue streams are not used for internal projects.']);
        }

        $validated = $this->validateStreamPayload($request);
        $type = $validated['type'];
        $name = trim((string) ($validated['name'] ?? ''));

        $payload = $this->buildStreamPayload($validated, $type);
        $payload['project_id'] = $project->id;
        $payload['name'] = $name;
        $payload['active'] = true;
        $payload['closed_at'] = null;
        $payload['closed_remark'] = null;

        ProjectRevenueStream::query()->create($payload);

        return back()->with('status', 'Revenue stream added.');
    }

    public function closeStream(Request $request, Project $project, ProjectRevenueStream $stream)
    {
        abort_unless((int) $stream->project_id === (int) $project->id, 404);

        if (!$stream->active) {
            return back()->withErrors(['stream_close' => 'This revenue stream is already closed.']);
        }

        $request->session()->put('pending_close_stream_id', (int) $stream->id);

        $data = $request->validate([
            'effective_date' => ['required', 'date'],
            'remark' => ['required', 'string', 'max:4000'],
        ]);

        $stream->update([
            'active' => false,
            'closed_at' => Carbon::parse($data['effective_date'])->toDateString(),
            'closed_remark' => $data['remark'],
        ]);

        $request->session()->forget('pending_close_stream_id');

        return back()->with('status', 'Revenue stream marked as closed.');
    }

    private function validateStreamPayload(Request $request): array
    {
        $type = (string) $request->input('type');
        $expectedValueRules = ['numeric', 'min:0'];
        if ($type === 'reimbursement') {
            $expectedValueRules[] = 'nullable';
        } else {
            $expectedValueRules[] = 'required';
        }

        $base = [
            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:' . self::STREAM_TYPES],
            'expected_total_value' => $expectedValueRules,
            'start_date' => ['required', 'date'],
            'notes' => ['required', 'string', 'max:8000'],
            'billing_cycle' => ['nullable', 'string', 'max:32'],
            'next_billing_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'installment_value' => ['nullable', 'numeric', 'min:0'],
            'installment_count' => ['nullable', 'integer', 'min:1'],
            'rate_per_unit' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'calculated_amount' => ['nullable', 'numeric', 'min:0'],
        ];

        $request->validate($base);

        if (in_array($type, ['retainer', 'usage', 'reimbursement'], true)) {
            $request->validate([
                'billing_cycle' => ['required', 'string', 'in:one_time,monthly,quarterly,annual,custom'],
                'next_billing_date' => ['required', 'date'],
            ]);
        }

        if ($type === 'fixed') {
            $request->validate([
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ]);
        }

        if ($type === 'installment') {
            $request->validate([
                'billing_cycle' => ['required', 'string', 'in:one_time,monthly,quarterly,lifetime,custom'],
                'installment_value' => ['required', 'numeric', 'min:0'],
                'installment_count' => ['required', 'integer', 'min:1'],
                'next_billing_date' => ['required', 'date'],
            ]);
        }

        if ($type === 'lifetime') {
            $request->validate([
                'billing_cycle' => ['nullable', 'string', 'max:32'],
                'next_billing_date' => ['nullable', 'date'],
            ]);
        }

        return $request->only(array_keys($base));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildStreamPayload(array $data, string $type): array
    {
        $billingCycle = $data['billing_cycle'] ?? null;
        $nextBilling = $data['next_billing_date'] ?? null;
        $endDate = null;
        $ratePerUnit = isset($data['rate_per_unit']) ? (float) $data['rate_per_unit'] : null;
        $quantity = isset($data['quantity']) ? (float) $data['quantity'] : null;

        if ($type === 'fixed') {
            $billingCycle = 'one_time';
            $nextBilling = $data['end_date'] ?? null;
            $endDate = $data['end_date'] ?? null;
        } elseif ($type === 'installment') {
            $ratePerUnit = (float) ($data['installment_value'] ?? 0);
            $quantity = (float) ($data['installment_count'] ?? 0);
        } elseif ($type === 'lifetime') {
            $billingCycle = 'lifetime';
            $nextBilling = $data['next_billing_date'] ?: $data['start_date'];
        }

        return [
            'type' => $type,
            'billing_cycle' => $billingCycle,
            'expected_total_value' => (float) ($data['expected_total_value'] ?? 0),
            'rate_per_unit' => $ratePerUnit,
            'quantity' => $quantity,
            'calculated_amount' => $data['calculated_amount'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'next_billing_date' => $nextBilling,
            'end_date' => $endDate,
            'notes' => $data['notes'] ?? null,
        ];
    }

    public function editInvoice(Project $project, ProjectRevenueStream $stream, ProjectRevenueInvoice $invoice)
    {
        abort_unless((int) $stream->project_id === (int) $project->id, 404);
        abort_unless((int) $invoice->project_revenue_stream_id === (int) $stream->id, 404);

        return view('projects.revenue.invoices.edit', [
            'project' => $project,
            'stream' => $stream,
            'invoice' => $invoice,
        ]);
    }

    public function updateInvoice(Request $request, Project $project, ProjectRevenueStream $stream, ProjectRevenueInvoice $invoice)
    {
        abort_unless((int) $stream->project_id === (int) $project->id, 404);
        abort_unless((int) $invoice->project_revenue_stream_id === (int) $stream->id, 404);

        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:64'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:draft,sent,paid,cancelled'],
            'notes' => ['required', 'string', 'max:8000'],
            'invoice_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $invoiceFilePath = $invoice->invoice_file_path;
        if ($request->hasFile('invoice_pdf')) {
            $invoiceFilePath = $request->file('invoice_pdf')->store('projects/revenue-invoices', 'public');
        }

        $invoice->update([
            'invoice_number' => $data['invoice_number'] ?? null,
            'invoice_date' => $data['invoice_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'amount' => (float) $data['amount'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'invoice_file_path' => $invoiceFilePath,
        ]);

        // If invoice is set to paid but doesn't have enough payments, keep status as paid (manual override).
        // If invoice has enough payments, ensure paid (auto compliance).
        $this->autoMarkInvoicePaidIfCovered($invoice);

        return redirect()->route('projects.show', $project)->with('status', 'Invoice updated.');
    }

    public function storeInvoice(Request $request, Project $project, ProjectRevenueStream $stream)
    {
        abort_unless($stream->project_id === $project->id, 404);

        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:64'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:draft,sent,paid,cancelled'],
            'notes' => ['required', 'string', 'max:8000'],
            'invoice_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $invoiceFilePath = null;
        if ($request->hasFile('invoice_pdf')) {
            $invoiceFilePath = $request->file('invoice_pdf')->store('projects/revenue-invoices', 'public');
        }

        ProjectRevenueInvoice::query()->create([
            'project_revenue_stream_id' => $stream->id,
            'invoice_number' => $data['invoice_number'] ?? null,
            'invoice_date' => $data['invoice_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'amount' => (float) $data['amount'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'invoice_file_path' => $invoiceFilePath,
        ]);

        return back()->with('status', 'Invoice entry added.');
    }

    public function storePayment(Request $request, Project $project, ProjectRevenueStream $stream)
    {
        abort_unless($stream->project_id === $project->id, 404);

        $data = $request->validate([
            'project_revenue_invoice_id' => ['required', 'exists:project_revenue_invoices,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'string', 'max:32'],
            'reference' => ['required', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:8000'],
        ]);

        $invoiceId = $data['project_revenue_invoice_id'] ?? null;
        $inv = ProjectRevenueInvoice::query()->find($invoiceId);
        if (!$inv || (int) $inv->project_revenue_stream_id !== (int) $stream->id) {
            return back()->withErrors(['project_revenue_invoice_id' => 'Invalid invoice selected for this stream.']);
        }

        $payment = ProjectRevenuePayment::query()->create([
            'project_revenue_stream_id' => $stream->id,
            'project_revenue_invoice_id' => $invoiceId,
            'payment_date' => $data['payment_date'] ?? null,
            'amount' => (float) $data['amount'],
            'method' => $data['method'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $invoice = ProjectRevenueInvoice::query()->find($invoiceId);
        if ($invoice) {
            $this->autoMarkInvoicePaidIfCovered($invoice);
        }

        return back()->with('status', 'Payment entry added.');
    }

    public function storeReimbursement(Request $request, Project $project)
    {
        $data = $request->validate([
            'project_revenue_stream_id' => ['nullable', 'exists:project_revenue_streams,id'],
            'spent_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'spend_amount' => ['required', 'numeric', 'min:0'],
            'markup_type' => ['required', 'string', 'in:percent,fixed'],
            'markup_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:not_billed,billed,recovered'],
            'notes' => ['nullable', 'string', 'max:8000'],
        ]);

        $streamId = $data['project_revenue_stream_id'] ?? null;
        if ($streamId) {
            $stream = ProjectRevenueStream::query()->find($streamId);
            if (!$stream || (int) $stream->project_id !== (int) $project->id) {
                return back()->withErrors(['project_revenue_stream_id' => 'Invalid stream selected for this project.']);
            }
        }

        $spend = (float) $data['spend_amount'];
        $markupType = $data['markup_type'];
        $markupVal = (float) ($data['markup_value'] ?? 0);

        $final = $spend;
        if ($markupType === 'percent') {
            $final = $spend + ($spend * ($markupVal / 100.0));
        } else {
            $final = $spend + $markupVal;
        }
        $final = round($final, 2);

        ProjectReimbursement::query()->create([
            'project_id' => $project->id,
            'project_revenue_stream_id' => $streamId,
            'spent_date' => $data['spent_date'] ?? null,
            'description' => $data['description'] ?? null,
            'spend_amount' => $spend,
            'markup_type' => $markupType,
            'markup_value' => $markupVal,
            'final_billable_amount' => $final,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', 'Reimbursement logged.');
    }

    public function editReimbursement(Project $project, ProjectReimbursement $reimbursement)
    {
        abort_unless((int) $reimbursement->project_id === (int) $project->id, 404);

        $streams = ProjectRevenueStream::query()
            ->where('project_id', $project->id)
            ->orderByDesc('id')
            ->get();

        return view('projects.revenue.reimbursements.edit', [
            'project' => $project,
            'reimbursement' => $reimbursement,
            'streams' => $streams,
        ]);
    }

    public function updateReimbursement(Request $request, Project $project, ProjectReimbursement $reimbursement)
    {
        abort_unless((int) $reimbursement->project_id === (int) $project->id, 404);

        $data = $request->validate([
            'project_revenue_stream_id' => ['nullable', 'exists:project_revenue_streams,id'],
            'spent_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'spend_amount' => ['required', 'numeric', 'min:0'],
            'markup_type' => ['required', 'string', 'in:percent,fixed'],
            'markup_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:not_billed,billed,recovered'],
            'notes' => ['nullable', 'string', 'max:8000'],
        ]);

        $streamId = $data['project_revenue_stream_id'] ?? null;
        if ($streamId) {
            $stream = ProjectRevenueStream::query()->find($streamId);
            if (!$stream || (int) $stream->project_id !== (int) $project->id) {
                return back()->withErrors(['project_revenue_stream_id' => 'Invalid stream selected for this project.']);
            }
        }

        $spend = (float) $data['spend_amount'];
        $markupType = $data['markup_type'];
        $markupVal = (float) ($data['markup_value'] ?? 0);

        $final = $spend;
        if ($markupType === 'percent') {
            $final = $spend + ($spend * ($markupVal / 100.0));
        } else {
            $final = $spend + $markupVal;
        }
        $final = round($final, 2);

        $reimbursement->update([
            'project_revenue_stream_id' => $streamId,
            'spent_date' => $data['spent_date'] ?? null,
            'description' => $data['description'] ?? null,
            'spend_amount' => $spend,
            'markup_type' => $markupType,
            'markup_value' => $markupVal,
            'final_billable_amount' => $final,
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('projects.show', $project)->with('status', 'Reimbursement updated.');
    }

    private function autoMarkInvoicePaidIfCovered(ProjectRevenueInvoice $invoice): void
    {
        if ($invoice->status === 'cancelled') {
            return;
        }

        $paid = (float) ProjectRevenuePayment::query()
            ->where('project_revenue_invoice_id', $invoice->id)
            ->sum('amount');

        $amount = (float) ($invoice->amount ?? 0);
        if ($amount > 0 && $paid + 0.001 >= $amount) {
            if ($invoice->status !== 'paid') {
                $invoice->update(['status' => 'paid']);
            }
        }
    }
}

