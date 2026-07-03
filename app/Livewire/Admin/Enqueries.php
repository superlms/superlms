<?php

namespace App\Livewire\Admin;

use App\Models\Admin\ContactAdminStudent;
use App\Models\Admin\ContactAdminTeacher;
use App\Models\SchoolWebsiteEnquiry;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use WireUi\Traits\WireUiActions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Enqueries extends Component
{
    use WithPagination, WireUiActions;

    // ─── Tabs ────────────────────────────────────────────────────────────────
    public string $activeTab = 'student'; // 'student' | 'teacher' | 'website'

    // ─── State ──────────────────────────────────────────────────────────────
    public $selectedEnquiry  = null;
    public $showDetailModal  = false;
    public $showReplyModal   = false;

    public $adminReply       = '';

    // ─── Filters ────────────────────────────────────────────────────────────
    public string $filterDays    = '';
    public string $search        = '';
    public string $statusFilter  = '';

    // ─── Custom delete overlay ──────────────────────────────────────────────
    public bool $showDeleteConfirm = false;
    public $deleteTargetId         = null;

    // ─── Stats ──────────────────────────────────────────────────────────────
    public int $totalTeacher  = 0;
    public int $totalStudent  = 0;
    public int $totalWebsite  = 0;
    public int $pendingCount  = 0;
    public int $repliedCount  = 0;

    protected $queryString = [
        'activeTab'    => ['except' => 'student'],
        'filterDays'   => ['except' => ''],
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        if (!Auth::check() || !Auth::user()->organization_id) {
            return;
        }

        $orgId = Auth::user()->organization_id;

        // Single aggregate query per table (instead of 3 separate COUNTs each).
        // admin_reply is a BOOLEAN flag (false = pending, true = replied);
        // the actual reply text lives in admin_text.
        $teacher = ContactAdminTeacher::where('organization_id', $orgId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN admin_reply = 0 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN admin_reply = 1 THEN 1 ELSE 0 END) as replied
            ')
            ->first();

        $student = ContactAdminStudent::where('organization_id', $orgId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN admin_reply = 0 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN admin_reply = 1 THEN 1 ELSE 0 END) as replied
            ')
            ->first();

        $this->totalTeacher = (int) ($teacher->total ?? 0);
        $this->totalStudent = (int) ($student->total ?? 0);
        $this->totalWebsite = SchoolWebsiteEnquiry::where('organization_id', $orgId)->count();

        // Pending/Replied only apply to teacher & student enquiries.
        // Website enquiries come from the public site and have no reply flow.
        if ($this->activeTab === 'teacher') {
            $this->pendingCount = (int) ($teacher->pending ?? 0);
            $this->repliedCount = (int) ($teacher->replied ?? 0);
        } elseif ($this->activeTab === 'student') {
            $this->pendingCount = (int) ($student->pending ?? 0);
            $this->repliedCount = (int) ($student->replied ?? 0);
        } else {
            $this->pendingCount = 0;
            $this->repliedCount = 0;
        }
    }

    public function render()
    {
        $enquiries = $this->getEnquiries();
        return view('livewire.admin.enqueries', compact('enquiries'));
    }

    public function showTab($tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->loadStats();
    }

    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); $this->loadStats(); }
    public function updatedFilterDays(): void   { $this->resetPage(); }

    // ─── Queries ────────────────────────────────────────────────────────────

    private function getEnquiries()
    {
        return match ($this->activeTab) {
            'student' => $this->getStudentEnquiries(),
            'website' => $this->getWebsiteEnquiries(),
            default   => $this->getTeacherEnquiries(),
        };
    }

    private function getWebsiteEnquiries()
    {
        return SchoolWebsiteEnquiry::where('organization_id', Auth::user()->organization_id)
            ->when($this->filterDays, fn($q) => $q->where('created_at', '>=', Carbon::now()->subDays((int) $this->filterDays)))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('subject', 'like', '%' . $this->search . '%')
                        ->orWhere('message', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);
    }

    private function getTeacherEnquiries()
    {
        // Select only columns the blade reads — avoid eager-loading heavy
        // relations (teacherDetail has nested assignedSubjects/sections/
        // classes which would N+1 expand needlessly).
        return ContactAdminTeacher::where('organization_id', Auth::user()->organization_id)
            ->with([
                'user:id,name,email',
                'organization:id,name',
            ])
            ->when($this->filterDays, fn($q) => $q->where('created_at', '>=', Carbon::now()->subDays((int) $this->filterDays)))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('topic', 'like', '%' . $this->search . '%')
                        ->orWhere('teacher_query', 'like', '%' . $this->search . '%')
                        ->orWhere('admin_text', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->statusFilter, function ($query) {
                if ($this->statusFilter === 'replied') {
                    $query->where('admin_reply', true);
                } elseif ($this->statusFilter === 'pending') {
                    $query->where('admin_reply', false);
                }
            })
            ->latest()
            ->paginate(10);
    }

    private function getStudentEnquiries()
    {
        return ContactAdminStudent::where('organization_id', Auth::user()->organization_id)
            ->with([
                'user:id,name,email',
                'organization:id,name',
            ])
            ->when($this->filterDays, fn($q) => $q->where('created_at', '>=', Carbon::now()->subDays((int) $this->filterDays)))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('topic', 'like', '%' . $this->search . '%')
                        ->orWhere('student_query', 'like', '%' . $this->search . '%')
                        ->orWhere('admin_text', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->statusFilter, function ($query) {
                if ($this->statusFilter === 'replied') {
                    $query->where('admin_reply', true);
                } elseif ($this->statusFilter === 'pending') {
                    $query->where('admin_reply', false);
                }
            })
            ->latest()
            ->paginate(10);
    }

    // ─── View / Reply ───────────────────────────────────────────────────────

    public function viewEnquiry($id): void
    {
        $orgId = Auth::user()->organization_id;

        $this->selectedEnquiry = match ($this->activeTab) {
            'website' => SchoolWebsiteEnquiry::where('organization_id', $orgId)->findOrFail($id),
            'teacher' => ContactAdminTeacher::where('organization_id', $orgId)
                ->with(['user:id,name,email', 'organization:id,name'])->findOrFail($id),
            default   => ContactAdminStudent::where('organization_id', $orgId)
                ->with(['user:id,name,email', 'organization:id,name'])->findOrFail($id),
        };

        $this->showDetailModal = true;
    }

    public function openReplyModal($id): void
    {
        // Website enquiries have no reply flow — just open the detail view.
        if ($this->activeTab === 'website') {
            $this->viewEnquiry($id);
            return;
        }

        $this->viewEnquiry($id);
        $this->showDetailModal = false;
        $this->adminReply      = $this->selectedEnquiry->admin_text ?? '';
        $this->showReplyModal  = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedEnquiry = null;
    }

    public function closeReplyModal(): void
    {
        $this->showReplyModal  = false;
        $this->selectedEnquiry = null;
        $this->adminReply      = '';
    }

    public function sendReply(): void
    {
        $this->validate([
            'adminReply' => 'required|string|min:5',
        ]);

        if ($this->selectedEnquiry) {
            $this->selectedEnquiry->update([
                'admin_text'  => $this->adminReply,
                'admin_reply' => true,
            ]);

            $this->closeReplyModal();
            $this->loadStats();
            $this->notification()->success('Reply Sent', 'Your reply has been sent successfully.');
        }
    }

    public function applyFilterDays($days): void
    {
        $this->filterDays = ((string) $this->filterDays === (string) $days) ? '' : (string) $days;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterDays', 'search', 'statusFilter']);
        $this->resetPage();
    }

    // ─── Delete (custom overlay) ────────────────────────────────────────────

    public function deleteEnquiry($id): void
    {
        $this->deleteTargetId    = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

    public function confirmDelete(): void
    {
        $modelClass = match ($this->activeTab) {
            'website' => SchoolWebsiteEnquiry::class,
            'teacher' => ContactAdminTeacher::class,
            default   => ContactAdminStudent::class,
        };
        $enquiry    = $modelClass::find($this->deleteTargetId);

        if ($enquiry) {
            if (!empty($enquiry->image)) {
                Storage::disk('s3')->delete(parse_url($enquiry->image, PHP_URL_PATH));
            }
            $enquiry->delete();

            $this->notification()->success('Enquiry Deleted', 'The enquiry has been deleted successfully.');
            $this->loadStats();

            if ($this->selectedEnquiry && $this->selectedEnquiry->id == $this->deleteTargetId) {
                $this->closeDetailModal();
            }
        } else {
            $this->notification()->error('Error', 'Enquiry not found.');
        }

        $this->showDeleteConfirm = false;
        $this->deleteTargetId    = null;
    }

}
