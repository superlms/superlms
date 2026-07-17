<?php

namespace App\Livewire\SuperAdmin;

use App\Models\SchoolListing;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Listing extends Component
{
    use WireUiActions, WithFileUploads, WithPagination;

    /** Filters */
    public string $search = '';
    public string $locationFilter = '';
    public string $statusFilter = '';

    /** Slide-in add/edit panel */
    public bool $showPanel = false;
    public ?int $editId = null;

    /** Form fields (Location is entered first) */
    public string $location = '';
    public $logo = null;                // new upload
    public ?string $logoUrl = null;     // existing url (edit)
    public string $name = '';
    public string $email = '';
    public string $mobile = '';
    public string $address = '';
    public string $classes = '';
    public $noOfStudents = '';
    public $avgFee = '';

    /** Remark modal */
    public ?int $remarkTargetId = null;
    public string $remarkText = '';

    /** Delete confirmation */
    public ?int $pendingDelete = null;

    protected function rules(): array
    {
        return [
            'location'     => 'required|string|max:255',
            'logo'         => 'nullable|image|max:2048', // 2 MB
            'name'         => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'mobile'       => 'nullable|digits:10',
            'address'      => 'nullable|string|max:500',
            'classes'      => 'nullable|string|max:255',
            'noOfStudents' => 'nullable|integer|min:0|max:1000000',
            'avgFee'       => 'nullable|numeric|min:0|max:100000000',
        ];
    }

    protected array $messages = [
        'mobile.digits' => 'Mobile number must be exactly 10 digits.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'locationFilter', 'statusFilter']);
        $this->resetPage();
    }

    // ─── Panel ────────────────────────────────────────────────────────
    public function openCreate(): void
    {
        $this->resetForm();
        $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        $s = SchoolListing::findOrFail($id);
        $this->editId       = $s->id;
        $this->location     = $s->location ?? '';
        $this->name         = $s->name;
        $this->email        = $s->email ?? '';
        $this->mobile       = $s->mobile ?? '';
        $this->address      = $s->address ?? '';
        $this->classes      = $s->classes ?? '';
        $this->noOfStudents = $s->no_of_students !== null ? (string) $s->no_of_students : '';
        $this->avgFee       = $s->avg_fee !== null ? (string) $s->avg_fee : '';
        $this->logoUrl      = $s->logo;
        $this->logo         = null;
        $this->resetErrorBag();
        $this->showPanel = true;
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editId', 'location', 'logo', 'logoUrl', 'name', 'email',
            'mobile', 'address', 'classes', 'noOfStudents', 'avgFee',
        ]);
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'location'       => $this->location,
            'name'           => $this->name,
            'email'          => $this->email ?: null,
            'mobile'         => $this->mobile ?: null,
            'address'        => $this->address ?: null,
            'classes'        => $this->classes ?: null,
            'no_of_students' => $this->noOfStudents !== '' ? (int) $this->noOfStudents : null,
            'avg_fee'        => $this->avgFee !== '' ? $this->avgFee : null,
        ];

        // Logo upload (public S3 url)
        if ($this->logo) {
            if ($this->editId && $this->logoUrl) {
                $old = parse_url($this->logoUrl, PHP_URL_PATH);
                if ($old) {
                    Storage::disk('s3')->delete(ltrim($old, '/'));
                }
            }
            $path = $this->logo->store('super-admin/school-listings', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $data['logo'] = Storage::disk('s3')->url($path);
        }

        if ($this->editId) {
            SchoolListing::findOrFail($this->editId)->update($data);
            $this->notification()->success('Updated', 'School updated successfully.');
        } else {
            $data['status'] = 'pending';
            SchoolListing::create($data);
            $this->notification()->success('Added', 'School added to the listing.');
        }

        $this->closePanel();
    }

    // ─── Status: approve / remark ─────────────────────────────────────
    public function approve(int $id): void
    {
        $s = SchoolListing::find($id);
        if ($s) {
            $s->update(['status' => 'approved']);
            $this->notification()->success('Approved', 'School marked as approved.');
        }
    }

    public function openRemark(int $id): void
    {
        $s = SchoolListing::findOrFail($id);
        $this->remarkTargetId = $s->id;
        $this->remarkText     = $s->remark ?? '';
        $this->resetErrorBag('remarkText');
    }

    public function cancelRemark(): void
    {
        $this->remarkTargetId = null;
        $this->remarkText     = '';
    }

    public function saveRemark(): void
    {
        $this->validate([
            'remarkText' => 'required|string|max:1000',
        ], [
            'remarkText.required' => 'Please enter a remark.',
        ]);

        $s = SchoolListing::find($this->remarkTargetId);
        if ($s) {
            $s->update(['status' => 'rejected', 'remark' => $this->remarkText]);
            $this->notification()->success('Remark Saved', 'School marked with a remark.');
        }

        $this->cancelRemark();
    }

    public function markPending(int $id): void
    {
        $s = SchoolListing::find($id);
        if ($s) {
            $s->update(['status' => 'pending']);
            $this->notification()->success('Reset', 'School moved back to pending.');
        }
    }

    // ─── Delete ───────────────────────────────────────────────────────
    public function confirmDelete(int $id): void
    {
        $this->pendingDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->pendingDelete = null;
    }

    public function deleteSchool(): void
    {
        $s = SchoolListing::find($this->pendingDelete);
        if ($s) {
            if ($s->logo) {
                $old = parse_url($s->logo, PHP_URL_PATH);
                if ($old) {
                    Storage::disk('s3')->delete(ltrim($old, '/'));
                }
            }
            $s->delete();
            $this->notification()->success('Deleted', 'School removed from the listing.');
        }
        $this->pendingDelete = null;
        $this->resetPage();
    }

    public function render()
    {
        $schools = SchoolListing::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('mobile', 'like', "%{$this->search}%");
                });
            })
            ->when($this->locationFilter, fn($q) => $q->where('location', $this->locationFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        $locations = SchoolListing::query()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        $stats = [
            'total'    => SchoolListing::count(),
            'approved' => SchoolListing::where('status', 'approved')->count(),
            'pending'  => SchoolListing::where('status', 'pending')->count(),
            'rejected' => SchoolListing::where('status', 'rejected')->count(),
        ];

        return view('livewire.super-admin.listing', [
            'schools'   => $schools,
            'locations' => $locations,
            'stats'     => $stats,
        ]);
    }
}
