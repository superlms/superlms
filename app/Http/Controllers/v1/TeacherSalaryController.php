<?php

namespace App\Http\Controllers\v1;

use App\Livewire\Admin\Payroll as PayrollPage;
use App\Models\Admin\AdminEmployee;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Support\Carbon;

/**
 * The teacher app's Salaries: the signed-in teacher's own salary account, read
 * only — the same months the admin panel's Payroll → Mark Salary shows for
 * them (the salary for each month from their attendance, what was paid
 * against it and when), with the totals.
 *
 * It runs the panel component's own salaryAccount, so the teacher sees exactly
 * what the school sees. A teacher with no payroll row yet (the school has not
 * opened Payroll since they joined) gets an empty account, not an error.
 */
class TeacherSalaryController extends ApiController
{
    /** GET /v1/teacher/salaries */
    public function index()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;
        if (!$user->organization_id) {
            return $this->error('No school assigned to this account.', 403);
        }

        $teacherId = TeacherDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->value('id');

        $employee = $teacherId
            ? AdminEmployee::forOrganization($user->organization_id)
                ->where('teacher_detail_id', $teacherId)
                ->first()
            : null;

        if (!$employee) {
            return $this->success([
                'employee' => null,
                'months'   => [],
                'payable'  => 0.0,
                'paid'     => 0.0,
                'balance'  => 0.0,
                'paid_leaves_a_month' => PayrollPage::PAID_LEAVES_A_MONTH,
            ], 'No salary set up yet.');
        }

        $page = new PayrollPage();
        $page->salaryEmpId = (string) $employee->id;
        $account = (fn () => $this->salaryAccount($employee))->call($page);

        return $this->success([
            'employee' => [
                'name'         => $employee->name,
                'designation'  => $employee->designation,
                'salary'       => (float) $employee->salary,
                'joining_date' => $employee->joining_date
                    ? Carbon::parse($employee->joining_date)->format('Y-m-d')
                    : null,
            ],
            'months'   => $account['months'],
            'payable'  => (float) $account['payable'],
            'paid'     => (float) $account['paid'],
            'balance'  => (float) $account['balance'],
            'paid_leaves_a_month' => PayrollPage::PAID_LEAVES_A_MONTH,
        ], 'Salary fetched.');
    }
}
