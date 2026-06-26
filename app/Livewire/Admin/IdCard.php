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

    /* ───────────────────────── Analytics ───────────────────────── */

    public function getAnalyticsProperty(): array
    {
        $orgId = Auth::user()->organization_id;

        switch ($this->cardType) {
            case 'student':
                $total  = StudentDetail::where('organization_id', $orgId)->count();
                $issued = StudentIdCard::where('organization_id', $orgId)->where('status', 'active')
                    ->distinct('student_detail_id')->count('student_detail_id');
                break;
            case 'teacher':
                $total  = TeacherDetail::where('organization_id', $orgId)->count();
                $issued = TeacherIdCard::where('organization_id', $orgId)->where('status', 'active')
                    ->distinct('teacher_detail_id')->count('teacher_detail_id');
                break;
            default:
                $total  = AdminEmployee::where('organization_id', $orgId)->count();
                $issued = EmployeeIdCard::where('organization_id', $orgId)->where('status', 'active')
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
        $this->genExpiryDate = now()->addYear()->format('Y-m-d');
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
        $this->reset(['search', 'standardFilter', 'sectionFilter', 'statusFilter']);
        $this->resetPage();
    }

    /* ───────────────────────── Render ───────────────────────── */

    public function render()
    {
        $orgId = Auth::user()->organization_id;

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
            $query = EmployeeIdCard::with(['adminEmployee', 'organization'])
                ->where('organization_id', $orgId);

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

        $cards = $query->latest()->paginate($this->perPage);

        $standards = \App\Models\Student\Standard::where('organization_id', $orgId)
            ->where('is_active', true)->orderBy('order')->get(['id', 'name']);
        $sections = $this->standardFilter
            ? \App\Models\Student\Section::where('standard_id', $this->standardFilter)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('livewire.admin.id-card', [
            'cards'     => $cards,
            'standards' => $standards,
            'sections'  => $sections,
        ]);
    }
}
