<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Fee\FeeAutopayDebit;
use App\Models\Admin\Fee\FeeAutopayMandate;
use App\Services\FeeAutopay as Autopay;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * Fee → Autopay: the school's parents' PhonePe UPI AutoPay mandates, and the
 * installments taken on them (App\Services\FeeAutopay). A mandate can be
 * stopped from here too.
 */
class FeeAutopay extends Component
{
    use WireUiActions, WithPagination;

    public string $list   = 'mandates';   // mandates | debits
    public string $status = '';
    public string $search = '';

    public ?int $stopId = null;

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    public function updated($name): void
    {
        if (in_array($name, ['list', 'status', 'search'], true)) {
            $this->resetPage();
        }
        if ($name === 'list') {
            $this->status = '';
        }
    }

    public function confirmStop(int $id): void
    {
        $this->stopId = FeeAutopayMandate::where('organization_id', $this->orgId())->whereKey($id)->value('id');
    }

    public function stop(): void
    {
        $mandate = $this->stopId ? FeeAutopayMandate::where('organization_id', $this->orgId())->find($this->stopId) : null;
        $this->stopId = null;
        if (!$mandate) {
            return;
        }

        try {
            Autopay::cancel($mandate, 'school');
            $this->notification()->success('Autopay stopped for ' . ($mandate->student?->full_name ?? 'the student') . '.');
        } catch (\RuntimeException $e) {
            $this->notification()->error($e->getMessage());
        }
    }

    public function render()
    {
        $orgId = $this->orgId();
        $data  = ['ready' => Autopay::tracked(), 'available' => Autopay::available($orgId), 'rows' => null, 'stats' => []];

        if (!$data['ready']) {
            return view('livewire.admin.fee-autopay', $data);
        }

        $monthStart = now()->startOfMonth();
        $debits = FeeAutopayDebit::where('organization_id', $orgId);
        $data['stats'] = [
            'active'   => FeeAutopayMandate::where('organization_id', $orgId)->where('status', 'active')->count(),
            'pending'  => FeeAutopayMandate::where('organization_id', $orgId)->where('status', 'pending')->count(),
            'paid'     => (float) (clone $debits)->where('status', 'completed')->where('completed_at', '>=', $monthStart)->sum('amount'),
            'upcoming' => (float) (clone $debits)->whereIn('status', ['notifying', 'notified', 'debiting'])->sum('amount'),
            'failed'   => (clone $debits)->where('status', 'failed')->where('updated_at', '>=', $monthStart)->count(),
        ];

        $term = trim($this->search);
        $byStudent = fn ($q) => $q->whereHas('student', fn ($s) => $s->where('full_name', 'like', "%{$term}%")
            ->orWhere('admission_no', 'like', "%{$term}%"));

        if ($this->list === 'debits') {
            $data['rows'] = FeeAutopayDebit::where('organization_id', $orgId)
                ->with('student.standard', 'student.section')
                ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
                ->when($term !== '', $byStudent)
                ->latest('id')
                ->paginate(20);
        } else {
            $data['rows'] = FeeAutopayMandate::where('organization_id', $orgId)
                ->with('student.standard', 'student.section')
                ->withCount(['debits as paid_count' => fn ($q) => $q->where('status', 'completed')])
                ->when($this->status !== '', fn ($q) => $q->where('status', $this->status), fn ($q) => $q->whereIn('status', array_merge(FeeAutopayMandate::LIVE, ['cancelling'])))
                ->when($term !== '', $byStudent)
                ->latest('id')
                ->paginate(20);
        }

        return view('livewire.admin.fee-autopay', $data);
    }
}
