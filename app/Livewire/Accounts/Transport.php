<?php

namespace App\Livewire\Accounts;

use App\Livewire\Concerns\HandlesTransportFees;
use App\Models\Admin\DriverDetail;
use App\Models\Admin\Transportation;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class Transport extends Component
{
    use WireUiActions, WithPagination, WithFileUploads, HandlesTransportFees;

    #[Url(keep: true)]
    public string $activeTab = 'transportation'; // transportation | drivers | students | fees

    protected function txOrgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    // ─── Modals (slide-in panels) ──────────────────────────
    public bool $driverModal    = false;
    public bool $transportModal = false;

    public ?int $editDriverId    = null;
    public ?int $editTransportId = null;

    // ─── Driver Form ───────────────────────────────────────
    public string $driver_name         = '';
    public string $driver_email        = '';
    public string $driver_phone        = '';
    public string $license_no          = '';
    public string $driver_vehicle_no   = '';
    public string $driver_vehicle_type = '';
    public string $driver_address      = '';
    public int    $experience_years    = 0;
    public bool   $driver_is_active    = true;
    public $driver_image;                    // uploaded file
    public ?string $driver_image_existing = null;

    // ─── Transportation (Route) Form ───────────────────────
    public string $route_name          = '';
    public ?int   $driver_detail_id    = null;
    public string $pickup_time         = '';
    public float  $monthly_fee         = 0;
    public int    $capacity            = 0;
    public bool   $transport_is_active = true;

    // ─── Filters ───────────────────────────────────────────
    #[Url(keep: true)]
    public string $search = '';
    #[Url(keep: true)]
    public string $filterStatus = '';
    #[Url(keep: true)]
    public string $filterRoute = '';     // drivers tab → filter by route
    #[Url(keep: true)]
    public string $filterDriver = '';    // routes tab → filter by driver
    #[Url(keep: true)]
    public int $perPage = 10;

    // ─── Delete confirm ────────────────────────────────────
    public ?int $pendingDeleteDriverId = null;
    public ?int $pendingDeleteRouteId  = null;

    public array $availableDrivers = [];
    public array $vehicleTypes = ['Bus', 'Mini Bus', 'Van', 'Auto', 'Car', 'Other'];

    protected $listeners = ['refresh-transport' => '$refresh'];

    public function mount(): void
    {
        $this->loadAvailableDrivers();
    }

    #[Computed(cache: true, key: 'transport-org-id', seconds: 3600)]
    public function organizationId(): ?int
    {
        return Auth::user()?->organization_id;
    }

    #[Computed]
    public function statistics(): array
    {
        if (!$this->organizationId) {
            return ['drivers' => 0, 'routes' => 0, 'students' => 0, 'monthly_revenue' => 0];
        }
        $orgId = $this->organizationId;

        return [
            'drivers'         => DriverDetail::where('organization_id', $orgId)->where('is_active', true)->count(),
            'routes'          => Transportation::where('organization_id', $orgId)->where('is_active', true)->count(),
            'students'        => DB::table('transportation_students')->where('organization_id', $orgId)->count(),
            'monthly_revenue' => Transportation::where('organization_id', $orgId)
                ->where('is_active', true)->withCount('students')->get()
                ->sum(fn($t) => $t->monthly_fee * $t->students_count),
        ];
    }

    private function loadAvailableDrivers(): void
    {
        if (!$this->organizationId) { $this->availableDrivers = []; return; }

        $this->availableDrivers = DriverDetail::with('user:id,name')
            ->where('organization_id', $this->organizationId)
            ->where('is_active', true)
            ->get(['id', 'user_id', 'license_no', 'vehicle_no'])
            ->map(fn($d) => [
                'id'         => $d->id,
                'name'       => $d->user->name ?? 'Unknown',
                'license_no' => $d->license_no,
                'vehicle_no' => $d->vehicle_no,
            ])->toArray();
    }

    // ── Watchers ──
    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedPerPage(): void      { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterRoute(): void  { $this->resetPage(); }
    public function updatedFilterDriver(): void { $this->resetPage(); }
    public function updatedActiveTab(): void    { $this->resetPage(); $this->search = ''; }

    // ═══════════════════════════════ DRIVERS ═════════════════════════════════
    public function createDriver(): void
    {
        $this->resetDriverForm();
        $this->editDriverId = null;
        $this->driverModal  = true;
    }

    public function editDriver(int $id): void
    {
        $driver = DriverDetail::with('user')->findOrFail($id);
        $this->editDriverId        = $driver->id;
        $this->driver_name         = $driver->user->name ?? '';
        $this->driver_email        = $driver->user->email ?? '';
        $this->driver_phone        = $driver->phone ?? '';
        $this->license_no          = $driver->license_no ?? '';
        $this->driver_vehicle_no   = $driver->vehicle_no ?? '';
        $this->driver_vehicle_type = $driver->vehicle_type ?? '';
        $this->driver_address      = $driver->address ?? '';
        $this->experience_years    = $driver->experience_years ?? 0;
        $this->driver_is_active    = $driver->is_active;
        $this->driver_image        = null;
        $this->driver_image_existing = $driver->image;
        $this->driverModal         = true;
    }

    public function saveDriver(): void
    {
        $rules = [
            'driver_name'         => 'required|string|max:255',
            'driver_email'        => 'required|email|max:255',
            'driver_phone'        => 'nullable|string|max:20',
            'license_no'          => 'nullable|string|max:50',
            'driver_vehicle_no'   => 'nullable|string|max:30',
            'driver_vehicle_type' => 'nullable|string|max:50',
            'driver_address'      => 'nullable|string|max:500',
            'experience_years'    => 'nullable|integer|min:0|max:50',
            'driver_image'        => 'nullable|image|max:2048',
        ];
        if (!$this->editDriverId) {
            $rules['driver_email'] = 'required|email|unique:users,email';
        }
        $this->validate($rules);

        DB::beginTransaction();
        try {
            // Resolve image URL
            $imageUrl = $this->driver_image_existing;
            if ($this->driver_image) {
                if ($this->driver_image_existing) {
                    $old = parse_url($this->driver_image_existing, PHP_URL_PATH);
                    if ($old) Storage::disk('s3')->delete(ltrim($old, '/'));
                }
                $path = $this->driver_image->store('admin/drivers/photos', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $imageUrl = Storage::disk('s3')->url($path);
            }

            if ($this->editDriverId) {
                $driver = DriverDetail::findOrFail($this->editDriverId);
                $driver->user->update(['name' => $this->driver_name, 'email' => $this->driver_email]);
                $driver->update([
                    'image'            => $imageUrl,
                    'phone'            => $this->driver_phone,
                    'license_no'       => $this->license_no,
                    'vehicle_no'       => $this->driver_vehicle_no,
                    'vehicle_type'     => $this->driver_vehicle_type,
                    'address'          => $this->driver_address,
                    'experience_years' => $this->experience_years,
                    'is_active'        => $this->driver_is_active,
                ]);
            } else {
                $user = User::create([
                    'name'            => $this->driver_name,
                    'email'           => $this->driver_email,
                    'mobile_number'   => $this->driver_phone,
                    'password'        => Hash::make('123456'),
                    'role'            => 'driver',
                    'organization_id' => $this->organizationId,
                    'is_active'       => true,
                ]);
                DriverDetail::create([
                    'user_id'          => $user->id,
                    'organization_id'  => $this->organizationId,
                    'image'            => $imageUrl,
                    'phone'            => $this->driver_phone,
                    'license_no'       => $this->license_no,
                    'vehicle_no'       => $this->driver_vehicle_no,
                    'vehicle_type'     => $this->driver_vehicle_type,
                    'address'          => $this->driver_address,
                    'experience_years' => $this->experience_years,
                    'is_active'        => true,
                ]);
            }

            DB::commit();
            $this->loadAvailableDrivers();
            unset($this->statistics);
            $this->notification()->success('Success!', $this->editDriverId ? 'Driver updated' : 'Driver added');
            $this->closeDriverModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notification()->error('Error!', 'Failed to save driver: ' . $e->getMessage());
        }
    }

    public function confirmDeleteDriver(int $id): void { $this->pendingDeleteDriverId = $id; }
    public function cancelDeleteDriver(): void { $this->pendingDeleteDriverId = null; }
    public function executeDeleteDriver(): void
    {
        if (!$this->pendingDeleteDriverId) return;
        DB::beginTransaction();
        try {
            $driver = DriverDetail::with('user')->findOrFail($this->pendingDeleteDriverId);
            if ($driver->image) {
                $old = parse_url($driver->image, PHP_URL_PATH);
                if ($old) Storage::disk('s3')->delete(ltrim($old, '/'));
            }
            $driver->user?->delete();
            $driver->delete();
            DB::commit();
            $this->loadAvailableDrivers();
            unset($this->statistics);
            $this->notification()->success('Deleted!', 'Driver removed');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notification()->error('Error!', 'Failed to delete driver');
        }
        $this->pendingDeleteDriverId = null;
    }

    public function toggleDriverStatus(int $id): void
    {
        $driver = DriverDetail::findOrFail($id);
        $driver->update(['is_active' => !$driver->is_active]);
        $driver->user?->update(['is_active' => !$driver->user->is_active]);
        $this->loadAvailableDrivers();
        unset($this->statistics);
        $this->notification()->success('Updated!', 'Driver status changed');
    }

    public function closeDriverModal(): void
    {
        $this->driverModal = false;
        $this->editDriverId = null;
        $this->resetDriverForm();
        $this->resetValidation();
    }

    private function resetDriverForm(): void
    {
        $this->reset([
            'driver_name', 'driver_email', 'driver_phone',
            'license_no', 'driver_vehicle_no', 'driver_vehicle_type',
            'driver_address', 'experience_years', 'driver_is_active',
            'driver_image', 'driver_image_existing',
        ]);
        $this->driver_is_active = true;
    }

    // ═══════════════════════════════ ROUTES ══════════════════════════════════
    public function createTransport(): void
    {
        $this->resetTransportForm();
        $this->editTransportId = null;
        $this->transportModal  = true;
    }

    public function editTransport(int $id): void
    {
        $t = Transportation::findOrFail($id);
        $this->editTransportId     = $t->id;
        $this->route_name          = $t->route_name;
        $this->driver_detail_id    = $t->driver_detail_id;
        $this->pickup_time         = $t->pickup_time ?? '';
        $this->monthly_fee         = (float) $t->monthly_fee;
        $this->capacity            = (int) $t->capacity;
        $this->transport_is_active = $t->is_active;
        $this->transportModal      = true;
    }

    public function saveTransport(): void
    {
        $this->validate([
            'route_name'       => 'required|string|max:255',
            'driver_detail_id' => 'required|exists:driver_details,id',
            'pickup_time'      => 'nullable|string|max:20',
            'monthly_fee'      => 'nullable|numeric|min:0',
            'capacity'         => 'nullable|integer|min:0',
        ]);

        $driver = DriverDetail::find($this->driver_detail_id);

        $data = [
            'organization_id'  => $this->organizationId,
            'route_name'       => $this->route_name,
            'vehicle_no'       => $driver?->vehicle_no,
            'vehicle_type'     => $driver?->vehicle_type,
            'driver_detail_id' => $this->driver_detail_id,
            'pickup_time'      => $this->pickup_time ?: null,
            'monthly_fee'      => $this->monthly_fee,
            'capacity'         => $this->capacity,
            'is_active'        => $this->transport_is_active,
        ];

        try {
            if ($this->editTransportId) {
                Transportation::findOrFail($this->editTransportId)->update($data);
            } else {
                Transportation::create($data);
            }
            unset($this->statistics);
            $this->notification()->success('Success!', $this->editTransportId ? 'Route updated' : 'Route created');
            $this->closeTransportModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error!', 'Failed to save route: ' . $e->getMessage());
        }
    }

    public function confirmDeleteRoute(int $id): void { $this->pendingDeleteRouteId = $id; }
    public function cancelDeleteRoute(): void { $this->pendingDeleteRouteId = null; }
    public function executeDeleteRoute(): void
    {
        if (!$this->pendingDeleteRouteId) return;
        try {
            Transportation::findOrFail($this->pendingDeleteRouteId)->delete();
            unset($this->statistics);
            $this->notification()->success('Deleted!', 'Route deleted');
        } catch (\Exception $e) {
            $this->notification()->error('Error!', 'Failed to delete route');
        }
        $this->pendingDeleteRouteId = null;
    }

    public function toggleTransportStatus(int $id): void
    {
        $t = Transportation::findOrFail($id);
        $t->update(['is_active' => !$t->is_active]);
        unset($this->statistics);
        $this->notification()->success('Updated!', 'Route status changed');
    }

    public function closeTransportModal(): void
    {
        $this->transportModal  = false;
        $this->editTransportId = null;
        $this->resetTransportForm();
        $this->resetValidation();
    }

    private function resetTransportForm(): void
    {
        $this->reset(['route_name', 'driver_detail_id', 'pickup_time', 'monthly_fee', 'capacity', 'transport_is_active']);
        $this->transport_is_active = true;
        $this->monthly_fee = 0;
        $this->capacity = 0;
    }

    public function render()
    {
        return view('livewire.accounts.transport', [
            'transportations' => $this->getTransportations(),
            'drivers'         => $this->getDrivers(),
            'routeOptions'    => $this->getRouteOptions(),
        ]);
    }

    private function getRouteOptions()
    {
        if (!$this->organizationId) return collect();
        return Transportation::where('organization_id', $this->organizationId)
            ->orderBy('route_name')->get(['id', 'route_name']);
    }

    private function getTransportations()
    {
        if (!$this->organizationId) return collect()->paginate($this->perPage);

        $query = Transportation::with(['driver.user', 'students'])
            ->where('organization_id', $this->organizationId);

        if ($this->search) {
            $query->where(fn($q) => $q->where('route_name', 'like', '%' . $this->search . '%'));
        }
        if ($this->filterDriver !== '') {
            $query->where('driver_detail_id', $this->filterDriver);
        }
        if ($this->filterStatus !== '') {
            $query->where('is_active', (bool) $this->filterStatus);
        }

        // Order by pickup time (earliest first); routes without a time go last
        return $query->orderByRaw('pickup_time IS NULL, pickup_time ASC')
            ->orderBy('route_name')
            ->paginate($this->perPage);
    }

    private function getDrivers()
    {
        if (!$this->organizationId) return collect()->paginate($this->perPage);

        $query = DriverDetail::with(['user', 'transportations'])
            ->where('organization_id', $this->organizationId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('license_no', 'like', '%' . $this->search . '%')
                  ->orWhere('vehicle_no', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', '%' . $this->search . '%'));
            });
        }
        if ($this->filterRoute !== '') {
            $query->whereHas('transportations', fn($q) => $q->where('id', $this->filterRoute));
        }
        if ($this->filterStatus !== '') {
            $query->where('is_active', (bool) $this->filterStatus);
        }

        return $query->orderByDesc('created_at')->paginate($this->perPage);
    }
}
