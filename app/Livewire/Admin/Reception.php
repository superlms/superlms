<?php

namespace App\Livewire\Admin;

use App\Models\Admin\ReceptionVisitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * More → Reception: the school's visitor book. Who came — their name, mobile
 * and email — when, whom they came to meet, what for (a purpose, and what the
 * work was), and what time they left. A visitor still inside is marked out
 * with one click when they go. The book is listed newest first, by day if a
 * date is picked, and can be narrowed to those inside or gone, or searched by
 * name, mobile or whom they met.
 */
class Reception extends Component
{
    use WithPagination, WireUiActions;

    // ─── The list's filters ──────────────────────────────────────────────────
    public string $filterDate   = '';
    public string $filterStatus = '';   // '' | inside | left
    public string $search       = '';

    // ─── Add / edit ──────────────────────────────────────────────────────────
    public bool   $formOpen   = false;
    public ?int   $editingId  = null;
    public string $name       = '';
    public string $mobile     = '';
    public string $email      = '';
    public string $whomToMeet = '';
    public string $purpose    = '';
    public string $details    = '';
    public string $inDate     = '';
    public string $inTime     = '';
    public string $outTime    = '';

    // ─── View / delete ───────────────────────────────────────────────────────
    public ?int $viewingId  = null;
    public ?int $deletingId = null;

    private function orgId(): int
    {
        return (int) Auth::user()?->organization_id;
    }

    private function own()
    {
        return ReceptionVisitor::where('organization_id', $this->orgId());
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['filterDate', 'filterStatus', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['filterDate', 'filterStatus', 'search']);
        $this->resetPage();
    }

    public function showToday(): void
    {
        $this->filterDate = now()->toDateString();
        $this->resetPage();
    }

    public function showInside(): void
    {
        $this->filterStatus = 'inside';
        $this->resetPage();
    }

    public function openAdd(): void
    {
        $this->resetForm();
        // Comes in now, unless the admin says otherwise.
        $this->inDate = now()->toDateString();
        $this->inTime = now()->format('H:i');
        $this->formOpen = true;
    }

    public function openEdit(int $id): void
    {
        $v = $this->own()->find($id);
        if (!$v) {
            return;
        }
        $this->resetForm();
        $this->editingId  = $v->id;
        $this->name       = (string) $v->name;
        $this->mobile     = (string) $v->mobile;
        $this->email      = (string) $v->email;
        $this->whomToMeet = (string) $v->whom_to_meet;
        $this->purpose    = (string) $v->purpose;
        $this->details    = (string) $v->details;
        $this->inDate     = $v->in_at?->toDateString() ?? '';
        $this->inTime     = $v->in_at?->format('H:i') ?? '';
        $this->outTime    = $v->out_at?->format('H:i') ?? '';
        $this->viewingId  = null;
        $this->formOpen   = true;
    }

    public function closeForm(): void
    {
        $this->formOpen = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'mobile', 'email', 'whomToMeet', 'purpose', 'details', 'inDate', 'inTime', 'outTime']);
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->validate([
            'name'       => 'required|string|max:120',
            'mobile'     => ['required', 'regex:/^\+?[0-9][0-9 \-]{6,18}$/'],
            'email'      => 'nullable|email|max:150',
            'whomToMeet' => 'nullable|string|max:120',
            'purpose'    => 'required|in:' . implode(',', ReceptionVisitor::PURPOSES),
            'details'    => ($this->purpose === 'Other' ? 'required' : 'nullable') . '|string|max:1000',
            'inDate'     => 'required|date_format:Y-m-d',
            'inTime'     => 'required|date_format:H:i',
            'outTime'    => 'nullable|date_format:H:i',
        ], [
            'name.required'     => 'Please enter the visitor\'s name.',
            'mobile.required'   => 'Please enter a mobile number.',
            'mobile.regex'      => 'Enter a valid mobile number.',
            'email.email'       => 'Enter a valid email, or leave it empty.',
            'purpose.required'  => 'Please choose what they came for.',
            'purpose.in'        => 'Please choose what they came for.',
            'details.required'  => 'Please say what the work was.',
            'inDate.required'   => 'Please enter the date they came.',
            'inTime.required'   => 'Please enter the time they came.',
        ]);

        $in  = Carbon::createFromFormat('Y-m-d H:i', $this->inDate . ' ' . $this->inTime);
        $out = $this->outTime !== '' ? Carbon::createFromFormat('Y-m-d H:i', $this->inDate . ' ' . $this->outTime) : null;
        if ($out && $out->lt($in)) {
            $this->addError('outTime', 'The time they left has to be after the time they came.');

            return;
        }

        $visitor = $this->editingId
            ? $this->own()->find($this->editingId)
            : new ReceptionVisitor(['organization_id' => $this->orgId(), 'added_by' => Auth::id()]);
        if (!$visitor) {
            $this->closeForm();

            return;
        }

        $visitor->fill([
            'name'         => trim($this->name),
            'mobile'       => trim($this->mobile),
            'email'        => trim($this->email) ?: null,
            'whom_to_meet' => trim($this->whomToMeet) ?: null,
            'purpose'      => $this->purpose,
            'details'      => trim($this->details) ?: null,
            'in_at'        => $in,
            'out_at'       => $out,
        ])->save();

        $this->notification()->success($this->editingId ? 'Visitor updated' : 'Visitor added');
        $this->formOpen = false;
        $this->resetForm();
    }

    /** They leave now (never before they came). */
    public function markOut(int $id): void
    {
        $v = $this->own()->whereNull('out_at')->find($id);
        if (!$v) {
            return;
        }
        $v->update(['out_at' => now()->max($v->in_at)]);
        $this->notification()->success($v->name . ' marked out at ' . $v->out_at->format('h:i A'));
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function deleteVisitor(): void
    {
        $v = $this->own()->find($this->deletingId);
        $this->deletingId = null;
        if (!$v) {
            return;
        }
        $v->delete();
        if ($this->viewingId === $v->id) {
            $this->viewingId = null;
        }
        $this->notification()->success('Visitor deleted');
    }

    public function render()
    {
        $term = trim($this->search);

        $visitors = $this->own()
            ->when($this->filterDate !== '', fn ($q) => $q->whereDate('in_at', $this->filterDate))
            ->when($this->filterStatus === 'inside', fn ($q) => $q->whereNull('out_at'))
            ->when($this->filterStatus === 'left', fn ($q) => $q->whereNotNull('out_at'))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%")
                ->orWhere('whom_to_meet', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->orderByDesc('in_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.reception', [
            'visitors' => $visitors,
            'today'    => $this->own()->whereDate('in_at', now()->toDateString())->count(),
            'inside'   => $this->own()->whereNull('out_at')->count(),
            'total'    => $this->own()->count(),
            'viewing'  => $this->viewingId ? $this->own()->with('addedBy:id,name')->find($this->viewingId) : null,
        ]);
    }
}
