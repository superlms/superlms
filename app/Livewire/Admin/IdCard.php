<?php

namespace App\Livewire\Admin;

use App\Models\Admin\AdminEmployee;
use App\Models\Admin\EmployeeIdCard;
use App\Models\Admin\IdCardGenerationSetting;
use App\Models\Admin\StudentIdCard;
use App\Models\Admin\TeacherIdCard;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Services\IdCardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class IdCard extends Component
{
    use WithPagination, WireUiActions;

    // Active tab / listing type
    public $cardType = 'student'; // student | teacher | employee

    // Filters
    public $search = '';
    public $standardFilter = '';
    public $sectionFilter  = '';
    public $statusFilter   = '';
    /** '' both, 'issued' (holds an active card) or 'not_issued' (holds none) — as the header counts them. */
    public $issueFilter    = '';
    /** Rows a page: fixed at 100 (the filter bar no longer offers a choice). */
    public $perPage = 100;

    // Generate flow
    public $showGenerateModal = false;
    public $genType = 'student';
    public $genStandardIds = [];
    public $genExpiryDate = '';

    // Edit
    public $showEditModal = false;
    public $cardId;
    public $editExpiryDate;
    public $editStatus = 'active';

    // View
    public $showViewModal = false;
    public $viewCard = null;
    public $viewType = 'student';

    // Delete
    public $showDeleteModal = false;

    public function updatedStandardFilter() { $this->sectionFilter = ''; $this->resetPage(); }
    public function updatedSectionFilter()  { $this->resetPage(); }
    public function updatedStatusFilter()   { $this->resetPage(); }
    public function updatedIssueFilter()    { $this->resetPage(); }
    public function updatedSearch()         { $this->resetPage(); }

    public function switchCardType($type)
    {
        if (!in_array($type, IdCardService::TYPES, true)) {
            return;
        }
        $this->cardType = $type;
        $this->resetFilters();
    }

    private function service(): IdCardService
    {
        return app(IdCardService::class);
    }

    private function modelFor(string $type): string
    {
        return $this->service()->modelClassFor($type);
    }

    /**
     * The Employees tab's people: the staff who are not teachers — management,
     * employees and drivers. A teacher has a staff (payroll) row as well, but
     * teachers have a tab and a card of their own, so they are left out here.
     */
    private function staff(int $orgId)
    {
        return AdminEmployee::where('organization_id', $orgId)->where('type', '!=', 'teacher');
    }

    /* ───────────────────────── Analytics ───────────────────────── */

    public function getAnalyticsProperty(): array
    {
        $orgId = Auth::user()->organization_id;

        switch ($this->cardType) {
            case 'student':
                $total  = StudentDetail::where('organization_id', $orgId)->count();
                // Only cards of students still here: deleting a student leaves
                // their card behind, and counting it made Issued exceed Total.
                $issued = StudentIdCard::where('organization_id', $orgId)->where('status', 'active')
                    ->whereIn('student_detail_id', StudentDetail::where('organization_id', $orgId)->select('id'))
                    ->distinct('student_detail_id')->count('student_detail_id');
                break;
            case 'teacher':
                $total  = TeacherDetail::where('organization_id', $orgId)->count();
                $issued = TeacherIdCard::where('organization_id', $orgId)->where('status', 'active')
                    ->whereIn('teacher_detail_id', TeacherDetail::where('organization_id', $orgId)->select('id'))
                    ->distinct('teacher_detail_id')->count('teacher_detail_id');
                break;
            default:
                $total  = $this->staff($orgId)->count();
                $issued = EmployeeIdCard::where('organization_id', $orgId)->where('status', 'active')
                    ->whereIn('admin_employee_id', $this->staff($orgId)->select('id'))
                    ->distinct('admin_employee_id')->count('admin_employee_id');
        }

        return [
            'total'     => $total,
            'issued'    => $issued,
            'remaining' => max(0, $total - $issued),
        ];
    }

    /* ───────────────────────── Generate ───────────────────────── */

    public function openGenerate()
    {
        $this->genType = $this->cardType;
        $this->genStandardIds = [];
        // Cards run to the end of the session: 31 March.
        $this->genExpiryDate = \App\Support\AcademicYear::end()->format('Y-m-d');
        $this->resetValidation();
        $this->showGenerateModal = true;
    }

    public function closeGenerate()
    {
        $this->showGenerateModal = false;
        $this->genStandardIds = [];
        $this->resetValidation();
    }

    public function updatedGenType()
    {
        $this->genStandardIds = [];
    }

    public function generateCards()
    {
        $this->validate([
            'genType'        => 'required|in:student,teacher,employee',
            'genExpiryDate'  => 'required|date|after:today',
            'genStandardIds' => 'array',
        ], [
            'genExpiryDate.after' => 'Expiry date must be in the future.',
        ]);

        try {
            $organization = Auth::user()->organization;
            if (!$organization) {
                throw new \Exception('Organization not found');
            }

            $standardIds = $this->genType === 'student' ? array_values(array_filter($this->genStandardIds)) : null;

            $result = $this->service()->generateForType(
                $organization,
                $this->genType,
                $this->genExpiryDate,
                $standardIds,
                Auth::id(),
                // QR codes only for so long — the rest are drawn when a card is opened.
                IdCardService::QR_SECONDS_PER_REQUEST,
            );

            // Once any cards are issued for this type, switch on the daily
            // auto-generation for late joiners and remember the expiry to reuse.
            IdCardGenerationSetting::updateOrCreate(
                ['organization_id' => $organization->id, 'type' => $this->genType],
                ['auto_enabled' => true, 'expiry_date' => $this->genExpiryDate],
            );

            $this->closeGenerate();
            $this->cardType = $this->genType;
            $this->resetPage();

            if ($result['generated'] > 0) {
                $this->notification()->success(
                    $title = 'Success!',
                    $description = "Generated {$result['generated']} ID card(s)."
                );
            } else {
                $this->notification()->info(
                    $title = 'Nothing to generate',
                    $description = 'All selected ' . $this->genType . 's already have an active ID card.'
                );
            }

            if (!empty($result['errors'])) {
                $this->notification()->warning(
                    $title = 'Some errors occurred',
                    $description = implode('<br>', array_slice($result['errors'], 0, 5))
                );
            }
        } catch (\Throwable $e) {
            $this->notification()->error(
                $title = 'Error!',
                $description = 'Failed to generate cards: ' . $e->getMessage()
            );
        }
    }

    /* ───────────────────────── View ───────────────────────── */

    public function showCard($id)
    {
        $orgId = Auth::user()->organization_id;
        $type = $this->cardType;

        if ($type === 'student') {
            $card = StudentIdCard::with(['studentDetail.user', 'studentDetail.standard', 'studentDetail.section', 'organization'])
                ->where('organization_id', $orgId)->find($id);
            $person = $card?->studentDetail;
        } elseif ($type === 'teacher') {
            $card = TeacherIdCard::with(['teacherDetail.user', 'teacherDetail.assignedClasses.standard', 'teacherDetail.assignedClasses.section', 'organization'])
                ->where('organization_id', $orgId)->find($id);
            $person = $card?->teacherDetail;
        } else {
            $card = EmployeeIdCard::with(['adminEmployee.teacherDetail.user', 'organization'])
                ->where('organization_id', $orgId)->find($id);
            $person = $card?->adminEmployee;
        }

        if (!$card) {
            $this->notification()->error($title = 'Error!', $description = 'Card not found!');
            return;
        }

        if (!$card->qr_code && $person) {
            $qr = $this->service()->generateQrCode($card, $person, $card->organization, $type);
            if ($qr) {
                $card->update(['qr_code' => $qr]);
            }
        }

        $this->viewCard = $card;
        $this->viewType = $type;
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewCard = null;
    }

    /* ───────────────────────── Edit (expiry / status) ───────────────────────── */

    public function editCard($id)
    {
        $model = $this->modelFor($this->cardType);
        $card = $model::where('organization_id', Auth::user()->organization_id)->find($id);

        if (!$card) {
            $this->notification()->error($title = 'Error!', $description = 'Card not found!');
            return;
        }

        $this->cardId = $card->id;
        $this->editExpiryDate = optional($card->expiry_date)->format('Y-m-d');
        $this->editStatus = $card->status;
        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function saveEdit()
    {
        $this->validate([
            'editExpiryDate' => 'required|date',
            'editStatus'     => 'required|in:active,inactive',
        ]);

        $model = $this->modelFor($this->cardType);
        $card = $model::where('organization_id', Auth::user()->organization_id)->find($this->cardId);

        if ($card) {
            $card->update([
                'expiry_date' => $this->editExpiryDate,
                'status'      => $this->editStatus,
            ]);
            $this->notification()->success($title = 'Saved!', $description = 'ID card updated.');
        }

        $this->closeEditModal();
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->cardId = null;
        $this->resetValidation();
    }

    /* ───────────────────────── Delete ───────────────────────── */

    public function confirmDelete($id)
    {
        $this->cardId = $id;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->cardId = null;
    }

    public function deleteCard()
    {
        try {
            $model = $this->modelFor($this->cardType);
            $card = $model::where('organization_id', Auth::user()->organization_id)->find($this->cardId);

            if ($card) {
                $card->delete();
                $this->notification()->success($title = 'Deleted!', $description = 'ID card deleted successfully!');
            }
        } catch (\Throwable $e) {
            $this->notification()->error($title = 'Error!', $description = 'Failed to delete card: ' . $e->getMessage());
        } finally {
            $this->closeDeleteModal();
        }
    }

    public function resetFilters()
    {
        $this->reset(['search', 'standardFilter', 'sectionFilter', 'statusFilter', 'issueFilter']);
        $this->resetPage();
    }

    /* ───────────────────────── Render ───────────────────────── */

    /**
     * Students are listed a class at a time. A school has hundreds of them and
     * the whole roll is never the answer to anything, so the table waits until
     * a class is picked. Teachers and employees are few enough to just list.
     */
    public function awaitingClass(): bool
    {
        // Issued / Not issued works on its own too: it lists the whole school.
        return $this->cardType === 'student' && blank($this->standardFilter) && blank($this->issueFilter);
    }

    /** Not issued lists people (without an active card), not cards. */
    public function listingPeople(): bool
    {
        return $this->issueFilter === 'not_issued';
    }

    /**
     * Everyone of this tab's kind who holds no active card — the header's
     * "Remaining" — narrowed by the search and, for students, class and section.
     */
    private function peopleWithoutCard(int $orgId)
    {
        $like = '%' . $this->search . '%';

        if ($this->cardType === 'student') {
            return StudentDetail::with(['user', 'standard', 'section'])
                ->where('organization_id', $orgId)
                ->whereNotIn('id', StudentIdCard::where('organization_id', $orgId)->where('status', 'active')->select('student_detail_id'))
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('full_name', 'like', $like)
                    ->orWhere('admission_no', 'like', $like)
                    ->orWhere('email', 'like', $like)))
                ->when($this->standardFilter, fn ($q) => $q->where('standard_id', $this->standardFilter))
                ->when($this->sectionFilter, fn ($q) => $q->where('section_id', $this->sectionFilter))
                ->orderBy('full_name')
                ->orderBy('id');
        }

        if ($this->cardType === 'teacher') {
            return TeacherDetail::with('user')
                ->where('organization_id', $orgId)
                ->whereNotIn('id', TeacherIdCard::where('organization_id', $orgId)->where('status', 'active')->select('teacher_detail_id'))
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('employee_id', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like))))
                ->orderBy(\App\Models\User::select('name')->whereColumn('users.id', 'teacher_details.user_id')->limit(1))
                ->orderBy('id');
        }

        return $this->staff($orgId)
            ->whereNotIn('id', EmployeeIdCard::where('organization_id', $orgId)->where('status', 'active')->select('admin_employee_id'))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('mobile', 'like', $like)
                ->orWhere('designation', 'like', $like)))
            ->orderBy('name')
            ->orderBy('id');
    }

    public function render()
    {
        $orgId = Auth::user()->organization_id;

        if ($this->awaitingClass()) {
            return view('livewire.admin.id-card', [
                'cards'     => new \Illuminate\Pagination\LengthAwarePaginator(
                    [], 0, $this->perPage, 1,
                    ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
                ),
                'standards' => \App\Models\Student\Standard::where('organization_id', $orgId)
                    ->where('is_active', true)->inClassOrder()->get(['id', 'name']),
                'sections'  => collect(),
            ]);
        }

        $standards = \App\Models\Student\Standard::where('organization_id', $orgId)
            ->where('is_active', true)->inClassOrder()->get(['id', 'name']);
        $sections = $this->standardFilter
            ? \App\Models\Student\Section::where('standard_id', $this->standardFilter)->orderBy('id')->get(['id', 'name'])
            : collect();

        if ($this->listingPeople()) {
            return view('livewire.admin.id-card', [
                'cards'     => $this->peopleWithoutCard($orgId)->paginate($this->perPage),
                'standards' => $standards,
                'sections'  => $sections,
            ]);
        }

        if ($this->cardType === 'student') {
            $query = StudentIdCard::with(['studentDetail.user', 'studentDetail.standard', 'studentDetail.section', 'organization'])
                ->where('organization_id', $orgId);

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('card_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('studentDetail', function ($q2) {
                            $q2->where('full_name', 'like', '%' . $this->search . '%')
                                ->orWhere('admission_no', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                        });
                });
            }
            if ($this->standardFilter) {
                $query->whereHas('studentDetail', fn($q) => $q->where('standard_id', $this->standardFilter));
            }
            if ($this->sectionFilter) {
                $query->whereHas('studentDetail', fn($q) => $q->where('section_id', $this->sectionFilter));
            }
        } elseif ($this->cardType === 'teacher') {
            $query = TeacherIdCard::with(['teacherDetail.user', 'organization'])
                ->where('organization_id', $orgId);

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('card_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('teacherDetail', function ($q2) {
                            $q2->where('employee_id', 'like', '%' . $this->search . '%')
                                ->orWhere('phone', 'like', '%' . $this->search . '%')
                                ->orWhereHas('user', function ($q3) {
                                    $q3->where('name', 'like', '%' . $this->search . '%')
                                        ->orWhere('email', 'like', '%' . $this->search . '%');
                                });
                        });
                });
            }
        } else {
            // Teachers' staff rows carry employee cards too; those are not listed
            // here (a card whose holder was deleted still is, as on the other tabs).
            $query = EmployeeIdCard::with(['adminEmployee', 'organization'])
                ->where('organization_id', $orgId)
                ->whereNotIn('admin_employee_id', AdminEmployee::where('organization_id', $orgId)->where('type', 'teacher')->select('id'));

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('card_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('adminEmployee', function ($q2) {
                            $q2->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%')
                                ->orWhere('mobile', 'like', '%' . $this->search . '%')
                                ->orWhere('designation', 'like', '%' . $this->search . '%');
                        });
                });
            }
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Issued: the active cards of people still here — what the header counts.
        if ($this->issueFilter === 'issued') {
            $query->where('status', 'active');
            match ($this->cardType) {
                'student' => $query->whereIn('student_detail_id', StudentDetail::where('organization_id', $orgId)->select('id')),
                'teacher' => $query->whereIn('teacher_detail_id', TeacherDetail::where('organization_id', $orgId)->select('id')),
                default   => $query->whereIn('admin_employee_id', $this->staff($orgId)->select('id')),
            };
        }

        // A to Z by the holder's name, on every tab.
        $holderName = match ($this->cardType) {
            'student' => StudentDetail::select('full_name')
                ->whereColumn('student_details.id', 'student_id_cards.student_detail_id')->limit(1),
            'teacher' => \App\Models\User::select('users.name')
                ->join('teacher_details', 'teacher_details.user_id', '=', 'users.id')
                ->whereColumn('teacher_details.id', 'teacher_id_cards.teacher_detail_id')->limit(1),
            default   => AdminEmployee::select('name')
                ->whereColumn('admin_employees.id', 'employee_id_cards.admin_employee_id')->limit(1),
        };
        $cards = $query->orderBy($holderName)->orderBy('id')->paginate($this->perPage);

        return view('livewire.admin.id-card', [
            'cards'     => $cards,
            'standards' => $standards,
            'sections'  => $sections,
        ]);
    }
}
