<?php

namespace App\Livewire\Accounts;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeSettings;
use App\Models\Student\StudentDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class Penalties extends Component
{
    use WireUiActions;

    public $penaltyPerDay = '0';
    public $cycleType = 'monthly';
    public $dueDayOfMonth = '10';
    public $penaltyAnalytics = [];

    public function mount(): void
    {
        $this->loadPenaltySettings();
        $this->loadPenaltyAnalytics();
    }

    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function loadPenaltySettings(): void
    {
        $settings = FeeSettings::getForOrg($this->orgId());
        $this->penaltyPerDay = $settings->penalty_per_day;
        $this->cycleType = $settings->cycle_type;
        $this->dueDayOfMonth = $settings->due_day_of_month;
    }

    public function saveSettings(): void
    {
        $this->validate([
            'penaltyPerDay' => 'required|numeric|min:0',
            'cycleType' => 'required|in:monthly,quarterly',
            'dueDayOfMonth' => 'required|integer|min:1|max:31',
        ]);

        FeeSettings::updateOrCreate(
            ['organization_id' => $this->orgId()],
            [
                'penalty_per_day' => $this->penaltyPerDay,
                'cycle_type' => $this->cycleType,
                'due_day_of_month' => $this->dueDayOfMonth,
                'is_active' => true,
            ]
        );

        $this->notification()->success('Fee settings saved successfully!');
        $this->loadPenaltyAnalytics();
    }

    public function loadPenaltyAnalytics(): void
    {
        $orgId = $this->orgId();
        $settings = FeeSettings::getForOrg($orgId);

        if ($settings->penalty_per_day <= 0) {
            $this->penaltyAnalytics = [
                'total' => 0,
                'students' => 0,
                'days_overdue' => 0,
                'penalty_per_day' => 0,
            ];
            return;
        }

        $dueDay = $settings->due_day_of_month;
        $today = Carbon::today();
        $dueDate = Carbon::createFromDate($today->year, $today->month, min($dueDay, $today->daysInMonth));

        if ($today->day <= $dueDay) {
            $dueDate = $dueDate->subMonth();
        }

        $studentCount = StudentDetail::where('organization_id', $orgId)->count();
        $paidThisMonth = FeePayment::where('organization_id', $orgId)
            ->whereMonth('payment_date', $today->month)
            ->whereYear('payment_date', $today->year)
            ->distinct('student_detail_id')
            ->count('student_detail_id');

        $overdueStudents = max(0, $studentCount - $paidThisMonth);
        $daysOverdue = max(0, $today->diffInDays($dueDate));
        $totalPenalty = $overdueStudents * $daysOverdue * $settings->penalty_per_day;

        $this->penaltyAnalytics = [
            'total' => $totalPenalty,
            'students' => $overdueStudents,
            'days_overdue' => $daysOverdue,
            'penalty_per_day' => $settings->penalty_per_day,
        ];
    }

    public function render()
    {
        return view('livewire.accounts.penalties', [
            'penaltyAnalytics' => $this->penaltyAnalytics,
        ]);
    }
}
