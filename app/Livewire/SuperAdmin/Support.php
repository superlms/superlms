<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use App\Models\Admin\ContactSuperAdmin;
use App\Models\Organization;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Support extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';
    public $filterDays = null;
    public $filterMonths = null;
    public $organizationFilter = '';
    public $statusFilter = '';

    public $showDetailModal = false;
    public $showReplyModal = false;
    public $selectedSupport = null;

    public $superAdminReply = '';

    // Reply attachment
    public $replyAttachment       = null;   // new upload
    public $existingReplyAttachment = null; // existing S3 url (edit)

    // Delete confirmation
    public $confirmDeleteId = null;

    public function mount() {}

    public function applyFilter($type, $value)
    {
        if ($type === 'days') {
            $this->filterDays = $value;
            $this->filterMonths = null;
        } elseif ($type === 'months') {
            $this->filterMonths = $value;
            $this->filterDays = null;
        }

        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'filterDays', 'filterMonths', 'organizationFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function getOrganizationsProperty()
    {
        return Organization::all();
    }

    public function getSupportsProperty()
    {
        $query = ContactSuperAdmin::with(['user', 'organization'])
            ->orderBy('created_at', 'desc');

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('topic', 'like', '%' . $this->search . '%')
                    ->orWhere('admin_query', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($userQuery) {
                        $userQuery->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('organization', function ($orgQuery) {
                        $orgQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Apply date filters
        if ($this->filterDays) {
            $query->where('created_at', '>=', now()->subDays($this->filterDays));
        } elseif ($this->filterMonths) {
            $query->where('created_at', '>=', now()->subMonths($this->filterMonths));
        }

        // Apply organization filter
        if ($this->organizationFilter) {
            $query->where('organization_id', $this->organizationFilter);
        }

        // Apply status filter
        if ($this->statusFilter === 'pending') {
            $query->where('super_admin_reply', false);
        } elseif ($this->statusFilter === 'replied') {
            $query->where('super_admin_reply', true);
        }

        return $query->paginate(10);
    }

    public function viewSupport($supportId)
    {
        $this->selectedSupport = ContactSuperAdmin::with(['user', 'organization'])->find($supportId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedSupport = null;
    }

    public function openReplyModal($supportId)
    {
        $this->selectedSupport = ContactSuperAdmin::with(['user', 'organization'])->find($supportId);
        $this->showReplyModal = true;
        $this->superAdminReply = $this->selectedSupport->super_admin_text ?? '';
        $this->existingReplyAttachment = $this->selectedSupport->super_admin_attachment ?? null;
        $this->replyAttachment = null;
    }

    public function closeReplyModal()
    {
        $this->showReplyModal = false;
        $this->selectedSupport = null;
        $this->superAdminReply = '';
        $this->replyAttachment = null;
        $this->existingReplyAttachment = null;
    }

    public function removeReplyAttachment()
    {
        $this->replyAttachment = null;
        $this->existingReplyAttachment = null;
    }

    public function sendReply()
    {
        $this->validate([
            'superAdminReply'  => 'required|string|min:5',
            'replyAttachment'  => 'nullable|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ], [], ['replyAttachment' => 'attachment']);

        try {
            $attachmentUrl = $this->existingReplyAttachment;

            if ($this->replyAttachment) {
                // Replace: drop the previous file if there was one
                if ($this->selectedSupport->super_admin_attachment) {
                    Storage::disk('s3')->delete(parse_url($this->selectedSupport->super_admin_attachment, PHP_URL_PATH));
                }
                $path = $this->replyAttachment->store('super-admin/support/replies', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $attachmentUrl = Storage::disk('s3')->url($path);
            } elseif (!$this->existingReplyAttachment && $this->selectedSupport->super_admin_attachment) {
                // Attachment was removed in the UI — delete the stored file
                Storage::disk('s3')->delete(parse_url($this->selectedSupport->super_admin_attachment, PHP_URL_PATH));
            }

            $this->selectedSupport->update([
                'super_admin_text'       => $this->superAdminReply,
                'super_admin_reply'      => true,
                'super_admin_attachment' => $attachmentUrl,
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Reply sent successfully!'
            ]);

            $this->closeReplyModal();
            $this->closeDetailModal();
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to send reply: ' . $e->getMessage()
            ]);
        }
    }

    // Step 1: Show confirmation popup
    public function confirmDelete($supportId)
    {
        $this->confirmDeleteId = $supportId;
    }

    // Step 2: Cancel confirmation
    public function cancelDelete()
    {
        $this->confirmDeleteId = null;
    }

    // Step 3: Actual delete (called only after confirmation)
    public function deleteSupport($supportId)
    {
        try {
            ContactSuperAdmin::find($supportId)->delete();

            $this->confirmDeleteId = null;

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Support ticket deleted successfully!'
            ]);

            if ($this->selectedSupport && $this->selectedSupport->id == $supportId) {
                $this->closeDetailModal();
                $this->closeReplyModal();
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to delete ticket: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        $totalQueries     = ContactSuperAdmin::count();
        $repliedQueries   = ContactSuperAdmin::where('super_admin_reply', true)->count();
        $pendingQueries   = ContactSuperAdmin::where('super_admin_reply', false)->count();
        $thisMonthQueries = ContactSuperAdmin::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();

        return view('livewire.super-admin.support', [
            'supports'         => $this->supports,
            'organizations'    => $this->organizations,
            'totalQueries'     => $totalQueries,
            'repliedQueries'   => $repliedQueries,
            'pendingQueries'   => $pendingQueries,
            'thisMonthQueries' => $thisMonthQueries,
        ]);
    }
}
