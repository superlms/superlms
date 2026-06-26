<?php

namespace App\Livewire\Admin;

use App\Models\Admin\ContactSuperAdmin;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class ContactAdmin extends Component
{
    use WireUiActions, WithFileUploads;

    public $showViewModal    = false;
    public $open             = false;
    public $editId           = null;
    public $viewModalTitle   = '';
    public $viewData         = [];

    public bool $showDeleteConfirm = false;
    public $deleteTargetId         = null;

    // Form fields
    public $topic        = '';
    public $admin_query  = '';
    public $image;
    public $existingImage = null; // existing S3 URL when editing

    // Data
    public $contacts      = [];
    public $organization;

    // Filters
    public $filterDays   = null;
    public $statusFilter = '';

    protected $listeners = ['onViewContact', 'onDeleteContact', 'onEditContact'];

    public function mount(): void
    {
        $this->organization = Organization::find(Auth::user()->organization_id);
        $this->loadContacts();
    }

    // ─── Filters ─────────────────────────────────────────────────────────────

    public function applyFilter($type, $value): void
    {
        if ($type === 'days') {
            $this->filterDays = $value;
        }
        $this->loadContacts();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterDays', 'statusFilter']);
        $this->loadContacts();
    }


    public function onAddContact(): void
    {
        $this->resetForm();
        $this->open = true;
    }

    public function closeModal(): void
    {
        $this->open = false;
        $this->resetForm();
    }

    public function updatedStatusFilter(): void
    {
        $this->loadContacts();
    }

    public function loadContacts(): void
    {
        $query = ContactSuperAdmin::with(['user', 'organization'])
            ->where('organization_id', Auth::user()->organization_id);

        if ($this->filterDays) {
            $query->where('created_at', '>=', now()->subDays($this->filterDays));
        }

        // super_admin_reply is boolean — filter accordingly
        if ($this->statusFilter === 'pending') {
            $query->where('super_admin_reply', false);
        } elseif ($this->statusFilter === 'replied') {
            $query->where('super_admin_reply', true);
        }

        $this->contacts = $query->latest()->get();
    }

    // ─── Save ─────────────────────────────────────────────────────────────────

    public function onSave(): void
    {
        $this->validate([
            'topic'       => 'required|string|max:255',
            'admin_query' => 'required|string',
            'image'       => 'nullable|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            $contact = $this->editId
                ? ContactSuperAdmin::findOrFail($this->editId)
                : new ContactSuperAdmin();

            $data = [
                'user_id'         => Auth::id(),
                'organization_id' => Auth::user()->organization_id,
                'topic'           => $this->topic,
                'admin_query'     => $this->admin_query,
            ];

            if ($this->image) {
                // Delete old image if replacing
                if ($contact->image) {
                    Storage::disk('s3')->delete(parse_url($contact->image, PHP_URL_PATH));
                }
                $path = $this->image->store('admin/contact/images', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $data['image'] = Storage::disk('s3')->url($path);
            } elseif ($this->existingImage) {
                // Keep existing image on edit
                $data['image'] = $this->existingImage;
            }

            $contact->fill($data)->save();

            $this->notification()->success(
                $this->editId ? 'Message updated successfully!' : 'Message sent to Super Admin successfully!'
            );

            $this->loadContacts();
            $this->closeModal();
            $this->dispatch('onSuperAdminContactAdded');
        } catch (\Exception $e) {
            $this->notification()->error('Error saving message', $e->getMessage());
        }
    }

    // ─── View ─────────────────────────────────────────────────────────────────

    public function onViewContact($id): void
    {
        $contact = ContactSuperAdmin::with(['user', 'organization'])->find($id);

        if (!$contact) {
            $this->notification()->error('Contact message not found!');
            return;
        }

        $this->viewModalTitle = 'Message Details';
        $this->viewData = [
            'contact'      => $contact,
            'organization' => $contact->organization,
            'user'         => $contact->user,
        ];

        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewData      = [];
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function onEditContact($id): void
    {
        $contact = ContactSuperAdmin::find($id);

        if (!$contact) {
            $this->notification()->error('Contact message not found!');
            return;
        }

        $this->editId        = $contact->id;
        $this->topic         = $contact->topic;
        $this->admin_query   = $contact->admin_query;
        $this->existingImage = $contact->image; // preserve existing attachment
        $this->image         = null;

        $this->open = true;
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function onDeleteContact($id): void
    {
        $this->deleteTargetId  = $id;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteTargetId   = null;
    }

    public function confirmDelete(): void
    {
        $contact = ContactSuperAdmin::find($this->deleteTargetId);

        if ($contact) {
            if ($contact->image) {
                Storage::disk('s3')->delete(parse_url($contact->image, PHP_URL_PATH));
            }
            $contact->delete();
            $this->dispatch('onSuperAdminContactAdded');
            $this->notification()->success('Message deleted successfully!');
            $this->loadContacts();
        } else {
            $this->notification()->error('Message not found!');
        }

        $this->showDeleteConfirm = false;
        $this->deleteTargetId   = null;
    }

    protected function resetForm(): void
    {
        $this->reset(['topic', 'admin_query', 'image', 'editId', 'existingImage']);
    }

    public function render()
    {
        return view('livewire.admin.contact-admin');
    }
}
