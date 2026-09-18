<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\DriverDetail;
use App\Models\Admin\Transportation;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\StudentDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\TransportReceipt;

/**
 * School-admin Transport module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Transport.php + Concerns/HandlesTransportFees.php —
 * routes, drivers, transport students (per-month billing) and the fee summary /
 * payments flow. Annual transport fee = route.monthly_fee × billable months
 * (June excluded by default). Org-scoped, role-gated to admin / sub-admin.
 */
class AdminTransportController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    // Academic-year month order (Apr first, Mar last).
    private const MONTHS_ORDER = [
        'apr' => 'April', 'may' => 'May', 'jun' => 'June',
        'jul' => 'July', 'aug' => 'August', 'sep' => 'September',
        'oct' => 'October', 'nov' => 'November', 'dec' => 'December',
        'jan' => 'January', 'feb' => 'February', 'mar' => 'March',
    ];
    private const MONTH_NUM = [
        'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9,
        'oct' => 10, 'nov' => 11, 'dec' => 12, 'jan' => 1, 'feb' => 2, 'mar' => 3,
    ];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        return [$user, null];
    }

    // ══════════════════════════ STATS ══════════════════════════

    /** GET /admin/transport/stats */
    public function stats()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $revenue = Transportation::where('organization_id', $orgId)->where('is_active', true)
            ->withCount('students')->get()->sum(fn ($t) => $t->monthly_fee * $t->students_count);

        return $this->success([
            'drivers'         => DriverDetail::where('organization_id', $orgId)->where('is_active', true)->count(),
            // One route per group, as the listing shows them — not one per
            // vehicle-type row.
            'routes'          => Transportation::where('organization_id', $orgId)->where('is_active', true)
                ->distinct()->count(DB::raw('COALESCE(route_group, id)')),
            'students'        => DB::table('transportation_students')->where('organization_id', $orgId)->count(),
            'monthly_revenue' => round($revenue, 2),
        ], 'Transport stats fetched.');
    }

    // ══════════════════════════ ROUTE GROUPS ══════════════════════════
    //
    // A route runs one or more vehicle types, and each type is its own row
    // (so a driver is set per type), tied together by route_group — the
    // panel's Routes tab. A row from before groups has none; its key is
    // 'r{id}'.

    private const VEHICLE_TYPES = ['Bus', 'Mini Bus', 'Van', 'Auto', 'Car', 'Other'];

    private function groupKey(Transportation $t): string
    {
        return $t->route_group ?: 'r' . $t->id;
    }

    /** Every row of one route. */
    private function groupQuery(int $orgId, string $key)
    {
        return Transportation::where('organization_id', $orgId)
            ->where(function ($q) use ($key) {
                $q->where('route_group', $key);
                if (preg_match('/^r(\d+)$/', $key, $m)) {
                    $q->orWhere(fn ($qq) => $qq->whereNull('route_group')->where('id', (int) $m[1]));
                }
            });
    }

    /** A route as the panel lists it: the rows' shared details, their types, drivers and riders. */
    private function shapeGroup(string $key, $rows): array
    {
        $first = $rows->first();

        return [
            'key'           => $key,
            'route_name'    => $first->route_name,
            'vehicle_types' => $rows->pluck('vehicle_type')->filter()->unique()->values(),
            'driver_names'  => $rows->map(fn ($r) => $r->driver?->user?->name)->filter()->unique()->values(),
            'driver_image'  => $rows->map(fn ($r) => $r->driver?->image)->filter()->first(),
            'vehicle_nos'   => $rows->map(fn ($r) => $r->driver?->vehicle_no)->filter()->unique()->values(),
            'pickup_time'   => $first->pickup_time,
            'drop_time'     => $first->drop_time,
            'monthly_fee'   => (float) $first->monthly_fee,
            // The route's year at the default eleven months (June off).
            'annual_fee'    => round((float) $first->monthly_fee * 11, 2),
            'capacity'      => (int) $first->capacity,
            'students'      => (int) $rows->sum('students_count'),
            'is_active'     => $rows->contains(fn ($r) => (bool) $r->is_active),
            'rows'          => $rows->map(fn ($r) => [
                'id'           => $r->id,
                'vehicle_type' => $r->vehicle_type,
                'driver_id'    => $r->driver_detail_id ?: null,
                'driver_name'  => $r->driver?->user?->name,
                'driver_phone' => $r->driver?->phone,
                'vehicle_no'   => $r->driver?->vehicle_no,
                'students'     => (int) $r->students_count,
                'is_active'    => (bool) $r->is_active,
            ])->values(),
        ];
    }

    /** GET /admin/transport/route-groups?search=&driver_id=&status= */
    public function routeGroups(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $rows = Transportation::with('driver.user:id,name')->withCount('students')
            ->where('organization_id', $user->organization_id)
            ->when($request->filled('search'), fn ($q) => $q->where('route_name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('driver_id'), fn ($q) => $q->where('driver_detail_id', $request->driver_id))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', (bool) $request->status))
            // Earliest pickup first; routes without a time go last.
            ->orderByRaw('pickup_time IS NULL, pickup_time ASC')->orderBy('route_name')->orderBy('id')
            ->get();

        $groups = $rows->groupBy(fn ($r) => $this->groupKey($r))
            ->map(fn ($rows, $key) => $this->shapeGroup((string) $key, $rows))
            ->values();

        return $this->success(['routes' => $groups, 'vehicle_types' => self::VEHICLE_TYPES], 'Routes fetched.');
    }

    /** GET /admin/transport/route-groups/{key} */
    public function routeGroup($key)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $rows = $this->groupQuery($user->organization_id, (string) $key)
            ->with('driver.user:id,name')->withCount('students')->orderBy('id')->get();
        if ($rows->isEmpty()) return $this->error('Route not found.', 404);

        return $this->success($this->shapeGroup((string) $key, $rows), 'Route fetched.');
    }

    /**
     * POST /admin/transport/route-groups (and /route-groups/{key} to edit one).
     *
     * One row per vehicle type ticked. Editing sets the shared details on
     * every row, adds a row for a newly ticked type and drops the row of a
     * type unticked — unless students still ride it. The driver is set from
     * the driver form, never here.
     */
    public function saveRouteGroup(Request $request, $key = null)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'route_name'      => 'required|string|max:255',
            'vehicle_types'   => 'required|array|min:1',
            'vehicle_types.*' => 'string|max:50',
            'pickup_time'     => 'nullable|string|max:20',
            'drop_time'       => 'nullable|string|max:20',
            'monthly_fee'     => 'nullable|numeric|min:0|max:9999999',
            'capacity'        => 'nullable|integer|min:0|max:1000',
            'is_active'       => 'nullable|boolean',
        ], [
            'vehicle_types.required' => 'Pick at least one vehicle type.',
            'vehicle_types.min'      => 'Pick at least one vehicle type.',
        ])) return $err;

        $orgId = $user->organization_id;
        $types = array_values(array_unique(array_filter((array) $request->vehicle_types)));
        $shared = [
            'organization_id' => $orgId,
            'route_name'      => $request->route_name,
            'pickup_time'     => $request->pickup_time ?: null,
            'drop_time'       => $request->drop_time ?: null,
            'monthly_fee'     => $request->monthly_fee ?: 0,
            'capacity'        => $request->capacity ?: 0,
            'is_active'       => $request->boolean('is_active', true),
        ];

        $rows = null;
        if ($key !== null) {
            $rows = $this->groupQuery($orgId, (string) $key)->withCount('students')->orderBy('id')->get();
            if ($rows->isEmpty()) return $this->error('Route not found.', 404);
        }

        try {
            $kept = [];
            $group = DB::transaction(function () use ($key, $rows, $types, $shared, &$kept) {
                if ($rows === null) {
                    $group = (string) Str::uuid();
                    foreach ($types as $type) {
                        Transportation::create($shared + ['vehicle_type' => $type, 'route_group' => $group]);
                    }
                    return $group;
                }

                $group    = (string) $key;
                $existing = $rows->pluck('vehicle_type')->filter()->values()->all();

                foreach ($rows as $row) {
                    $row->update($shared + ['route_group' => $group]);
                }
                foreach (array_diff($types, $existing) as $type) {
                    Transportation::create($shared + ['vehicle_type' => $type, 'route_group' => $group]);
                }
                foreach ($rows as $row) {
                    if (in_array($row->vehicle_type, $types, true)) continue;
                    if ($row->students_count > 0) {
                        $kept[] = $row->vehicle_type ?: 'A vehicle';
                        continue;
                    }
                    $row->delete();
                }
                return $group;
            });

            $message = $key !== null
                ? 'Route updated'
                : (count($types) > 1 ? count($types) . ' routes created — one per vehicle type' : 'Route created');
            if ($kept) {
                $message .= '. ' . implode(', ', $kept) . ' still has students assigned, so it was not removed.';
            }

            return $this->success(['key' => $group, 'kept' => $kept], $message, $key !== null ? 200 : 201);
        } catch (\Throwable $e) {
            return $this->error('Failed to save route: ' . $e->getMessage(), 500);
        }
    }

    /** POST /admin/transport/route-groups/{key}/toggle — one switch for the whole route. */
    public function toggleRouteGroup($key)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $rows = $this->groupQuery($user->organization_id, (string) $key)->get();
        if ($rows->isEmpty()) return $this->error('Route not found.', 404);

        // If any row is live, they all go off.
        $next = !$rows->contains(fn ($r) => (bool) $r->is_active);
        $rows->each->update(['is_active' => $next]);

        return $this->success(['is_active' => $next], 'Route status changed');
    }

    /** DELETE /admin/transport/route-groups/{key} — every vehicle-type row, and its students come off it. */
    public function deleteRouteGroup($key)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $rows = $this->groupQuery($user->organization_id, (string) $key)->get();
        if ($rows->isEmpty()) return $this->error('Route not found.', 404);

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $row->students()->detach();
                $row->delete();
            }
        });

        return $this->success(null, 'Route deleted');
    }

    // ══════════════════════════ ROUTES ══════════════════════════

    /** GET /admin/transport/routes?search=&driver_id=&status= */
    public function routes(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $rows = Transportation::with('driver.user:id,name')->withCount('students')
            ->where('organization_id', $orgId)
            ->when($request->filled('search'), fn ($q) => $q->where('route_name', 'like', '%' . $request->search . '%'))
            ->when($request->filled('driver_id'), fn ($q) => $q->where('driver_detail_id', $request->driver_id))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', (bool) $request->status))
            ->orderByRaw('pickup_time IS NULL, pickup_time ASC')->orderBy('route_name')->get()
            ->map(fn ($t) => [
                'id'             => $t->id,
                'route_name'     => $t->route_name,
                'driver_id'      => $t->driver_detail_id ?: null,
                'driver_name'    => $t->driver?->user?->name ?? null,
                'pickup_time'    => $t->pickup_time,
                'drop_time'      => $t->drop_time,
                'monthly_fee'    => (float) $t->monthly_fee,
                'capacity'       => (int) $t->capacity,
                'students_count' => $t->students_count,
                'is_active'      => (bool) $t->is_active,
            ]);

        return $this->success(['routes' => $rows], 'Routes fetched.');
    }

    /**
     * GET /admin/transport/route-options — every vehicle-type row for pickers,
     * labelled "Route 1 — Bus": drivers and students are set per type.
     */
    public function routeOptions()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $opts = Transportation::where('organization_id', $user->organization_id)
            ->orderBy('route_name')->orderBy('vehicle_type')
            ->get(['id', 'route_name', 'vehicle_type', 'route_group', 'is_active'])
            ->map(fn ($r) => [
                'id'           => $r->id,
                'route_name'   => $r->route_name,
                'vehicle_type' => $r->vehicle_type,
                'group'        => $this->groupKey($r),
                'is_active'    => (bool) $r->is_active,
                'label'        => $r->route_name . ($r->vehicle_type ? ' — ' . $r->vehicle_type : ''),
            ]);
        return $this->success(['routes' => $opts], 'Route options fetched.');
    }

    /** POST /admin/transport/routes  (and /routes/{id} for update) */
    public function saveRoute(Request $request, $id = null)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'route_name'  => 'required|string|max:255',
            'pickup_time' => 'nullable|string|max:20',
            'drop_time'   => 'nullable|string|max:20',
            'monthly_fee' => 'nullable|numeric|min:0|max:9999999',
            'capacity'    => 'nullable|integer|min:0|max:1000',
        ])) return $err;

        $data = [
            'organization_id' => $user->organization_id,
            'route_name'      => $request->route_name,
            'pickup_time'     => $request->pickup_time ?: null,
            'drop_time'       => $request->drop_time ?: null,
            'monthly_fee'     => $request->monthly_fee ?: 0,
            'capacity'        => $request->capacity ?: 0,
            'is_active'       => $request->boolean('is_active', true),
        ];

        try {
            if ($id) {
                $route = Transportation::where('organization_id', $user->organization_id)->find($id);
                if (!$route) return $this->error('Route not found.', 404);
                // Driver assignment is managed from the driver form — don't touch it here.
                unset($data['organization_id']);
                $route->update($data);
            } else {
                // A group of its own, so the grouped listing keeps it whole.
                Transportation::create($data + ['route_group' => (string) Str::uuid()]);
            }
            return $this->success(null, $id ? 'Route updated' : 'Route created', $id ? 200 : 201);
        } catch (\Throwable $e) {
            return $this->error('Failed to save route: ' . $e->getMessage(), 500);
        }
    }

    /** POST /admin/transport/routes/{id}/toggle */
    public function toggleRoute($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $route = Transportation::where('organization_id', $user->organization_id)->find($id);
        if (!$route) return $this->error('Route not found.', 404);
        $route->update(['is_active' => !$route->is_active]);
        return $this->success(['is_active' => (bool) $route->is_active], 'Route status changed');
    }

    /** DELETE /admin/transport/routes/{id} */
    public function deleteRoute($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $route = Transportation::where('organization_id', $user->organization_id)->find($id);
        if (!$route) return $this->error('Route not found.', 404);
        $route->delete();
        return $this->success(null, 'Route deleted');
    }

    // ══════════════════════════ DRIVERS ══════════════════════════

    /** GET /admin/transport/drivers?search=&route_id=&status= */
    public function drivers(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $rows = DriverDetail::with(['user:id,name,email,is_active', 'transportations:id,route_name,vehicle_type,driver_detail_id'])
            ->where('organization_id', $orgId)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($qq) use ($s) {
                    $qq->where('license_no', 'like', "%{$s}%")
                        ->orWhere('vehicle_no', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%"));
                });
            })
            ->when($request->filled('route_id'), fn ($q) => $q->whereHas('transportations', fn ($t) => $t->where('id', $request->route_id)))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', (bool) $request->status))
            ->orderByDesc('created_at')->get()
            ->map(fn ($d) => $this->shapeDriver($d));

        return $this->success(['drivers' => $rows, 'vehicle_types' => self::VEHICLE_TYPES], 'Drivers fetched.');
    }

    /** GET /admin/transport/drivers/{id} */
    public function driver($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $d = DriverDetail::with(['user:id,name,email,is_active', 'transportations:id,route_name,vehicle_type,driver_detail_id'])
            ->where('organization_id', $user->organization_id)->find($id);
        if (!$d) return $this->error('Driver not found.', 404);

        return $this->success($this->shapeDriver($d), 'Driver fetched.');
    }

    /** A driver saved without an email carries a stand-in address; it reads as none. */
    private function isPlaceholderDriverEmail(?string $email): bool
    {
        return $email !== null && str_ends_with($email, \App\Livewire\Admin\Transport::DRIVER_EMAIL_DOMAIN);
    }

    /** users.email is required and unique, so a driver without one gets a stand-in from the mobile number. */
    private function placeholderDriverEmail(string $phone, int $orgId): string
    {
        $base  = 'driver' . ($phone !== '' ? '.' . $phone : '') . '.' . $orgId;
        $email = $base . \App\Livewire\Admin\Transport::DRIVER_EMAIL_DOMAIN;
        $i     = 1;
        while (User::where('email', $email)->exists()) {
            $email = $base . '-' . $i++ . \App\Livewire\Admin\Transport::DRIVER_EMAIL_DOMAIN;
        }
        return $email;
    }

    private function shapeDriver(DriverDetail $d): array
    {
        $email = $d->user?->email ?? '';

        return [
            'id'               => $d->id,
            'name'             => $d->user?->name ?? '—',
            'email'            => $this->isPlaceholderDriverEmail($email) ? '' : $email,
            'phone'            => $d->phone,
            'license_no'       => $d->license_no,
            'vehicle_no'       => $d->vehicle_no,
            'vehicle_type'     => $d->vehicle_type,
            'address'          => $d->address,
            'experience_years' => (int) $d->experience_years,
            'image'            => $d->image,
            'is_active'        => (bool) $d->is_active,
            'routes'           => $d->transportations->map(fn ($t) => [
                'id'           => $t->id,
                'name'         => $t->route_name,
                'vehicle_type' => $t->vehicle_type,
                'label'        => $t->route_name . ($t->vehicle_type ? ' — ' . $t->vehicle_type : ''),
            ])->values(),
        ];
    }

    /**
     * POST /admin/transport/drivers (multipart)  (and /drivers/{id} for update)
     *
     * As the panel's driver form: a name and a 10-digit mobile number, an
     * email only if they have one, a photo of up to 1 MB, and the routes they
     * drive. A new driver signs in with 123456.
     */
    public function saveDriver(Request $request, $id = null)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $rules = [
            'name'             => 'required|string|max:255',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'required|regex:/^[6-9]\d{9}$/',
            'license_no'       => 'nullable|string|max:50',
            'vehicle_no'       => 'nullable|string|max:30',
            'vehicle_type'     => 'nullable|string|max:50',
            'address'          => 'nullable|string|max:500',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'image'            => 'nullable|image|max:1024',
        ];
        if (!$id) $rules['email'] = 'nullable|email|max:255|unique:users,email';
        if ($err = $this->validateWith($request, $rules, [
            'phone.required' => 'Mobile number is required.',
            'phone.regex'    => 'Enter a valid 10-digit mobile number.',
            'image.max'      => 'Photo must be 1 MB or smaller.',
        ])) return $err;

        // routes[] may arrive as an array or a JSON/CSV string (multipart).
        $routes = $request->input('routes', []);
        if (is_string($routes)) $routes = json_decode($routes, true) ?: array_filter(explode(',', $routes));
        $routes = array_values(array_filter(array_map('intval', (array) $routes)));

        try {
            $driverId = DB::transaction(function () use ($request, $orgId, $user, $id, $routes) {
                $imageUrl = null;
                if ($id) {
                    $driver = DriverDetail::findOrFail($id);
                    $imageUrl = $driver->image;
                }
                if ($request->hasFile('image')) {
                    if ($imageUrl) $this->deleteFile($imageUrl);
                    $path = $request->file('image')->store('admin/drivers/photos', 's3');
                    Storage::disk('s3')->setVisibility($path, 'public');
                    $imageUrl = Storage::disk('s3')->url($path);
                }

                $detail = [
                    'image'            => $imageUrl,
                    'phone'            => $request->phone,
                    'license_no'       => $request->license_no,
                    'vehicle_no'       => $request->vehicle_no,
                    'address'          => $request->address,
                    'experience_years' => $request->experience_years ?: 0,
                    'is_active'        => $request->boolean('is_active', true),
                ];
                // The vehicle type lives on the route now; only an older app still sends it.
                if ($request->has('vehicle_type')) {
                    $detail['vehicle_type'] = $request->vehicle_type;
                }

                $email = trim((string) $request->email);
                if ($id) {
                    $driver = DriverDetail::where('organization_id', $orgId)->findOrFail($id);
                    // An emptied email keeps the one on file.
                    $driver->user?->update([
                        'name'          => $request->name,
                        'email'         => $email !== '' ? $email : $driver->user->email,
                        'mobile_number' => $request->phone,
                    ]);
                    $driver->update($detail);
                    $dId = $driver->id;
                } else {
                    $u = User::create([
                        'name'            => $request->name,
                        'email'           => $email !== '' ? $email : $this->placeholderDriverEmail((string) $request->phone, $orgId),
                        'mobile_number'   => $request->phone,
                        'password'        => Hash::make('123456'),
                        'role'            => 'driver',
                        'organization_id' => $orgId,
                        'is_active'       => true,
                    ]);
                    $driver = DriverDetail::create(array_merge($detail, [
                        'user_id'         => $u->id,
                        'organization_id' => $orgId,
                        'is_active'       => true,
                    ]));
                    $dId = $driver->id;
                }

                $this->syncDriverRoutes($orgId, $dId, $routes);
                return $dId;
            });

            return $this->success(['id' => $driverId], $id ? 'Driver updated' : 'Driver added', $id ? 200 : 201);
        } catch (\Throwable $e) {
            return $this->error('Failed to save driver: ' . $e->getMessage(), 500);
        }
    }

    /** Point selected routes at this driver; release routes no longer selected (0 = none). */
    private function syncDriverRoutes(int $orgId, int $driverId, array $selected): void
    {
        if (!empty($selected)) {
            Transportation::where('organization_id', $orgId)->whereIn('id', $selected)
                ->update(['driver_detail_id' => $driverId]);
        }
        Transportation::where('organization_id', $orgId)->where('driver_detail_id', $driverId)
            ->when(!empty($selected), fn ($q) => $q->whereNotIn('id', $selected))
            ->update(['driver_detail_id' => 0]);
    }

    /** POST /admin/transport/drivers/{id}/toggle */
    public function toggleDriver($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $driver = DriverDetail::where('organization_id', $user->organization_id)->find($id);
        if (!$driver) return $this->error('Driver not found.', 404);
        $driver->update(['is_active' => !$driver->is_active]);
        $driver->user?->update(['is_active' => !$driver->user->is_active]);
        return $this->success(['is_active' => (bool) $driver->is_active], 'Driver status changed');
    }

    /** DELETE /admin/transport/drivers/{id} */
    public function deleteDriver($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        try {
            DB::transaction(function () use ($id, $user) {
                $driver = DriverDetail::with('user')->where('organization_id', $user->organization_id)->findOrFail($id);
                if ($driver->image) $this->deleteFile($driver->image);
                // release routes
                Transportation::where('organization_id', $user->organization_id)
                    ->where('driver_detail_id', $driver->id)->update(['driver_detail_id' => 0]);
                $driver->user?->delete();
                $driver->delete();
            });
            return $this->success(null, 'Driver removed');
        } catch (\Throwable $e) {
            return $this->error('Failed to delete driver: ' . $e->getMessage(), 500);
        }
    }

    // ══════════════════════════ TRANSPORT STUDENTS ══════════════════════════

    /** GET /admin/transport/students?route_id=&search= */
    public function students(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId   = $user->organization_id;
        $routeId = $request->filled('route_id') ? (int) $request->route_id : null;

        $route = $routeId ? Transportation::with('driver.user')->where('organization_id', $orgId)->find($routeId) : null;
        if ($routeId && !$route) return $this->success(['students' => []], 'No students.');

        $students = StudentDetail::with(['user:id,name,image', 'standard:id,name', 'section:id,name', 'transportations.driver.user'])
            ->where('student_details.organization_id', $orgId)
            ->whereHas('transportations', fn ($q) => $routeId ? $q->where('transportations.id', $routeId) : $q)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->search;
                $q->where(fn ($qq) => $qq->where('full_name', 'like', "%{$s}%")->orWhere('admission_no', 'like', "%{$s}%"));
            })
            ->orderBy('full_name')->get();

        $ids = $students->pluck('id')->all();

        $pivotQuery = DB::table('transportation_students')->where('organization_id', $orgId)->whereIn('student_detail_id', $ids);
        if ($routeId) $pivotQuery->where('transportation_id', $routeId);
        $pivotRows = $pivotQuery->get()->groupBy('student_detail_id');

        $paidQuery = TransportFeePayment::where('organization_id', $orgId)->whereIn('student_detail_id', $ids);
        if ($routeId) $paidQuery->where('transportation_id', $routeId);
        $paidMap = $paidQuery->selectRaw('student_detail_id, SUM(amount) as paid')->groupBy('student_detail_id')->pluck('paid', 'student_detail_id');

        $rows = $students->map(function ($s) use ($route, $pivotRows, $paidMap) {
            $rowRoute = $route ?: $s->transportations->first();
            $monthly  = (float) ($rowRoute?->monthly_fee ?? 0);
            $pivot    = $pivotRows->get($s->id, collect())->first(fn ($p) => $rowRoute ? $p->transportation_id == $rowRoute->id : true);
            $months   = $this->normalizeBillableMonths($pivot->billable_months ?? null);
            $annual   = $this->studentAnnualFee($monthly, $months);
            $paid     = (float) ($paidMap[$s->id] ?? 0);

            return [
                'student_detail_id' => $s->id,
                'name'         => $s->user->name ?? ($s->full_name ?? '—'),
                'admission_no' => $s->admission_no,
                'class'        => ($s->standard->name ?? '') . ($s->section ? '-' . $s->section->name : ''),
                'image'        => $s->user->image ?? null,
                'route_id'     => $rowRoute?->id,
                'route'        => $rowRoute?->route_name ?? '—',
                'vehicle_type' => $rowRoute?->vehicle_type,
                'driver'       => $rowRoute?->driver?->user?->name ?? '—',
                'monthly'      => $monthly,
                'months'       => $months,
                'months_count' => $this->billableMonthsCount($months),
                'annual'       => $annual,
                'paid'         => $paid,
                'remaining'    => max(0, $annual - $paid),
            ];
        });

        return $this->success(['students' => $rows, 'months_order' => self::MONTHS_ORDER], 'Transport students fetched.');
    }

    /** POST /admin/transport/students/months — {student_detail_id, transportation_id, months:{apr:true,...}} */
    public function saveStudentMonths(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'student_detail_id' => 'required|integer',
            'transportation_id' => 'required|integer',
            'months'            => 'required|array',
        ])) return $err;

        $months = [];
        foreach (array_keys(self::MONTHS_ORDER) as $k) {
            $months[$k] = (bool) ($request->months[$k] ?? false);
        }

        $affected = DB::table('transportation_students')
            ->where('organization_id', $user->organization_id)
            ->where('transportation_id', $request->transportation_id)
            ->where('student_detail_id', $request->student_detail_id)
            ->update(['billable_months' => json_encode($months), 'updated_at' => now()]);

        if (!$affected) return $this->error('Assignment not found.', 404);
        return $this->success(null, 'Monthly fee schedule updated.');
    }

    /** DELETE /admin/transport/students — {student_detail_id, transportation_id} */
    public function removeStudent(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'student_detail_id' => 'required|integer',
            'transportation_id' => 'required|integer',
        ])) return $err;

        DB::table('transportation_students')
            ->where('organization_id', $user->organization_id)
            ->where('transportation_id', $request->transportation_id)
            ->where('student_detail_id', $request->student_detail_id)
            ->delete();

        return $this->success(null, 'Student removed from this route.');
    }

    // ══════════════════════════ FEES ══════════════════════════

    /** GET /admin/transport/fees/students?route_id=&search= — students eligible for fee summary. */
    public function feeStudents(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $q = StudentDetail::with(['standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->whereHas('transportations', fn ($t) => $request->filled('route_id') ? $t->where('transportations.id', $request->route_id) : $t);

        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(fn ($qq) => $qq->where('full_name', 'like', "%{$s}%")->orWhere('admission_no', 'like', "%{$s}%"));
        } elseif (!$request->filled('route_id')) {
            return $this->success(['students' => []], 'Search for a student.');
        }

        // A route's riders come whole, as the panel's student select lists them;
        // a search is capped.
        $rows = $q->orderBy('full_name')
            ->when(!$request->filled('route_id'), fn ($qq) => $qq->limit(50))
            ->get()->map(fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->full_name,
            'admission_no' => $s->admission_no,
            'class'        => ($s->standard->name ?? '') . ($s->section ? '-' . $s->section->name : ''),
        ]);

        return $this->success(['students' => $rows], 'Fee students fetched.');
    }

    /**
     * GET /admin/transport/fees/summary?student_id=&route_id=
     *
     * route_id, from a route's student list, reads the fee on that route when
     * the student rides it; otherwise the student's route, as the panel picks it.
     */
    public function feeSummary(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'student_id' => 'required|integer',
            'route_id'   => 'nullable|integer',
        ])) return $err;
        $orgId = $user->organization_id;

        $student = StudentDetail::with(['user:id,name,image', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)->find($request->student_id);
        if (!$student) return $this->error('Student not found.', 404);

        $route = $request->filled('route_id')
            ? Transportation::where('organization_id', $orgId)
                ->whereHas('students', fn ($q) => $q->where('student_details.id', $student->id))
                ->find((int) $request->route_id)
            : null;
        $route   = $route ?: $this->studentRoute($orgId, $student->id);
        $monthly = (float) ($route?->monthly_fee ?? 0);

        $pivot = $route ? DB::table('transportation_students')
            ->where('organization_id', $orgId)->where('transportation_id', $route->id)
            ->where('student_detail_id', $student->id)->first() : null;
        $months      = $this->normalizeBillableMonths($pivot->billable_months ?? null);
        $annual      = $this->studentAnnualFee($monthly, $months);

        $payments = TransportFeePayment::with('transportation:id,route_name')
            ->where('organization_id', $orgId)->where('student_detail_id', $student->id)
            ->orderByDesc('payment_date')->orderByDesc('id')->get()
            ->map(fn ($p) => [
                'id'           => $p->id,
                'amount'       => (float) $p->amount,
                'mode'         => $p->payment_mode,
                'date'         => $p->payment_date?->format('Y-m-d'),
                'receipt'      => $p->receipt_number,
                'route'        => $p->transportation?->route_name,
                'remark'       => $p->remark,
            ]);
        $paid = (float) $payments->sum('amount');

        return $this->success([
            'student' => [
                'id'    => $student->id,
                'name'  => $student->full_name,
                'admission_no' => $student->admission_no,
                'class' => ($student->standard->name ?? '') . ($student->section ? '-' . $student->section->name : ''),
                'image' => $student->user->image ?? null,
                'email'  => $student->email ?: null,
                'mobile' => $student->phone ?: null,
            ],
            'route'        => $route ? [
                'id'           => $route->id,
                'name'         => $route->route_name,
                'vehicle_type' => $route->vehicle_type,
                'pickup_time'  => $route->pickup_time,
                'drop_time'    => $route->drop_time,
                'driver'       => $route->driver?->user?->name,
            ] : null,
            'monthly'      => $monthly,
            'months'       => $months,
            'months_count' => $this->billableMonthsCount($months),
            'annual'       => $annual,
            'paid'         => $paid,
            'remaining'    => max(0, $annual - $paid),
            'payments'     => $payments,
            // The months up to this one (older apps read this) …
            'month_status' => $this->monthStatuses($monthly, $months, $paid),
            // … and the whole year, as the panel's Monthly Fee Status shows it.
            'months_year'  => $this->yearStatuses($monthly, $months, $paid),
        ], 'Fee summary fetched.');
    }

    /** GET /admin/transport/fees/payment/{id}/pdf — a payment's receipt, as the panel prints it. */
    public function receiptPdf($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $payment = TransportFeePayment::with(TransportReceipt::WITH)
            ->where('organization_id', $user->organization_id)
            ->find($id);
        if (!$payment) return $this->error('Receipt not found.', 404);

        return TransportReceipt::pdf($payment)->stream("Transport_Receipt_{$payment->receipt_number}.pdf");
    }

    /** POST /admin/transport/fees/payment — {student_id, amount, mode, date, remark?} */
    public function recordPayment(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'student_id' => 'required|integer',
            'amount'     => 'required|numeric|min:1',
            'mode'       => 'required|in:cash,online,cheque,upi',
            'date'       => 'required|date',
            'remark'     => 'nullable|string|max:255',
        ])) return $err;

        $orgId   = $user->organization_id;
        $student = StudentDetail::where('organization_id', $orgId)->find($request->student_id);
        if (!$student) return $this->error('Student not found.', 404);
        $route = $this->studentRoute($orgId, $student->id);

        try {
            $payment = DB::transaction(function () use ($request, $orgId, $student, $route, $user) {
                return TransportFeePayment::create([
                    'organization_id'   => $orgId,
                    'transportation_id' => $route?->id,
                    'student_detail_id' => $student->id,
                    'amount'            => $request->amount,
                    'payment_mode'      => $request->mode,
                    'payment_date'      => $request->date,
                    'receipt_number'    => $this->generateReceiptNumber($orgId),
                    'academic_year'     => now()->format('Y') . '-' . substr((string) (now()->year + 1), -2),
                    'remark'            => $request->remark ?: null,
                    'submitted_by'      => $user->id,
                ]);
            });
            return $this->success(['receipt' => $payment->receipt_number], 'Transport fee payment recorded.', 201);
        } catch (\Throwable $e) {
            return $this->error('Failed to record payment: ' . $e->getMessage(), 500);
        }
    }

    /** DELETE /admin/transport/fees/payment/{id} */
    public function deletePayment($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        TransportFeePayment::where('id', $id)->where('organization_id', $user->organization_id)->delete();
        return $this->success(null, 'Payment removed.');
    }

    // ══════════════════════════ FEE HELPERS ══════════════════════════

    private function defaultBillableMonths(): array
    {
        $flags = [];
        foreach (array_keys(self::MONTHS_ORDER) as $key) $flags[$key] = ($key !== 'jun');
        return $flags;
    }

    private function normalizeBillableMonths($raw): array
    {
        if (empty($raw)) return $this->defaultBillableMonths();
        if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
        $defaults = $this->defaultBillableMonths();
        $merged = [];
        foreach (array_keys(self::MONTHS_ORDER) as $key) {
            $merged[$key] = array_key_exists($key, (array) $raw) ? (bool) $raw[$key] : $defaults[$key];
        }
        return $merged;
    }

    private function billableMonthsCount(array $months): int
    {
        return count(array_filter($months));
    }

    private function studentAnnualFee($monthlyFee, array $months): float
    {
        return round((float) $monthlyFee * $this->billableMonthsCount($months), 2);
    }

    private function studentRoute(int $orgId, int $studentDetailId): ?Transportation
    {
        return Transportation::where('organization_id', $orgId)
            ->whereHas('students', fn ($q) => $q->where('student_details.id', $studentDetailId))
            ->orderByDesc('is_active')->first();
    }

    /** Month-by-month fee status; total paid applied sequentially over enabled months. */
    private function monthStatuses(float $monthly, array $months, float $paid): array
    {
        $now           = Carbon::now();
        $curMonthStart = $now->copy()->startOfMonth();
        $startYear     = $now->month >= 4 ? $now->year : $now->year - 1;

        $remaining = $paid;
        $rows = [];
        foreach (array_keys(self::MONTHS_ORDER) as $key) {
            if (empty($months[$key])) continue;
            $mnum       = self::MONTH_NUM[$key];
            $year       = $mnum >= 4 ? $startYear : $startYear + 1;
            $monthStart = Carbon::create($year, $mnum, 1)->startOfMonth();
            if ($monthStart->gt($curMonthStart)) continue;

            $alloc     = $monthly > 0 ? max(0, min($remaining, $monthly)) : 0;
            $remaining = max(0, $remaining - $alloc);
            $status    = ($monthly > 0 && $alloc >= $monthly) ? 'paid' : ($alloc > 0 ? 'partial' : 'unpaid');

            $rows[] = [
                'key'    => $key,
                'label'  => self::MONTHS_ORDER[$key],
                'amount' => $monthly,
                'paid'   => round($alloc, 2),
                'status' => $status,
            ];
        }
        return $rows;
    }

    /**
     * Every month of the academic year, as the panel's Monthly Fee Status
     * draws it. What has been paid covers the billed months oldest first; a
     * month the student isn't billed for is not_used, and one not begun yet
     * is upcoming.
     */
    private function yearStatuses(float $monthly, array $months, float $paid): array
    {
        $now           = Carbon::now();
        $curMonthStart = $now->copy()->startOfMonth();
        $startYear     = $now->month >= 4 ? $now->year : $now->year - 1;

        $remaining = $paid;
        $rows = [];
        foreach (array_keys(self::MONTHS_ORDER) as $key) {
            $mnum       = self::MONTH_NUM[$key];
            $year       = $mnum >= 4 ? $startYear : $startYear + 1;
            $monthStart = Carbon::create($year, $mnum, 1)->startOfMonth();
            $billable   = !empty($months[$key]);
            $alloc      = 0.0;

            if (!$billable) {
                $status = 'not_used';
            } elseif ($monthStart->gt($curMonthStart)) {
                $status = 'upcoming';
            } else {
                $alloc     = $monthly > 0 ? max(0, min($remaining, $monthly)) : 0;
                $remaining = max(0, $remaining - $alloc);
                $status    = ($monthly > 0 && $alloc >= $monthly) ? 'paid' : ($alloc > 0 ? 'partial' : 'unpaid');
            }

            $rows[] = [
                'key'        => $key,
                'label'      => self::MONTHS_ORDER[$key],
                'year'       => $year,
                'amount'     => $billable ? $monthly : 0.0,
                'paid'       => round($alloc, 2),
                'status'     => $status,
                'billable'   => $billable,
                'is_current' => $monthStart->equalTo($curMonthStart),
            ];
        }
        return $rows;
    }

    private function generateReceiptNumber(int $orgId): string
    {
        $year = now()->format('y');
        $base = "TRP{$orgId}{$year}";
        $last = TransportFeePayment::where('organization_id', $orgId)
            ->where('receipt_number', 'like', "{$base}%")->orderByDesc('id')->first();
        $serial = $last ? ((int) substr($last->receipt_number, -5) + 1) : 1;
        return $base . str_pad((string) $serial, 5, '0', STR_PAD_LEFT);
    }

    private function deleteFile(string $url): void
    {
        try {
            $path = parse_url($url, PHP_URL_PATH);
            if ($path) Storage::disk('s3')->delete(ltrim($path, '/'));
        } catch (\Throwable $e) {
            logger()->warning('Driver photo delete failed: ' . $e->getMessage());
        }
    }
}
