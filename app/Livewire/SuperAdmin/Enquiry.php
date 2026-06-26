<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;
use Livewire\WithPagination;
use Carbon\Carbon;
use WireUi\Traits\WireUiActions;

class Enquiry extends Component
{
    use WithPagination, WireUiActions;

    public string  $activeTab       = 'demo';
    public ?int    $viewEnquiryId   = null;
    public ?string $viewEnquiryType = null;
    public bool    $showDetailModal = false;
    public string  $filterDays     = '';
    public string  $statusFilter   = '';
    public string  $search         = '';
    public bool    $showRemarkModal  = false;
    public string  $remarkText       = '';
    public ?int    $remarkEnquiryId  = null;
    public ?int    $pendingDeleteId  = null;

    protected $queryString = [
        'activeTab'    => ['except' => 'demo'],
        'filterDays'   => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'search'       => ['except' => ''],
    ];

    protected $rules = [
        'remarkText' => 'required|string|max:1000',
    ];

    protected $messages = [
        'remarkText.required' => 'Remark cannot be empty.',
        'remarkText.max'      => 'Remark must not exceed 1000 characters.',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
    public function updatedFilterDays(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $model = $this->activeTab === 'demo' ? WebsiteDemo::class : WebsiteContact::class;

        $enquiries = $model::query()
            ->when(
                $this->filterDays,
                fn($q) =>
                $q->where('created_at', '>=', Carbon::now()->subDays((int) $this->filterDays))
            )
            ->when($this->statusFilter === 'pending',  fn($q) => $q->whereNull('remark'))
            ->when($this->statusFilter === 'remarked', fn($q) => $q->whereNotNull('remark'))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('full_name',    'like', '%' . $this->search . '%')
                        ->orWhere('email',       'like', '%' . $this->search . '%')
                        ->orWhere('school_name', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);

        $startOfMonth = Carbon::now()->startOfMonth();

        $analytics = [
            'total'   => WebsiteDemo::count() + WebsiteContact::count(),

            'demo'            => WebsiteDemo::count(),
            'demo_pending'    => WebsiteDemo::whereNull('remark')->count(),
            'demo_remarked'   => WebsiteDemo::whereNotNull('remark')->count(),
            'demo_this_month' => WebsiteDemo::where('created_at', '>=', $startOfMonth)->count(),

            'contact'            => WebsiteContact::count(),
            'contact_pending'    => WebsiteContact::whereNull('remark')->count(),
            'contact_remarked'   => WebsiteContact::whereNotNull('remark')->count(),
            'contact_this_month' => WebsiteContact::where('created_at', '>=', $startOfMonth)->count(),
        ];

        $selectedEnquiry = null;
        if ($this->viewEnquiryId && $this->viewEnquiryType) {
            $selectedEnquiry = ($this->viewEnquiryType)::find($this->viewEnquiryId);
        }

        return view('livewire.super-admin.enquiry', compact('enquiries', 'analytics', 'selectedEnquiry'));
    }

    // ─── Tab ─────────────────────────────────────────────────────────────────

    public function switchTab(string $tab): void
    {
        $this->activeTab    = $tab;
        $this->search       = '';
        $this->statusFilter = '';
        $this->filterDays   = '';
        $this->resetPage();
    }

    // ─── Detail Modal ─────────────────────────────────────────────────────────

    public function viewEnquiry(int $id): void
    {
        $this->viewEnquiryId   = $id;
        $this->viewEnquiryType = $this->activeTab === 'demo' ? WebsiteDemo::class : WebsiteContact::class;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal  = false;
        $this->viewEnquiryId    = null;
        $this->viewEnquiryType  = null;
    }

    // ─── Remark Modal ─────────────────────────────────────────────────────────

    public function openRemarkModal(int $id): void
    {
        $model   = $this->activeTab === 'demo' ? WebsiteDemo::class : WebsiteContact::class;
        $enquiry = $model::findOrFail($id);

        $this->remarkEnquiryId = $id;
        $this->remarkText      = $enquiry->remark ?? '';
        $this->showRemarkModal = true;
    }

    public function saveRemark(): void
    {
        $this->validate();

        $model   = $this->activeTab === 'demo' ? WebsiteDemo::class : WebsiteContact::class;
        $enquiry = $model::findOrFail($this->remarkEnquiryId);
        $enquiry->update(['remark' => $this->remarkText]);

        // selectedEnquiry is fetched fresh in render() via viewEnquiryId

        $this->closeRemarkModal();

        $this->notification()->success('Remark Saved', 'The remark has been saved successfully.');
    }

    public function closeRemarkModal(): void
    {
        $this->showRemarkModal = false;
        $this->remarkText      = '';
        $this->remarkEnquiryId = null;
        $this->resetValidation();
    }

    // ─── Filters ──────────────────────────────────────────────────────────────

    public function clearFilters(): void
    {
        $this->filterDays   = '';
        $this->statusFilter = '';
        $this->search       = '';
        $this->resetPage();
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function deleteEnquiry(int $id): void
    {
        $this->pendingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->pendingDeleteId = null;
    }

    public function executeDelete(): void
    {
        if (!$this->pendingDeleteId) return;

        $model   = $this->activeTab === 'demo' ? WebsiteDemo::class : WebsiteContact::class;
        $enquiry = $model::find($this->pendingDeleteId);

        if ($enquiry) {
            $enquiry->delete();
            $this->notification()->success('Enquiry Deleted', 'The enquiry has been deleted successfully.');
        }

        if ($this->viewEnquiryId === $this->pendingDeleteId) {
            $this->closeDetailModal();
        }

        $this->pendingDeleteId = null;
    }
}
