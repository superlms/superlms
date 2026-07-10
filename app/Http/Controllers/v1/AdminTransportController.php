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
            'routes'          => Transportation::where('organization_id', $orgId)->where('is_active', true)->count(),
            'students'        => DB::table('transportation_students')->where('organization_id', $orgId)->count(),
            'monthly_revenue' => round($revenue, 2),
        ], 'Transport stats fetched.');
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

    /** GET /admin/transport/route-options — id + name for pickers. */
    public function routeOptions()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $opts = Transportation::where('organization_id', $user->organization_id)
            ->orderBy('route_name')->get(['id', 'route_name']);
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
                Transportation::create($data);
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

        $rows = DriverDetail::with(['user:id,name,email,is_active', 'transportations:id,route_name,driver_detail_id'])
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
            ->map(fn ($d) => [
                'id'               => $d->id,
                'name'             => $d->user?->name ?? '—',
                'email'            => $d->user?->email ?? '',
                'phone'            => $d->phone,
                'license_no'       => $d->license_no,
                'vehicle_no'       => $d->vehicle_no,
                'vehicle_type'     => $d->vehicle_type,
                'address'          => $d->address,
                'experience_years' => (int) $d->experience_years,
                'image'            => $d->image,
                'is_active'        => (bool) $d->is_active,
                'routes'           => $d->transportations->map(fn ($t) => ['id' => $t->id, 'name' => $t->route_name])->values(),
            ]);

        return $this->success(['drivers' => $rows, 'vehicle_types' => ['Bus', 'Mini Bus', 'Van', 'Auto', 'Car', 'Other']], 'Drivers fetched.');
    }

    /** POST /admin/transport/drivers (multipart)  (and /drivers/{id} for update) */
    public function saveDriver(Request $request, $id = null)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $rules = [
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|max:255',
            'phone'            => 'nullable|regex:/^[6-9]\d{9}$/',
            'license_no'       => 'nullable|string|max:50',
            'vehicle_no'       => 'nullable|string|max:30',
            'vehicle_type'     => 'nullable|string|max:50',
            'address'          => 'nullable|string|max:500',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'image'            => 'nullable|image|max:1024',
        ];
        if (!$id) $rules['email'] = 'required|email|unique:users,email';
        if ($err = $this->validateWith($request, $rules, [
            'phone.regex' => 'Enter a valid 10-digit mobile number.',
            'image.max'   => 'Photo must be 1 MB or smaller.',
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
                    'vehicle_type'     => $request->vehicle_type,
                    'address'          => $request->address,
                    'experience_years' => $request->experience_years ?: 0,
                    'is_active'        => $request->boolean('is_active', true),
                ];

                if ($id) {
                    $driver = DriverDetail::where('organization_id', $orgId)->findOrFail($id);
                    $driver->user?->update(['name' => $request->name, 'email' => $request->email]);
                    $driver->update($detail);
                    $dId = $driver->id;
                } else {
                    $u = User::create([
                        'name'            => $request->name,
                        'email'           => $request->email,
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

        $rows = $q->orderBy('full_name')->limit(50)->get()->map(fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->full_name,
            'admission_no' => $s->admission_no,
            'class'        => ($s->standard->name ?? '') . ($s->section ? '-' . $s->section->name : ''),
        ]);

        return $this->success(['students' => $rows], 'Fee students fetched.');
    }

    /** GET /admin/transport/fees/summary?student_id= */
    public function feeSummary(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['student_id' => 'required|integer'])) return $err;
        $orgId = $user->organization_id;

        $student = StudentDetail::with(['user:id,name,image', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)->find($request->student_id);
        if (!$student) return $this->error('Student not found.', 404);

        $route   = $this->studentRoute($orgId, $student->id);
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
                'date'         => $p->payment_date,
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
            ],
            'route'        => $route ? ['id' => $route->id, 'name' => $route->route_name] : null,
            'monthly'      => $monthly,
            'months_count' => $this->billableMonthsCount($months),
            'annual'       => $annual,
            'paid'         => $paid,
            'remaining'    => max(0, $annual - $paid),
            'payments'     => $payments,
            'month_status' => $this->monthStatuses($monthly, $months, $paid),
        ], 'Fee summary fetched.');
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
