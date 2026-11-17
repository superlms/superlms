<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\PicksStudent;
use App\Models\Admin\StudentGatePass;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * More → Gatepass: a student let out of school during the day. The admin picks
 * the student by class, section and name, says why they are going, when, and
 * who is taking them (name, relation, mobile — the father's filled in from the
 * student, to change if someone else came). Each pass gets the school's next
 * number (GP-0001 …) and prints as a slip for the gate. A student who comes
 * back is marked returned. Passes are listed newest first, by day, class or
 * section, or found by the student's name, admission no. or the pass number.
 */
class Gatepass extends Component
{
    use PicksStudent, WithPagination, WireUiActions;

    // ─── The list's filters ──────────────────────────────────────────────────
    public string $filterDate     = '';
    public string $filterStandard = '';
    public string $filterSection  = '';
    public string $search         = '';

    // ─── Add / edit ──────────────────────────────────────────────────────────
    public bool   $formOpen         = false;
    public ?int   $editingId        = null;
    public string $reason           = '';
    public string $outDate          = '';
    public string $outTime          = '';
    public string $returnTime       = '';
    public string $pickedByName     = '';
    public string $pickedByRelation = '';
    public string $pickedByMobile   = '';
    public string $remarks          = '';

    // ─── View / delete ───────────────────────────────────────────────────────
    public ?int $viewingId  = null;
    public ?int $deletingId = null;

    private function own()
    {
        return StudentGatePass::where('organization_id', $this->orgId());
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['filterDate', 'filterSection', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function updatedFilterStandard(): void
    {
        $this->filterSection = '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterDate', 'filterStandard', 'filterSection', 'search']);
        $this->resetPage();
    }

    public function showToday(): void
    {
        $this->filterDate = now()->toDateString();
        $this->resetPage();
    }

    /** A student picked: who usually takes them — the father, on the student's phone — unless already typed. */
    public function updatedStudentId(): void
    {
        $student = $this->pickedStudent();
        if (!$student || trim($this->pickedByName) !== '') {
            return;
        }
        if (trim((string) $student->father_name) !== '') {
            $this->pickedByName     = (string) $student->father_name;
            $this->pickedByRelation = 'Father';
        }
        if (trim($this->pickedByMobile) === '' && trim((string) $student->phone) !== '') {
            $this->pickedByMobile = (string) $student->phone;
        }
    }

    public function openAdd(): void
    {
        $this->resetForm();
        $this->outDate  = now()->toDateString();
        $this->outTime  = now()->format('H:i');
        $this->formOpen = true;
    }

    public function openEdit(int $id): void
    {
        $p = $this->own()->find($id);
        if (!$p) {
            return;
        }
        $this->resetForm();
        $this->editingId        = $p->id;
        $this->pickStudent($p->standard_id, $p->section_id, $p->student_detail_id);
        $this->reason           = (string) $p->reason;
        $this->outDate          = $p->out_at?->toDateString() ?? '';
        $this->outTime          = $p->out_at?->format('H:i') ?? '';
        $this->returnTime       = $p->returned_at?->format('H:i') ?? '';
        $this->pickedByName     = (string) $p->picked_by_name;
        $this->pickedByRelation = (string) $p->picked_by_relation;
        $this->pickedByMobile   = (string) $p->picked_by_mobile;
        $this->remarks          = (string) $p->remarks;
        $this->viewingId        = null;
        $this->formOpen         = true;
    }

    public function closeForm(): void
    {
        $this->formOpen = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'reason', 'outDate', 'outTime', 'returnTime', 'pickedByName', 'pickedByRelation',
                      'pickedByMobile', 'remarks', 'standardId', 'sectionId', 'studentId']);
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->validate($this->pickerRules() + [
            'reason'           => 'required|string|max:255',
            'outDate'          => 'required|date_format:Y-m-d',
            'outTime'          => 'required|date_format:H:i',
            'returnTime'       => 'nullable|date_format:H:i',
            'pickedByName'     => 'required|string|max:120',
            'pickedByRelation' => 'nullable|in:' . implode(',', StudentGatePass::RELATIONS),
            'pickedByMobile'   => ['nullable', 'regex:/^\+?[0-9][0-9 \-]{6,18}$/'],
            'remarks'          => 'nullable|string|max:1000',
        ], $this->pickerMessages() + [
            'reason.required'       => 'Please say why the student is going.',
            'outDate.required'      => 'Please enter the date.',
            'outTime.required'      => 'Please enter the time they go out.',
            'pickedByName.required' => 'Please enter who is taking the student.',
            'pickedByMobile.regex'  => 'Enter a valid mobile number.',
        ]);

        $student = $this->pickedStudent();
        if (!$student) {
            $this->addError('studentId', 'Please select a student of this class and section.');

            return;
        }

        $out  = Carbon::createFromFormat('Y-m-d H:i', $this->outDate . ' ' . $this->outTime);
        $back = $this->returnTime !== '' ? Carbon::createFromFormat('Y-m-d H:i', $this->outDate . ' ' . $this->returnTime) : null;
        if ($back && $back->lt($out)) {
            $this->addError('returnTime', 'The time they came back has to be after the time they went out.');

            return;
        }

        $pass = $this->editingId ? $this->own()->find($this->editingId) : null;
        if ($this->editingId && !$pass) {
            $this->closeForm();

            return;
        }

        $data = [
            'student_detail_id'  => $student->id,
            'standard_id'        => $student->standard_id,
            'section_id'         => $student->section_id,
            'reason'             => trim($this->reason),
            'out_at'             => $out,
            'returned_at'        => $back,
            'picked_by_name'     => trim($this->pickedByName),
            'picked_by_relation' => $this->pickedByRelation ?: null,
            'picked_by_mobile'   => trim($this->pickedByMobile) ?: null,
            'remarks'            => trim($this->remarks) ?: null,
        ];

        if ($pass) {
            $pass->update($data);
        } else {
            $pass = $this->createWithNumber($data);
        }

        $this->notification()->success($this->editingId ? 'Gate pass updated' : 'Gate pass ' . $pass->number() . ' created');
        $this->formOpen = false;
        $this->resetForm();
        $this->viewingId = $pass->id;
    }

    /** A new pass on the school's next number (taken again if two were made at once). */
    private function createWithNumber(array $data): StudentGatePass
    {
        for ($try = 0; ; $try++) {
            try {
                return StudentGatePass::create($data + [
                    'organization_id' => $this->orgId(),
                    'pass_number'     => StudentGatePass::nextNumber($this->orgId()),
                    'added_by'        => Auth::id(),
                ]);
            } catch (QueryException $e) {
                if ($try >= 2) {
                    throw $e;
                }
            }
        }
    }

    /** The student is back now (never before they went). */
    public function markReturned(int $id): void
    {
        $p = $this->own()->whereNull('returned_at')->find($id);
        if (!$p) {
            return;
        }
        $p->update(['returned_at' => now()->max($p->out_at)]);
        $this->notification()->success('Marked returned at ' . $p->returned_at->format('h:i A'));
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

    public function deletePass(): void
    {
        $p = $this->own()->find($this->deletingId);
        $this->deletingId = null;
        if (!$p) {
            return;
        }
        $p->delete();
        if ($this->viewingId === $p->id) {
            $this->viewingId = null;
        }
        $this->notification()->success('Gate pass deleted');
    }

    public function render()
    {
        $orgId = $this->orgId();
        $term  = trim($this->search);
        $passNo = preg_match('/^(?:gp-?)?0*(\d+)$/i', $term, $m) ? (int) $m[1] : null;

        $passes = $this->own()
            ->with(['studentDetail.user:id,image', 'standard:id,name', 'section:id,name'])
            ->when($this->filterDate !== '', fn ($q) => $q->whereDate('out_at', $this->filterDate))
            ->when($this->filterStandard !== '', fn ($q) => $q->where('standard_id', (int) $this->filterStandard))
            ->when($this->filterSection !== '', fn ($q) => $q->where('section_id', (int) $this->filterSection))
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($term, $passNo) {
                $w->whereHas('studentDetail', fn ($s) => $s->where('full_name', 'like', "%{$term}%")
                    ->orWhere('admission_no', 'like', "%{$term}%"))
                    ->orWhere('picked_by_name', 'like', "%{$term}%");
                if ($passNo !== null) {
                    $w->orWhere('pass_number', $passNo);
                }
            }))
            ->orderByDesc('out_at')
            ->orderByDesc('id')
            ->paginate(15);

        $filterStandards = Standard::where('organization_id', $orgId)->where('is_active', true)->inClassOrder()->get(['id', 'name']);

        return view('livewire.admin.gatepass', [
            'passes'          => $passes,
            'today'           => $this->own()->whereDate('out_at', now()->toDateString())->count(),
            'total'           => $this->own()->count(),
            'filterStandards' => $filterStandards,
            'filterSections'  => $this->filterStandard !== ''
                ? Section::where('standard_id', (int) $this->filterStandard)->whereIn('standard_id', $filterStandards->pluck('id'))->orderBy('name')->get(['id', 'name'])
                : collect(),
            'viewing'   => $this->viewingId
                ? $this->own()->with(['studentDetail.user:id,image', 'standard:id,name', 'section:id,name', 'addedBy:id,name'])->find($this->viewingId)
                : null,
            'standards' => $this->formOpen ? $this->pickerStandards() : collect(),
            'sections'  => $this->formOpen ? $this->pickerSections() : collect(),
            'students'  => $this->formOpen ? $this->pickerStudents() : collect(),
        ]);
    }
}
