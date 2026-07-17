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

    /** View + status panel */
    public bool $showViewPanel = false;
    public ?int $viewId = null;
    public string $statusChoice = 'pending';   // pending | approved | rejected
    public string $paymentType = '';           // monthly | one_time | student_based
    public $paymentAmount = '';
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
        $this->showViewPanel = false;
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

    // ─── View + status marking (in the slide-in panel) ────────────────
    public function openView(int $id): void
    {
        $s = SchoolListing::findOrFail($id);
        $this->viewId        = $s->id;
        $this->statusChoice  = in_array($s->status, SchoolListing::STATUSES, true) ? $s->status : 'pending';
        $this->paymentType   = $s->payment_type ?? '';
        $this->paymentAmount = $s->payment_amount !== null ? (string) $s->payment_amount : '';
        $this->remarkText    = $s->remark ?? '';
        $this->resetErrorBag();
        $this->showPanel     = false;
        $this->showViewPanel = true;
    }

    public function closeViewPanel(): void
    {
        $this->showViewPanel = false;
        $this->reset(['viewId', 'statusChoice', 'paymentType', 'paymentAmount', 'remarkText']);
        $this->resetErrorBag();
    }

    public function saveStatus(): void
    {
        $rules = ['statusChoice' => 'required|in:pending,approved,rejected'];

        if ($this->statusChoice === 'approved') {
            $rules['paymentType']   = 'required|in:monthly,one_time,student_based';
            $rules['paymentAmount'] = 'required|numeric|min:0|max:100000000';
        }
        if ($this->statusChoice === 'rejected') {
            $rules['remarkText'] = 'required|string|max:1000';
        }

        $this->validate($rules, [
            'paymentType.required'   => 'Please choose a payment type.',
            'paymentType.in'         => 'Please choose a valid payment type.',
            'paymentAmount.required' => 'Please enter the amount.',
            'remarkText.required'    => 'Please enter a remark.',
        ]);

        $s = SchoolListing::find($this->viewId);
        if (! $s) {
            $this->notification()->error('Not found', 'School not found.');
            $this->closeViewPanel();
            return;
        }

        if ($this->statusChoice === 'approved') {
            $s->update([
                'status'         => 'approved',
                'payment_type'   => $this->paymentType,
                'payment_amount' => $this->paymentAmount,
            ]);
            $this->notification()->success('Approved', 'School approved with payment details.');
        } elseif ($this->statusChoice === 'rejected') {
            $s->update([
                'status'         => 'rejected',
                'remark'         => $this->remarkText,
                'payment_type'   => null,
                'payment_amount' => null,
            ]);
            $this->notification()->success('Remark Saved', 'School marked with a remark.');
        } else {
            $s->update(['status' => 'pending']);
            $this->notification()->success('Updated', 'School moved back to pending.');
        }

        $this->closeViewPanel();
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

        $viewSchool = $this->viewId ? SchoolListing::find($this->viewId) : null;

        return view('livewire.super-admin.listing', [
            'schools'    => $schools,
            'locations'  => $locations,
            'stats'      => $stats,
            'viewSchool' => $viewSchool,
        ]);
    }
}
