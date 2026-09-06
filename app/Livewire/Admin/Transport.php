<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesTransportFees;
use App\Models\Admin\DriverDetail;
use App\Models\Admin\Transportation;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    /** Domain used for the stand-in address when a driver is saved without an email. */
    public const DRIVER_EMAIL_DOMAIN = '@drivers.superlms.local';

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
    public string $driver_address      = '';
    public int    $experience_years    = 0;
    public bool   $driver_is_active    = true;
    public $driver_image;                    // uploaded file
    public ?string $driver_image_existing = null;
    public array  $driver_routes       = [];  // route ids this driver covers (multi-select)

    // ─── Transportation (Route) Form ───────────────────────
    public string $route_name          = '';
    /** Vehicle types this route runs — one route row is created per type. */
    public array   $route_vehicle_types = [];
    /** Group key of the route being edited (a group is one row per type). */
    public ?string $editTransportGroup  = null;
    public ?int   $driver_detail_id    = null;
    public string $pickup_time         = '';
    public string $drop_time           = '';
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
    /** Route group key pending deletion (a group is one row per vehicle type). */
    public ?string $pendingDeleteRouteId = null;

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
            // Counted as the listing shows them: one route per group, not one
            // per vehicle-type row.
            'routes'          => Transportation::where('organization_id', $orgId)
                ->where('is_active', true)
                ->distinct()
                ->count(DB::raw('COALESCE(route_group, id)')),
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
        $this->driver_email        = $this->isPlaceholderDriverEmail($driver->user->email ?? '')
            ? ''
            : ($driver->user->email ?? '');
        $this->driver_phone        = $driver->phone ?? '';
        $this->license_no          = $driver->license_no ?? '';
        $this->driver_vehicle_no   = $driver->vehicle_no ?? '';
        $this->driver_address      = $driver->address ?? '';
        $this->experience_years    = $driver->experience_years ?? 0;
        $this->driver_is_active    = $driver->is_active;
        $this->driver_image        = null;
        $this->driver_image_existing = $driver->image;
        $this->driver_routes       = $driver->transportations()->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->driverModal         = true;
    }

    public function saveDriver(): void
    {
        $rules = [
            'driver_name'         => 'required|string|max:255',
            'driver_email'        => 'nullable|email|max:255',
            'driver_phone'        => 'required|regex:/^[6-9]\d{9}$/',
            'license_no'          => 'nullable|string|max:50',
            'driver_vehicle_no'   => 'nullable|string|max:30',
            'driver_address'      => 'nullable|string|max:500',
            'experience_years'    => 'nullable|integer|min:0|max:50',
            'driver_image'        => 'nullable|image|max:1024', // 1 MB
        ];
        if (!$this->editDriverId) {
            $rules['driver_email'] = 'nullable|email|unique:users,email';
        }
        $this->validate($rules, [
            'driver_phone.required' => 'Mobile number is required.',
            'driver_phone.regex' => 'Enter a valid 10-digit mobile number.',
            'driver_image.max'   => 'Photo must be 1 MB or smaller.',
        ]);

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
                $driver->user->update([
                    'name'          => $this->driver_name,
                    'email'         => $this->driver_email !== '' ? $this->driver_email : $driver->user->email,
                    'mobile_number' => $this->driver_phone,
                ]);
                $driver->update([
                    'image'            => $imageUrl,
                    'phone'            => $this->driver_phone,
                    'license_no'       => $this->license_no,
                    'vehicle_no'       => $this->driver_vehicle_no,
                    'address'          => $this->driver_address,
                    'experience_years' => $this->experience_years,
                    'is_active'        => $this->driver_is_active,
                ]);
                $driverDetailId = $driver->id;
            } else {
                $user = User::create([
                    'name'            => $this->driver_name,
                    'email'           => $this->driver_email !== ''
                        ? $this->driver_email
                        : $this->placeholderDriverEmail($this->driver_phone),
                    'mobile_number'   => $this->driver_phone,
                    'password'        => Hash::make('123456'),
                    'role'            => 'driver',
                    'organization_id' => $this->organizationId,
                    'is_active'       => true,
                ]);
                $driver = DriverDetail::create([
                    'user_id'          => $user->id,
                    'organization_id'  => $this->organizationId,
                    'image'            => $imageUrl,
                    'phone'            => $this->driver_phone,
                    'license_no'       => $this->license_no,
                    'vehicle_no'       => $this->driver_vehicle_no,
                    'address'          => $this->driver_address,
                    'experience_years' => $this->experience_years,
                    'is_active'        => true,
                ]);
                $driverDetailId = $driver->id;
            }

            // Assign this driver to the selected routes (and unassign deselected).
            $this->syncDriverRoutes($driverDetailId);

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

    /**
     * Drivers can be saved without an email, but users.email is NOT NULL and
     * unique - mint a placeholder from the (required) mobile number instead.
     */
    private function isPlaceholderDriverEmail(?string $email): bool
    {
        return $email !== null && str_ends_with($email, self::DRIVER_EMAIL_DOMAIN);
    }

    private function placeholderDriverEmail(string $phone): string
    {
        $base  = 'driver' . ($phone !== '' ? '.' . $phone : '') . '.' . $this->organizationId;
        $email = $base . self::DRIVER_EMAIL_DOMAIN;
        $i     = 1;
        while (User::where('email', $email)->exists()) {
            $email = $base . '-' . $i++ . self::DRIVER_EMAIL_DOMAIN;
        }

        return $email;
    }

    /**
     * Point the selected routes at this driver and release any route that used
     * to belong to the driver but is no longer selected (driver_detail_id 0 =
     * "no driver", matching the table's NOT NULL default).
     */
    private function syncDriverRoutes(int $driverId): void
    {
        $orgId    = $this->organizationId;
        $selected = array_values(array_filter(array_map('intval', $this->driver_routes)));

        if (!empty($selected)) {
            Transportation::where('organization_id', $orgId)
                ->whereIn('id', $selected)
                ->update(['driver_detail_id' => $driverId]);
        }

        Transportation::where('organization_id', $orgId)
            ->where('driver_detail_id', $driverId)
            ->when(!empty($selected), fn($q) => $q->whereNotIn('id', $selected))
            ->update(['driver_detail_id' => 0]);
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
            'license_no', 'driver_vehicle_no',
            'driver_address', 'experience_years', 'driver_is_active',
            'driver_image', 'driver_image_existing', 'driver_routes',
        ]);
        $this->driver_is_active = true;
    }

    // ═══════════════════════════════ ROUTES ══════════════════════════════════
    public function createTransport(): void
    {
        $this->resetTransportForm();
        $this->editTransportId    = null;
        $this->editTransportGroup = null;
        $this->transportModal     = true;
    }

    /** Every row of one route group, oldest first. */
    private function groupRows(string $group)
    {
        return Transportation::where('organization_id', $this->organizationId)
            ->where('route_group', $group)
            ->orderBy('id')
            ->get();
    }

    /**
     * Edit a whole route group: the shared details come from its first row and
     * the vehicle types from every row in it.
     */
    public function editTransport(string $group): void
    {
        $rows  = $this->groupRows($group);
        $first = $rows->first();

        if (!$first) {
            $this->notification()->error('Error!', 'Route not found');
            return;
        }

        $this->editTransportGroup   = $group;
        $this->editTransportId      = $first->id;
        $this->route_name           = $first->route_name;
        $this->route_vehicle_types  = $rows->pluck('vehicle_type')->filter()->unique()->values()->all();
        $this->driver_detail_id     = $first->driver_detail_id ?: null;
        $this->pickup_time          = $first->pickup_time ?? '';
        $this->drop_time            = $first->drop_time ?? '';
        $this->monthly_fee          = (float) $first->monthly_fee;
        $this->capacity             = (int) $first->capacity;
        $this->transport_is_active  = (bool) $first->is_active;
        $this->transportModal       = true;
    }

    public function saveTransport(): void
    {
        $this->validate([
            'route_name'            => 'required|string|max:255',
            'route_vehicle_types'   => 'required|array|min:1',
            'route_vehicle_types.*' => 'string|max:50',
            'pickup_time'           => 'nullable|string|max:20',
            'drop_time'             => 'nullable|string|max:20',
            'monthly_fee'           => 'nullable|numeric|min:0|max:9999999',
            'capacity'              => 'nullable|integer|min:0|max:1000',
        ], [
            'route_vehicle_types.required' => 'Pick at least one vehicle type.',
            'route_vehicle_types.min'      => 'Pick at least one vehicle type.',
        ]);

        $types = array_values(array_unique(array_filter($this->route_vehicle_types)));

        // Shared across every row of the group. The driver is assigned from the
        // Driver form, so it is never written here.
        $shared = [
            'organization_id' => $this->organizationId,
            'route_name'      => $this->route_name,
            'pickup_time'     => $this->pickup_time ?: null,
            'drop_time'       => $this->drop_time ?: null,
            'monthly_fee'     => $this->monthly_fee,
            'capacity'        => $this->capacity,
            'is_active'       => $this->transport_is_active,
        ];

        try {
            DB::transaction(function () use ($types, $shared) {
                if ($this->editTransportGroup) {
                    $this->updateRouteGroup($this->editTransportGroup, $types, $shared);
                    return;
                }

                // One row per vehicle type, tied together by a shared group key.
                $group = (string) Str::uuid();
                foreach ($types as $type) {
                    Transportation::create($shared + [
                        'vehicle_type' => $type,
                        'route_group'  => $group,
                    ]);
                }
            });

            unset($this->statistics);

            $this->notification()->success(
                'Success!',
                $this->editTransportGroup
                    ? 'Route updated'
                    : (count($types) > 1
                        ? count($types) . ' routes created — one per vehicle type'
                        : 'Route created')
            );

            $this->closeTransportModal();
        } catch (\Exception $e) {
            $this->notification()->error('Error!', 'Failed to save route: ' . $e->getMessage());
        }
    }

    /**
     * Apply the edit across a group: shared fields on every row, a new row for
     * each newly ticked vehicle type, and rows dropped for types that were
     * unticked — except any that still has students riding it.
     */
    private function updateRouteGroup(string $group, array $types, array $shared): void
    {
        $rows     = $this->groupRows($group);
        $existing = $rows->pluck('vehicle_type')->filter()->values()->all();

        foreach ($rows as $row) {
            $row->update($shared);
        }

        foreach (array_diff($types, $existing) as $type) {
            Transportation::create($shared + [
                'vehicle_type' => $type,
                'route_group'  => $group,
            ]);
        }

        $kept = [];
        foreach ($rows as $row) {
            if (in_array($row->vehicle_type, $types, true)) {
                continue;
            }

            if ($row->students()->count() > 0) {
                $kept[] = $row->vehicle_type;
                continue;
            }

            $row->delete();
        }

        if ($kept) {
            $this->notification()->warning(
                'Kept in place',
                implode(', ', $kept) . ' still has students assigned, so it was not removed.'
            );
        }
    }

    public function confirmDeleteRoute(string $group): void { $this->pendingDeleteRouteId = $group; }
    public function cancelDeleteRoute(): void { $this->pendingDeleteRouteId = null; }

    public function executeDeleteRoute(): void
    {
        if (!$this->pendingDeleteRouteId) return;

        try {
            // Deleting a route deletes every vehicle-type row under it.
            $this->groupRows((string) $this->pendingDeleteRouteId)->each->delete();
            unset($this->statistics);
            $this->notification()->success('Deleted!', 'Route deleted');
        } catch (\Exception $e) {
            $this->notification()->error('Error!', 'Failed to delete route');
        }

        $this->pendingDeleteRouteId = null;
    }

    public function toggleTransportStatus(string $group): void
    {
        $rows = $this->groupRows($group);
        if ($rows->isEmpty()) return;

        // One switch for the whole route: if any row is live, they all go off.
        $next = !$rows->contains(fn ($r) => (bool) $r->is_active);
        $rows->each->update(['is_active' => $next]);

        unset($this->statistics);
        $this->notification()->success('Updated!', 'Route status changed');
    }

    public function closeTransportModal(): void
    {
        $this->transportModal     = false;
        $this->editTransportId    = null;
        $this->editTransportGroup = null;
        $this->resetTransportForm();
        $this->resetValidation();
    }

    private function resetTransportForm(): void
    {
        $this->reset([
            'route_name', 'route_vehicle_types', 'driver_detail_id',
            'pickup_time', 'drop_time', 'monthly_fee', 'capacity', 'transport_is_active',
        ]);
        $this->transport_is_active = true;
        $this->monthly_fee = 0;
        $this->capacity = 0;
    }

    public function render()
    {
        return view('livewire.admin.transport', [
            'transportations' => $this->getTransportations(),
            'drivers'         => $this->getDrivers(),
            'routeOptions'    => $this->getRouteOptions(),
        ]);
    }

    /**
     * Every route row, labelled with its vehicle type — the driver form and the
     * filters assign per vehicle-type route, not per route name.
     */
    private function getRouteOptions()
    {
        if (!$this->organizationId) return collect();

        return Transportation::where('organization_id', $this->organizationId)
            ->orderBy('route_name')
            ->orderBy('vehicle_type')
            ->get(['id', 'route_name', 'vehicle_type'])
            ->each(function ($r) {
                $r->label = $r->route_name . ($r->vehicle_type ? ' — ' . $r->vehicle_type : '');
            });
    }

    /**
     * The routes listing shows ONE row per route, with its vehicle types beside
     * it — even though each type is its own row underneath, so a driver can be
     * assigned to the Bus and the Van separately.
     */
    private function getTransportations()
    {
        if (!$this->organizationId) {
            return new LengthAwarePaginator([], 0, $this->perPage);
        }

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
        $rows = $query->orderByRaw('pickup_time IS NULL, pickup_time ASC')
            ->orderBy('route_name')
            ->orderBy('id')
            ->get();

        $groups = $rows
            ->groupBy(fn ($r) => $r->route_group ?: 'r' . $r->id)
            ->map(function ($rows, $key) {
                $first = $rows->first();

                return (object) [
                    'key'           => (string) $key,
                    'route_name'    => $first->route_name,
                    'vehicle_types' => $rows->pluck('vehicle_type')->filter()->unique()->values()->all(),
                    'driver'        => $first->driver,
                    'driver_names'  => $rows->map(fn ($r) => $r->driver?->user?->name)->filter()->unique()->values()->all(),
                    'vehicle_nos'   => $rows->map(fn ($r) => $r->driver?->vehicle_no)->filter()->unique()->values()->all(),
                    'pickup_time'   => $first->pickup_time,
                    'drop_time'     => $first->drop_time,
                    'monthly_fee'   => (float) $first->monthly_fee,
                    'capacity'      => (int) $first->capacity,
                    'students'      => $rows->sum(fn ($r) => $r->students->count()),
                    'is_active'     => $rows->contains(fn ($r) => (bool) $r->is_active),
                ];
            })
            ->values();

        $page = $this->getPage();

        return new LengthAwarePaginator(
            $groups->forPage($page, $this->perPage)->values(),
            $groups->count(),
            $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
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
