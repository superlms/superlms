<?php

namespace App\Livewire\Admin;

use App\Models\Admin\Fee\CustomFeeItem;
use App\Models\Admin\Fee\CustomFeePayment;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Support\CustomFee as CustomFeeSupport;
use App\Support\NameOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * The Fee page of a school on Custom Fee (App\Support\CustomFee) — the
 * admin's Fee page shows it in place of its nine sections, and the accounts
 * desk has it as its Fees page. Three tabs, each picked Class → Section →
 * Student:
 *
 *   Add Fee     the student's own particulars; Add Fee in the header opens a
 *               panel to put any number of them (name and amount) on them at
 *               once, or change the ones they have.
 *   Submit Fee  the same student's fee and payments; Update Fee in the
 *               header opens a panel to take a payment.
 *   View Fee    only the students with a fee set: their particulars and
 *               payments, each with its receipt.
 */
class CustomFee extends Component
{
    use WireUiActions;

    /** 'admin' or 'accounts' — which panel's routes the receipts open on. */
    #[Locked]
    public string $panel = 'admin';

    public string $tab = 'add'; // add | submit | view

    // ── Class → Section → Student, shared by the three tabs ───────────────
    public $standardId = '';
    public $sectionId  = '';
    public $studentId  = '';

    // ── Add Fee panel ─────────────────────────────────────────────────────
    public bool $showFeePanel = false;
    /** [['id' => ?int, 'name' => string, 'amount' => string]] */
    public array $feeRows = [];

    // ── Update Fee (take a payment) panel ─────────────────────────────────
    public bool $showPayPanel = false;
    public $payStandardId  = '';
    public $paySectionId   = '';
    public $payStudentId   = '';
    public $payAmount      = '';
    public $payDate        = '';
    public $payMode        = 'cash';
    public $payCollectedBy = '';
    public $payRemark      = '';

    // ── Edit Payment Date popup ───────────────────────────────────────────
    public bool $showPaymentDateEdit = false;
    public ?int $editPaymentId       = null;
    public $editPaymentDate          = '';
    public array $editPaymentInfo    = [];

    public const MODES = [
        'cash'          => 'Cash',
        'online'        => 'Online',
        'cheque'        => 'Cheque',
        'bank_transfer' => 'Bank Transfer',
    ];

    public function mount(): void
    {
        $this->panel = request()->routeIs('accounts.*') ? 'accounts' : 'admin';
    }

    private function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    // ── Tabs and filters ──────────────────────────────────────────────────

    /** Each tab opens on a clean filter — Class → Section → Student picked afresh. */
    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['add', 'submit', 'view'], true) ? $tab : 'add';
        $this->clearFilters();
    }

    public function updatedStandardId(): void
    {
        $this->sectionId = '';
        $this->studentId = '';
    }

    public function updatedSectionId(): void
    {
        $this->studentId = '';
    }

    public function clearFilters(): void
    {
        $this->reset([
            'standardId', 'sectionId', 'studentId', 'showFeePanel', 'feeRows', 'showPayPanel',
            'showPaymentDateEdit', 'editPaymentId', 'editPaymentDate', 'editPaymentInfo',
        ]);
        $this->resetValidation();
    }

    /** A row of the class list opens that student. */
    public function pickStudent(int $id): void
    {
        $student = $this->student($id);
        if (!$student) {
            return;
        }
        $this->standardId = (string) $student->standard_id;
        $this->sectionId  = $this->sectionId !== '' ? (string) $student->section_id : '';
        $this->studentId  = (string) $student->id;
    }

    private function student(?int $id): ?StudentDetail
    {
        return $id
            ? StudentDetail::with(['user', 'standard', 'section'])->where('organization_id', $this->orgId())->find($id)
            : null;
    }

    private function hasFee(int $studentId): bool
    {
        return CustomFeeItem::where('organization_id', $this->orgId())->where('student_detail_id', $studentId)->exists();
    }

    private function sectionsOf($standardId)
    {
        return $standardId
            ? Section::where('organization_id', $this->orgId())->where('standard_id', $standardId)
                ->where('is_active', true)->orderBy('id')->get()
            : collect();
    }

    /** The class's students — every one, or only those with a fee set. */
    private function studentsOf($standardId, $sectionId, bool $withFeeOnly)
    {
        if (!$standardId) {
            return collect();
        }

        $orgId = $this->orgId();

        return StudentDetail::with('user')
            ->where('organization_id', $orgId)
            ->where('standard_id', $standardId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->when($withFeeOnly, fn ($q) => $q->whereIn('id',
                CustomFeeItem::where('organization_id', $orgId)->select('student_detail_id')))
            ->tap(fn ($q) => NameOrder::students($q))
            ->get();
    }

    // ── Add Fee: the student's particulars ────────────────────────────────

    /** Header's Add Fee — the panel, with the particulars the student already has. */
    public function openFeePanel(): void
    {
        $student = $this->student($this->studentId ? (int) $this->studentId : null);
        if (!$student) {
            $this->notification()->error('Select a student first.');
            return;
        }

        $this->feeRows = CustomFeeItem::where('organization_id', $this->orgId())
            ->where('student_detail_id', $student->id)->orderBy('id')->get()
            ->map(fn ($i) => ['id' => $i->id, 'name' => $i->fee_name, 'amount' => (string) (float) $i->amount])
            ->all();
        if (!$this->feeRows) {
            $this->feeRows = [['id' => null, 'name' => '', 'amount' => '']];
        }

        $this->resetValidation();
        $this->showFeePanel = true;
    }

    public function closeFeePanel(): void
    {
        $this->showFeePanel = false;
        $this->feeRows      = [];
        $this->resetValidation();
    }

    public function addFeeRow(): void
    {
        $this->feeRows[] = ['id' => null, 'name' => '', 'amount' => ''];
    }

    public function removeFeeRow(int $index): void
    {
        unset($this->feeRows[$index]);
        $this->feeRows = array_values($this->feeRows);
        if (!$this->feeRows) {
            $this->addFeeRow();
        }
    }

    /** Save the panel: new particulars added, changed ones updated, removed ones gone. */
    public function saveFee(): void
    {
        $student = $this->student($this->studentId ? (int) $this->studentId : null);
        if (!$student) {
            $this->notification()->error('Select a student first.');
            return;
        }

        // A row left wholly blank is ignored, not an error.
        $this->feeRows = array_values(array_filter(
            $this->feeRows,
            fn ($r) => trim((string) ($r['name'] ?? '')) !== '' || trim((string) ($r['amount'] ?? '')) !== '' || !empty($r['id'])
        ));
        if (!$this->feeRows) {
            $this->feeRows = [['id' => null, 'name' => '', 'amount' => '']];
            $this->addError('feeRows', 'Add at least one particular.');
            return;
        }

        $this->validate([
            'feeRows'          => 'required|array|min:1|max:100',
            'feeRows.*.name'   => 'required|string|max:255',
            'feeRows.*.amount' => 'required|numeric|min:0|max:99999999',
        ], [
            'feeRows.*.name.required'   => 'Enter the fee name.',
            'feeRows.*.amount.required' => 'Enter the amount.',
            'feeRows.*.amount.numeric'  => 'Enter a number.',
            'feeRows.*.amount.min'      => 'Cannot be below 0.',
        ]);

        $orgId = $this->orgId();

        DB::transaction(function () use ($orgId, $student) {
            $existing = CustomFeeItem::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)->get()->keyBy('id');
            $kept = [];

            foreach ($this->feeRows as $row) {
                $data = ['fee_name' => trim($row['name']), 'amount' => round((float) $row['amount'], 2)];
                $item = !empty($row['id']) ? $existing->get((int) $row['id']) : null;

                if ($item) {
                    $item->update($data);
                    $kept[] = $item->id;
                } else {
                    $kept[] = CustomFeeItem::create($data + [
                        'organization_id'   => $orgId,
                        'student_detail_id' => $student->id,
                        'created_by'        => Auth::id(),
                    ])->id;
                }
            }

            CustomFeeItem::where('organization_id', $orgId)->where('student_detail_id', $student->id)
                ->whereNotIn('id', $kept ?: [0])->delete();
        });

        $this->closeFeePanel();
        $this->notification()->success('Fee saved for ' . $this->nameOf($student) . '.');
    }

    public function confirmDeleteItem(int $id): void
    {
        $this->dialog()->confirm([
            'title'       => 'Remove this particular?',
            'description' => 'It comes off the student\'s fee. Payments already taken are kept.',
            'icon'        => 'error',
            'accept'      => ['label' => 'Yes, remove', 'method' => 'deleteItem', 'params' => $id],
            'reject'      => ['label' => 'Cancel'],
        ]);
    }

    public function deleteItem(int $id): void
    {
        CustomFeeItem::where('organization_id', $this->orgId())->whereKey($id)->delete();
        $this->notification()->success('Particular removed.');
    }

    // ── Submit Fee: take a payment ────────────────────────────────────────

    /** Header's Update Fee — the panel, readied for the student on screen. */
    public function openPayPanel(): void
    {
        $this->payStandardId  = (string) $this->standardId;
        $this->paySectionId   = (string) $this->sectionId;
        $this->payStudentId   = $this->studentId && $this->hasFee((int) $this->studentId) ? (string) $this->studentId : '';
        $this->payAmount      = '';
        $this->payDate        = today()->toDateString();
        $this->payMode        = 'cash';
        $this->payCollectedBy = Auth::user()->name ?? '';
        $this->payRemark      = '';
        $this->resetValidation();
        $this->showPayPanel = true;
    }

    public function closePayPanel(): void
    {
        $this->showPayPanel = false;
        $this->resetValidation();
    }

    public function updatedPayStandardId(): void
    {
        $this->paySectionId = '';
        $this->payStudentId = '';
    }

    public function updatedPaySectionId(): void
    {
        $this->payStudentId = '';
    }

    public function savePayment(): void
    {
        $this->validate([
            'payStudentId'   => 'required',
            'payAmount'      => 'required|numeric|min:1|max:99999999',
            'payDate'        => 'required|date|before_or_equal:today',
            'payMode'        => 'required|in:' . implode(',', array_keys(self::MODES)),
            'payCollectedBy' => 'required|string|max:255',
            'payRemark'      => 'nullable|string|max:255',
        ], [
            'payStudentId.required'   => 'Select the student.',
            'payDate.before_or_equal' => 'The date cannot be after today.',
            'payCollectedBy.required' => 'Enter who collected it.',
        ]);

        $student = $this->student((int) $this->payStudentId);
        if (!$student || !$this->hasFee($student->id)) {
            $this->addError('payStudentId', 'This student has no fee set yet — add it in Add Fee first.');
            return;
        }

        $orgId  = $this->orgId();
        $amount = round((float) $this->payAmount, 2);
        $due    = CustomFeeSupport::forStudent($orgId, $student->id)['balance'];
        if ($amount > $due + 0.001) {
            $this->addError('payAmount', $due > 0
                ? 'Only ₹' . number_format($due, 2) . ' is due from this student.'
                : 'This student has nothing left to pay.');
            return;
        }

        $payment = DB::transaction(fn () => CustomFeePayment::create([
            'organization_id'   => $orgId,
            'student_detail_id' => $student->id,
            'standard_id'       => $student->standard_id,
            'section_id'        => $student->section_id,
            'amount'            => $amount,
            'payment_mode'      => $this->payMode,
            'payment_date'      => $this->payDate,
            'submitted_by'      => trim($this->payCollectedBy),
            'remark'            => trim((string) $this->payRemark) ?: null,
            'created_by'        => Auth::id(),
        ]));

        // The page follows the student just paid for.
        $this->standardId = (string) $student->standard_id;
        if ($this->sectionId !== '') {
            $this->sectionId = (string) $student->section_id;
        }
        $this->studentId = (string) $student->id;

        $this->closePayPanel();
        $this->notification()->success('₹' . number_format($amount, 2) . ' received · ' . $payment->receipt_number);
    }

    // ── A payment's date, corrected (payments are never deleted) ──────────

    /** Submit Fee's pencil beside a payment: the Edit Payment Date popup. */
    public function openPaymentDateEdit(int $id): void
    {
        $payment = CustomFeePayment::where('organization_id', $this->orgId())->find($id);
        if (!$payment) {
            return;
        }

        $this->editPaymentId   = $payment->id;
        $this->editPaymentDate = optional($payment->payment_date)->toDateString() ?? '';
        $this->editPaymentInfo = [
            'receipt_number' => $payment->receipt_number,
            'amount'         => (float) $payment->amount,
        ];
        $this->resetValidation('editPaymentDate');
        $this->showPaymentDateEdit = true;
    }

    public function closePaymentDateEdit(): void
    {
        $this->reset(['showPaymentDateEdit', 'editPaymentId', 'editPaymentDate', 'editPaymentInfo']);
        $this->resetValidation('editPaymentDate');
    }

    /** Only the date changes — amount, mode, collector and receipt number stay as they were. */
    public function savePaymentDate(): void
    {
        $this->validate(
            ['editPaymentDate' => 'required|date|before_or_equal:today'],
            ['editPaymentDate.before_or_equal' => 'The date cannot be after today.']
        );

        $payment = $this->editPaymentId
            ? CustomFeePayment::where('organization_id', $this->orgId())->find($this->editPaymentId)
            : null;
        if (!$payment) {
            $this->closePaymentDateEdit();
            return;
        }

        $payment->update(['payment_date' => $this->editPaymentDate]);

        $this->closePaymentDateEdit();
        $this->notification()->success('Payment date updated · ' . $payment->receipt_number);
    }

    // ── Reading ───────────────────────────────────────────────────────────

    private function nameOf(StudentDetail $s): string
    {
        return $s->full_name ?: ($s->user->name ?? 'Student');
    }

    public function receiptUrl(int $paymentId): string
    {
        return route($this->panel . '.fee.custom-receipt', ['organization' => $this->orgId(), 'id' => $paymentId]);
    }

    /** The open student's fee, payments and where it stands. */
    private function ledger(?StudentDetail $student): ?array
    {
        if (!$student) {
            return null;
        }

        $orgId = $this->orgId();

        return [
            'student'  => $student,
            'name'     => $this->nameOf($student),
            'items'    => CustomFeeItem::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)->orderBy('id')->get(),
            'payments' => CustomFeePayment::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)
                ->orderByDesc('payment_date')->orderByDesc('id')->get(),
            'totals'   => CustomFeeSupport::forStudent($orgId, $student->id),
        ];
    }

    public function render()
    {
        $orgId = $this->orgId();

        $standards = Standard::where('organization_id', $orgId)->where('is_active', true)->inClassOrder()->get();
        $students  = $this->studentsOf($this->standardId, $this->sectionId, $this->tab !== 'add');
        $open      = $this->studentId ? $this->student((int) $this->studentId) : null;

        $schoolTotal     = CustomFeeSupport::schoolTotal($orgId);
        $schoolCollected = CustomFeeSupport::collected($orgId);

        return view('livewire.admin.custom-fee', [
            'standards'     => $standards,
            'sections'      => $this->sectionsOf($this->standardId),
            'students'      => $students,
            'classTotals'   => $this->standardId && !$open
                ? CustomFeeSupport::studentTotals($orgId, $students->pluck('id')->all())
                : [],
            'ledger'        => $this->ledger($open),
            'school'        => [
                'total'     => $schoolTotal,
                'collected' => $schoolCollected,
                'balance'   => max(0, round($schoolTotal - $schoolCollected, 2)),
            ],
            // The Update Fee panel's own Class → Section → Student.
            'paySections'   => $this->showPayPanel ? $this->sectionsOf($this->payStandardId) : collect(),
            'payStudents'   => $this->showPayPanel ? $this->studentsOf($this->payStandardId, $this->paySectionId, true) : collect(),
            'payDue'        => $this->showPayPanel && $this->payStudentId
                ? CustomFeeSupport::forStudent($orgId, (int) $this->payStudentId)
                : null,
            'feeStudent'    => $this->showFeePanel ? $open : null,
            'modes'         => self::MODES,
        ]);
    }
}
