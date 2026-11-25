<?php

namespace App\Livewire\Admin;

use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use App\Support\BoardClasses;
use App\Support\BoardsStudent;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * More → Boards (user's ask of 2026-11-25): the school's Class 9 to 12
 * students only — the classes whose names say 9, 10, 11 or 12 (IX, 10th,
 * Class XI Science… — App\Support\BoardClasses) — with the details a board
 * registration asks for, by class, section or search. View shows a
 * student's personal and class details and their fee, as the website's
 * Boards login shows them to the student (App\Support\BoardsStudent).
 */
class Boards extends Component
{
    use WithPagination;

    public string $filterStandard = '';
    public string $filterSection  = '';
    public string $search         = '';

    public ?int $viewingId = null;

    protected $queryString = [
        'filterStandard' => ['except' => '', 'history' => false],
        'filterSection'  => ['except' => '', 'history' => false],
        'search'         => ['except' => '', 'history' => false],
    ];

    private function orgId(): int
    {
        return (int) Auth::user()?->organization_id;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['filterSection', 'search'], true)) {
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
        $this->reset(['filterStandard', 'filterSection', 'search']);
        $this->resetPage();
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    /** The school's Class 9–12 students, narrowed by the filters. */
    private function query(array $ids)
    {
        $term = trim($this->search);

        return StudentDetail::with(['user:id,name,image,is_active', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $this->orgId())
            ->whereIn('standard_id', $ids ?: [0])
            ->when($this->filterStandard !== '' && in_array((int) $this->filterStandard, $ids, true),
                fn ($q) => $q->where('standard_id', (int) $this->filterStandard))
            ->when($this->filterSection !== '', fn ($q) => $q->where('section_id', (int) $this->filterSection))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$term}%")
                ->orWhere('father_name', 'like', "%{$term}%")
                ->orWhere('admission_no', 'like', "%{$term}%")
                ->orWhere('roll_no', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('appar_id', 'like', "%{$term}%")
                ->orWhere('registration_number', 'like', "%{$term}%")));
    }

    public function render()
    {
        $orgId     = $this->orgId();
        $standards = BoardClasses::standards($orgId);
        $ids       = $standards->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Class 9 first, then the Standard page's order; in a class by section, then roll number.
        $order = $ids
            ? 'CASE standard_id ' . implode(' ', array_map(fn ($id, $i) => 'WHEN ' . (int) $id . ' THEN ' . (int) $i, $ids, array_keys($ids))) . ' END'
            : 'standard_id';

        $students = $this->query($ids)
            ->orderByRaw($order)
            ->orderBy('section_id')
            ->orderByRaw('roll_no + 0')
            ->orderBy('full_name')
            ->paginate(50);

        $counts = StudentDetail::where('organization_id', $orgId)->whereIn('standard_id', $ids ?: [0])
            ->selectRaw('standard_id, COUNT(*) as n')->groupBy('standard_id')->pluck('n', 'standard_id');

        $viewing = null;
        if ($this->viewingId) {
            $student = StudentDetail::where('organization_id', $orgId)->whereIn('standard_id', $ids ?: [0])->find($this->viewingId);
            $viewing = $student ? (new BoardsStudent($orgId))->data($student, fullAadhaar: true) + ['id' => $student->id] : null;
        }

        return view('livewire.admin.boards', [
            'students'       => $students,
            'standards'      => $standards,
            'counts'         => $counts,
            'total'          => (int) $counts->sum(),
            'filterSections' => $this->filterStandard !== ''
                ? Section::where('standard_id', (int) $this->filterStandard)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'viewing'        => $viewing,
        ]);
    }
}
