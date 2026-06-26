<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\StudentIdCard;
use App\Models\Admin\TeacherIdCard;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class IdCard extends Component
{
    use WithPagination;

    // ─── Tabs ───────────────────────────────────────────────────────────────────
    public string $activeTab = 'student'; // student | teacher

    // ─── Filters ────────────────────────────────────────────────────────────────
    public string $search          = '';
    public string $filterStandard  = '';
    public string $filterSection   = '';
    public string $filterStatus    = '';
    public int    $perPage         = 100;

    // ─── Bulk Generate ───────────────────────────────────────────────────────────
    public bool   $showBulkModal   = false;
    public int    $validityMonths  = 12;
    public string $cardPrefix      = 'ID';
    public string $bulkStandard    = '';
    public string $bulkSection     = '';

    // ─── View ───────────────────────────────────────────────────────────────────
    public bool $showViewModal = false;
    public $viewCard           = null;

    // ─── Delete ─────────────────────────────────────────────────────────────────
    public ?int $pendingDeleteId = null;

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    // ─── Tab Switch ─────────────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $this->activeTab     = $tab;
        $this->search        = '';
        $this->filterStandard = '';
        $this->filterSection  = '';
        $this->filterStatus   = '';
        $this->resetPage();
    }

    // ─── Filter Watchers ────────────────────────────────────────────────────────

    public function updatedSearch(): void         { $this->resetPage(); }
    public function updatedFilterStandard(): void { $this->filterSection = ''; $this->resetPage(); }
    public function updatedFilterSection(): void  { $this->resetPage(); }
    public function updatedFilterStatus(): void   { $this->resetPage(); }
    public function updatedBulkStandard(): void   { $this->bulkSection = ''; }

    // ─── Bulk Generate ───────────────────────────────────────────────────────────

    public function openBulkModal(): void
    {
        $this->showBulkModal  = true;
        $this->validityMonths = 12;
        $this->cardPrefix     = 'ID';
        $this->bulkStandard   = '';
        $this->bulkSection    = '';
    }

    public function closeBulkModal(): void
    {
        $this->showBulkModal = false;
    }

    public function bulkGenerateCards(): void
    {
        $this->validate([
            'validityMonths' => 'required|integer|min:1|max:60',
            'cardPrefix'     => 'required|string|max:10',
        ]);

        $orgId   = $this->orgId();
        $org     = Auth::user()->organization;
        $generated = 0;

        if ($this->activeTab === 'student') {
            $query = StudentDetail::with(['standard', 'section'])
                ->where('organization_id', $orgId)
                ->when($this->bulkStandard, fn($q) => $q->where('standard_id', $this->bulkStandard))
                ->when($this->bulkSection, fn($q) => $q->where('section_id', $this->bulkSection))
                ->whereDoesntHave('idCards', fn($q) => $q->where('status', 'active'));

            foreach ($query->get() as $student) {
                $cardNumber = $this->makeCardNumber($student->id, 'STU');
                StudentIdCard::create([
                    'card_number'       => $cardNumber,
                    'student_detail_id' => $student->id,
                    'organization_id'   => $orgId,
                    'user_id'           => Auth::id(),
                    'issue_date'        => now(),
                    'expiry_date'       => now()->addMonths($this->validityMonths),
                    'status'            => 'active',
                ]);
                $generated++;
            }
        } else {
            $teachers = TeacherDetail::with('user')
                ->where('organization_id', $orgId)
                ->whereDoesntHave('idCards', fn($q) => $q->where('status', 'active'))
                ->get();

            foreach ($teachers as $teacher) {
                $cardNumber = $this->makeCardNumber($teacher->id, 'TCH');
                TeacherIdCard::create([
                    'card_number'       => $cardNumber,
                    'teacher_detail_id' => $teacher->id,
                    'organization_id'   => $orgId,
                    'user_id'           => Auth::id(),
                    'issue_date'        => now(),
                    'expiry_date'       => now()->addMonths($this->validityMonths),
                    'status'            => 'active',
                ]);
                $generated++;
            }
        }

        $this->closeBulkModal();
        session()->flash('success', "Generated {$generated} ID card(s) successfully!");
        $this->resetPage();
    }

    private function makeCardNumber(int $personId, string $type): string
    {
        $orgId  = $this->orgId();
        $year   = now()->format('y');
        $random = mt_rand(1000, 9999);
        return "{$this->cardPrefix}{$type}{$orgId}{$year}{$personId}{$random}";
    }

    // ─── View ───────────────────────────────────────────────────────────────────

    public function viewCard(int $id): void
    {
        if ($this->activeTab === 'student') {
            $this->viewCard = StudentIdCard::with([
                'studentDetail.standard', 'studentDetail.section',
            ])->where('id', $id)->where('organization_id', $this->orgId())->first();
        } else {
            $this->viewCard = TeacherIdCard::with([
                'teacherDetail.user',
            ])->where('id', $id)->where('organization_id', $this->orgId())->first();
        }

        if ($this->viewCard) {
            $this->showViewModal = true;
        }
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewCard      = null;
    }

    // ─── Delete ─────────────────────────────────────────────────────────────────

    public function deleteCard(int $id): void
    {
        $this->pendingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->pendingDeleteId = null;
    }

    public function doDelete(): void
    {
        $model = $this->activeTab === 'student' ? StudentIdCard::class : TeacherIdCard::class;
        $model::where('id', $this->pendingDeleteId)
            ->where('organization_id', $this->orgId())
            ->delete();
        $this->pendingDeleteId = null;
        session()->flash('success', 'ID card deleted!');
    }

    // ─── Reset Filters ───────────────────────────────────────────────────────────

    public function resetFilters(): void
    {
        $this->search        = '';
        $this->filterStandard = '';
        $this->filterSection  = '';
        $this->filterStatus   = '';
        $this->resetPage();
    }

    // ─── Render ─────────────────────────────────────────────────────────────────

    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('order')->get();

        $filterSections = collect();
        if ($this->filterStandard) {
            $filterSections = Section::where('standard_id', $this->filterStandard)
                ->where('organization_id', $orgId)->get();
        }

        $bulkSections = collect();
        if ($this->bulkStandard) {
            $bulkSections = Section::where('standard_id', $this->bulkStandard)
                ->where('organization_id', $orgId)->get();
        }

        // Analytics
        $totalStudentCards  = StudentIdCard::where('organization_id', $orgId)->count();
        $activeStudentCards = StudentIdCard::where('organization_id', $orgId)->where('status', 'active')->count();
        $totalTeacherCards  = TeacherIdCard::where('organization_id', $orgId)->count();
        $activeTeacherCards = TeacherIdCard::where('organization_id', $orgId)->where('status', 'active')->count();

        if ($this->activeTab === 'student') {
            $query = StudentIdCard::with(['studentDetail.standard', 'studentDetail.section'])
                ->where('organization_id', $orgId);

            if ($this->search) {
                $query->where(fn($q) =>
                    $q->where('card_number', 'like', "%{$this->search}%")
                      ->orWhereHas('studentDetail', fn($s) =>
                          $s->where('full_name', 'like', "%{$this->search}%")
                            ->orWhere('admission_no', 'like', "%{$this->search}%")
                      )
                );
            }
            if ($this->filterStandard) {
                $query->whereHas('studentDetail', fn($q) => $q->where('standard_id', $this->filterStandard));
            }
            if ($this->filterSection) {
                $query->whereHas('studentDetail', fn($q) => $q->where('section_id', $this->filterSection));
            }
            if ($this->filterStatus) {
                $query->where('status', $this->filterStatus);
            }

            $cards = $query->latest()->paginate($this->perPage);
        } else {
            $query = TeacherIdCard::with(['teacherDetail.user'])
                ->where('organization_id', $orgId);

            if ($this->search) {
                $query->where(fn($q) =>
                    $q->where('card_number', 'like', "%{$this->search}%")
                      ->orWhereHas('teacherDetail.user', fn($s) =>
                          $s->where('name', 'like', "%{$this->search}%")
                      )
                );
            }
            if ($this->filterStatus) {
                $query->where('status', $this->filterStatus);
            }

            $cards = $query->latest()->paginate($this->perPage);
        }

        return view('livewire.accounts.id-card', compact(
            'standards', 'filterSections', 'bulkSections', 'cards',
            'totalStudentCards', 'activeStudentCards',
            'totalTeacherCards', 'activeTeacherCards'
        ));
    }
}
