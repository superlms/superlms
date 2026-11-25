<?php

namespace App\Livewire\Admin;

use App\Models\Boards\BoardSetting;
use App\Models\Boards\BoardStudent;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use App\Support\BoardClasses;
use App\Support\BoardsStudent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * More → Boards (user's ask of 2026-11-25): the school's Class 9 to 12
 * students only — the classes whose names say 9, 10, 11 or 12 (IX, 10th,
 * Class XI Science… — App\Support\BoardClasses) — with the details a board
 * registration asks for, by class, section or search.
 *
 * Each row's arrow opens the student: every detail the school has for them,
 * their fee, and two fields — their board roll number and their admit card
 * (a PDF or picture) — saved together. Settings holds the two fee rules: the
 * share of the fee paid before the website's Boards login shows the roll
 * number, and before it gives the admit card (App\Support\BoardsStudent).
 */
class Boards extends Component
{
    use WithFileUploads, WithPagination, WireUiActions;

    /** 'students' | 'settings' */
    public string $tab = 'students';

    public string $filterStandard = '';
    public string $filterSection  = '';
    public string $search         = '';

    // ─── One student's slide-in ───────────────────────────────────────────
    public ?int $viewingId  = null;
    public string $boardRoll = '';
    public $admitCard        = null;

    // ─── Settings: % of the fee paid for the roll number / the admit card ──
    public $rollPercent  = '0';
    public $admitPercent = '0';

    protected $queryString = [
        'tab'            => ['except' => 'students'],
        'filterStandard' => ['except' => '', 'history' => false],
        'filterSection'  => ['except' => '', 'history' => false],
        'search'         => ['except' => '', 'history' => false],
    ];

    private function orgId(): int
    {
        return (int) Auth::user()?->organization_id;
    }

    private static function percent(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    public function mount(): void
    {
        $rules = BoardSetting::rules($this->orgId());
        $this->rollPercent  = self::percent($rules['roll']);
        $this->admitPercent = self::percent($rules['admit']);
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['students', 'settings'], true) ? $tab : 'students';
        $this->closeView();
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

    // ─── A student ───────────────────────────────────────────────────────

    /** A Class 9–12 student of this school, or null. */
    private function boardStudent(?int $id): ?StudentDetail
    {
        $ids = BoardClasses::standardIds($this->orgId());

        return $id && $ids
            ? StudentDetail::where('organization_id', $this->orgId())->whereIn('standard_id', $ids)->find($id)
            : null;
    }

    public function view(int $id): void
    {
        $student = $this->boardStudent($id);
        if (! $student) {
            return;
        }
        $this->resetValidation();
        $this->viewingId = $id;
        $this->admitCard = null;
        $this->boardRoll = (string) ((new BoardsStudent($this->orgId()))->boardRecord($id)?->roll_number ?? '');
    }

    public function closeView(): void
    {
        $this->viewingId = null;
        $this->boardRoll = '';
        $this->admitCard = null;
        $this->resetValidation();
    }

    /** Save the board roll number and, if one was picked, the admit card. */
    public function saveBoard(): void
    {
        $student = $this->boardStudent($this->viewingId);
        if (! $student) {
            return;
        }

        $this->validate([
            'boardRoll' => 'nullable|string|max:40',
            'admitCard' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ], [
            'boardRoll.max'   => 'The roll number may not be longer than 40 characters.',
            'admitCard.mimes' => 'The admit card must be a PDF, JPG, PNG or WebP file.',
            'admitCard.max'   => 'The admit card must be 5 MB or smaller.',
        ]);

        $orgId = $this->orgId();
        $rec   = BoardStudent::firstOrNew(['student_detail_id' => $student->id], ['organization_id' => $orgId]);
        $rec->organization_id = $orgId;
        $rec->roll_number     = trim($this->boardRoll) !== '' ? trim($this->boardRoll) : null;
        $rec->updated_by      = Auth::id();

        if ($this->admitCard) {
            $old = $rec->admit_card_path;
            $rec->admit_card_path = $this->admitCard->store('boards/admit-cards/' . $orgId, 's3');
            $rec->admit_card_name = mb_substr($this->admitCard->getClientOriginalName() ?: 'Admit Card', 0, 191);
            if ($old) {
                try { Storage::disk('s3')->delete($old); } catch (\Throwable $e) {}
            }
        }
        $rec->save();

        $this->admitCard = null;
        $this->notification()->success('Saved', 'The board roll number and admit card are saved.');
    }

    public function removeAdmitCard(): void
    {
        $rec = (new BoardsStudent($this->orgId()))->boardRecord((int) $this->viewingId);
        if (! $rec || ! $rec->admit_card_path) {
            return;
        }
        try { Storage::disk('s3')->delete($rec->admit_card_path); } catch (\Throwable $e) {}
        $rec->update(['admit_card_path' => null, 'admit_card_name' => null, 'updated_by' => Auth::id()]);
        $this->notification()->success('Removed', 'The admit card is removed.');
    }

    // ─── Settings ────────────────────────────────────────────────────────

    public function saveSettings(): void
    {
        $this->validate([
            'rollPercent'  => 'required|numeric|min:0|max:100',
            'admitPercent' => 'required|numeric|min:0|max:100',
        ], [
            'rollPercent.*'  => 'Enter a percentage from 0 to 100.',
            'admitPercent.*' => 'Enter a percentage from 0 to 100.',
        ]);

        BoardSetting::updateOrCreate(
            ['organization_id' => $this->orgId()],
            ['roll_fee_percent' => round((float) $this->rollPercent, 2), 'admit_fee_percent' => round((float) $this->admitPercent, 2)],
        );
        $this->rollPercent  = self::percent((float) $this->rollPercent);
        $this->admitPercent = self::percent((float) $this->admitPercent);
        $this->notification()->success('Saved', 'The Boards fee rules are saved.');
    }

    // ─── The list ────────────────────────────────────────────────────────

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

        // Each listed student's board roll number and admit card.
        try {
            $boardRows = BoardStudent::where('organization_id', $orgId)
                ->whereIn('student_detail_id', $students->pluck('id'))->get()->keyBy('student_detail_id');
        } catch (\Illuminate\Database\QueryException $e) {
            $boardRows = collect();
        }

        $counts = StudentDetail::where('organization_id', $orgId)->whereIn('standard_id', $ids ?: [0])
            ->selectRaw('standard_id, COUNT(*) as n')->groupBy('standard_id')->pluck('n', 'standard_id');

        $viewing = null;
        if ($this->viewingId && ($student = $this->boardStudent($this->viewingId))) {
            $viewing = (new BoardsStudent($orgId))->data($student, forSchool: true) + ['id' => $student->id];
        }

        return view('livewire.admin.boards', [
            'students'       => $students,
            'boardRows'      => $boardRows,
            'standards'      => $standards,
            'counts'         => $counts,
            'total'          => (int) $counts->sum(),
            'filterSections' => $this->filterStandard !== ''
                ? Section::where('standard_id', (int) $this->filterStandard)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'viewing'        => $viewing,
            'rules'          => BoardSetting::rules($orgId),
        ]);
    }
}
