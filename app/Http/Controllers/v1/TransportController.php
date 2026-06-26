<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Transportation;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\DB;

class TransportController extends ApiController
{
    /**
     * Academic-year month order (April first, March last).
     * Keys match the JSON stored in transportation_students.billable_months.
     */
    private const MONTHS_ORDER = [
        'apr' => 'April',    'may' => 'May',      'jun' => 'June',
        'jul' => 'July',     'aug' => 'August',   'sep' => 'September',
        'oct' => 'October',  'nov' => 'November', 'dec' => 'December',
        'jan' => 'January',  'feb' => 'February', 'mar' => 'March',
    ];

    /**
     * GET /api/v1/transport/my-route
     *
     * Returns the transport route assigned to the authenticated student.
     * Only students (role=user) can call this.
     */
    public function myRoute()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->requireRole('user')) return $err;

        $student = StudentDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        // Active transport assigned to this student.
        // NB: query the relationship directly — activeTransportation() returns a
        // resolved model (or null), so chaining ->with() on it crashes (500) for
        // students who have no transport. Going through transportations() keeps
        // it a null-safe query builder.
        $transport = $student->transportations()
            ->where('is_active', true)
            ->with(['driver.user:id,name,email,image'])
            ->first();

        if (!$transport) {
            return $this->error('No transport route assigned to you.', 404);
        }

        $data = $this->formatTransport($transport);

        // Fee schedule is best-effort: never let a fee/data issue break the
        // whole screen — the route card should still load.
        try {
            $data['fees'] = $this->buildFeeSchedule($student, $transport);
        } catch (\Throwable $e) {
            report($e);
            $data['fees'] = null;
        }

        return $this->success($data, 'Transport route fetched successfully.');
    }

    /**
     * GET /api/v1/transport/routes
     *
     * All active routes for the school (teachers or admin can view).
     */
    public function routes()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $routes = Transportation::with(['driver.user:id,name,image'])
            ->where('organization_id', $user->organization_id)
            ->where('is_active', true)
            ->get()
            ->map(fn($t) => $this->formatTransport($t));

        return $this->success($routes, 'Transport routes fetched successfully.');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function formatTransport(Transportation $t): array
    {
        return [
            'id'              => $t->id,
            'route_name'      => $t->route_name,
            'pickup_location' => $t->pickup_location,
            'drop_location'   => $t->drop_location,
            'pickup_time'     => $t->pickup_time,
            'stops'           => $t->stops ?? [],
            'monthly_fee'     => (float) $t->monthly_fee,
            'capacity'        => $t->capacity,
            'vehicle_no'      => $t->driver?->vehicle_no,
            'driver'          => $t->driver ? [
                'id'          => $t->driver->id,
                'name'        => $t->driver->user?->name,
                'email'       => $t->driver->user?->email,
                'image'       => $t->driver->image ?? $t->driver->user?->image,
                'phone'       => $t->driver->phone,
                'license_no'  => $t->driver->license_no,
                'vehicle_no'  => $t->driver->vehicle_no,
                'vehicle_type' => $t->driver->vehicle_type,
            ] : null,
        ];
    }

    /**
     * Build the month-by-month transport fee schedule for a student on a route.
     *
     * The student's billable months come from the transportation_students pivot
     * (defaults to June off, all other 11 months on). Recorded payments are
     * allocated across billable months in academic order, so earlier months read
     * as "paid" and the first uncovered month onwards reads as "pending".
     */
    private function buildFeeSchedule(StudentDetail $student, Transportation $transport): array
    {
        $monthlyFee = (float) $transport->monthly_fee;

        $pivot = DB::table('transportation_students')
            ->where('organization_id', $transport->organization_id)
            ->where('transportation_id', $transport->id)
            ->where('student_detail_id', $student->id)
            ->first();

        $months = $this->normalizeBillableMonths($pivot->billable_months ?? null);

        $billableCount = count(array_filter($months));
        $annualFee     = round($monthlyFee * $billableCount, 2);

        $totalPaid = (float) TransportFeePayment::where('organization_id', $transport->organization_id)
            ->where('student_detail_id', $student->id)
            ->where('transportation_id', $transport->id)
            ->sum('amount');

        // Allocate the paid amount across billable months, oldest first.
        $remaining = $totalPaid;
        $schedule  = [];

        foreach (self::MONTHS_ORDER as $key => $label) {
            if (empty($months[$key])) {
                $schedule[] = [
                    'key'    => $key,
                    'month'  => $label,
                    'amount' => 0.0,
                    'status' => 'no_transport',
                ];
                continue;
            }

            if ($remaining >= $monthlyFee) {
                $status     = 'paid';
                $remaining -= $monthlyFee;
            } elseif ($remaining > 0) {
                $status    = 'partial';
                $remaining = 0;
            } else {
                $status = 'pending';
            }

            $schedule[] = [
                'key'    => $key,
                'month'  => $label,
                'amount' => $monthlyFee,
                'status' => $status,
            ];
        }

        return [
            'monthly_fee'  => $monthlyFee,
            'annual_fee'   => $annualFee,
            'months_count' => $billableCount,
            'total_paid'   => round($totalPaid, 2),
            'total_due'    => round(max(0, $annualFee - $totalPaid), 2),
            'schedule'     => $schedule,
        ];
    }

    /**
     * Normalize the stored billable_months value (null | JSON string | array)
     * into a complete apr..mar flag map. Null/empty falls back to the default
     * (June excluded, all other months billable).
     */
    private function normalizeBillableMonths($raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        $raw = (array) $raw;

        $flags = [];
        foreach (array_keys(self::MONTHS_ORDER) as $key) {
            $flags[$key] = array_key_exists($key, $raw)
                ? (bool) $raw[$key]
                : ($key !== 'jun'); // default: June off
        }
        return $flags;
    }
}
