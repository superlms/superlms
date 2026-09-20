<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesStudentFeeView;
use App\Models\Admin\Fee\FeePaymentRequest;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use WireUi\Traits\WireUiActions;

/**
 * Fees → QR Payments: what students paid on the school's QR and reported from
 * the app, with the UTR and/or a screenshot. Each one is checked against the
 * school's bank statement here: approving books it as an ordinary payment with
 * its own receipt (fee_payments, or transport_fee_payments for the bus);
 * rejecting closes it with a reason the student sees.
 */
class QrPayments extends Component
{
    use WireUiActions, WithPagination, HandlesStudentFeeView;

    // Filters — the bar lives in the Fee header; it pushes them here.
    public string $status  = FeePaymentRequest::STATUS_PENDING; // '' = all
    public string $feeType = '';
    public string $search  = '';
    public string $date    = '';                                // paid on this day

    // Review panel
    public bool $showPanel = false;
    public ?int $reviewId = null;
    public $approveAmount = '';
    public string $approveNote = '';
    public bool $rejecting = false;
    public string $rejectReason = '';

    protected function orgId(): int
    {
        return (int) Auth::user()->organization_id;
    }

    private function findRequest(?int $id): ?FeePaymentRequest
    {
        return $id
            ? FeePaymentRequest::where('organization_id', $this->orgId())->find($id)
            : null;
    }

    /** The filter bar in the Fee header sends every change here. */
    #[On('qr-filter')]
    public function applyFilters(string $status = '', string $feeType = '', string $search = '', string $date = ''): void
    {
        $this->status  = $status;
        $this->feeType = $feeType;
        $this->search  = $search;
        $this->date    = $date;
        $this->resetPage();
    }

    // ── Review ───────────────────────────────────────────────────────────────

    public function openReview(int $id): void
    {
        $req = $this->findRequest($id);
        if (!$req) {
            return;
        }

        $this->reviewId      = $req->id;
        $this->approveAmount = rtrim(rtrim(number_format((float) $req->amount, 2, '.', ''), '0'), '.');
        $this->approveNote   = '';
        $this->rejecting     = false;
        $this->rejectReason  = '';
        $this->resetValidation();
        $this->showPanel     = true;
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->reviewId  = null;
        $this->rejecting = false;
    }

    public function approve(): void
    {
        $req = $this->findRequest($this->reviewId);
        if (!$req) {
            return;
        }

        $this->validate([
            'approveAmount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'approveNote'   => ['nullable', 'string', 'max:300'],
        ], [
            'approveAmount.required' => 'Enter the amount that reached your account.',
            'approveAmount.min'      => 'The amount must be at least ₹1.',
        ]);

        try {
            $req->approve(Auth::user(), (float) $this->approveAmount, trim($this->approveNote) ?: null);
        } catch (QueryException $e) {
            report($e);
            $this->notification()->error('Could not approve', 'The payment could not be booked. Please try again.');
            return;
        } catch (RuntimeException $e) {
            $this->notification()->error('Could not approve', $e->getMessage());
            return;
        }

        $req->load(['feePayment', 'transportFeePayment', 'studentDetail']);
        $req->notifyStudent();

        $this->notification()->success(
            'Payment approved',
            '₹' . number_format((float) $req->approved_amount, 2) . ' booked for '
                . ($req->studentDetail?->full_name ?: 'the student')
                . ($req->receiptNumber() ? ' — receipt ' . $req->receiptNumber() : '') . '.'
        );
    }

    public function startReject(): void
    {
        $this->rejecting = true;
        $this->resetValidation();
    }

    public function cancelReject(): void
    {
        $this->rejecting    = false;
        $this->rejectReason = '';
        $this->resetValidation();
    }

    public function reject(): void
    {
        $req = $this->findRequest($this->reviewId);
        if (!$req) {
            return;
        }

        $this->rejectReason = trim($this->rejectReason);
        $this->validate([
            'rejectReason' => ['required', 'string', 'min:3', 'max:300'],
        ], [
            'rejectReason.required' => 'Tell the student why — they will see this in the app.',
        ]);

        try {
            $req->reject(Auth::user(), $this->rejectReason);
        } catch (QueryException $e) {
            report($e);
            $this->notification()->error('Could not reject', 'Please try again.');
            return;
        } catch (RuntimeException $e) {
            $this->notification()->error('Could not reject', $e->getMessage());
            return;
        }

        $req->notifyStudent();
        $this->rejecting = false;

        $this->notification()->success('Payment rejected', 'The student has been told why.');
    }

    // ── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $orgId = $this->orgId();
        $base  = FeePaymentRequest::where('organization_id', $orgId);

        $search = trim($this->search);

        $requests = (clone $base)
            ->with([
                'studentDetail:id,full_name,admission_no,roll_no,standard_id,section_id,image',
                'studentDetail.standard:id,name',
                'studentDetail.section:id,name',
                'feePayment:id,receipt_number',
                'transportFeePayment:id,receipt_number',
            ])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->feeType !== '', fn ($q) => $q->where('fee_type', $this->feeType))
            ->when($this->date !== '', fn ($q) => $q->whereDate('paid_on', $this->date))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('utr', 'like', '%' . strtoupper(preg_replace('/\s+/', '', $search)) . '%')
                        ->orWhereHas('studentDetail', fn ($s) => $s
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('admission_no', 'like', "%{$search}%"));
                });
            })
            // Waiting ones oldest first — checked in the order they came in.
            ->when(
                $this->status === FeePaymentRequest::STATUS_PENDING,
                fn ($q) => $q->orderBy('created_at'),
                fn ($q) => $q->orderByDesc('created_at')
            )
            ->paginate(15);

        $review = null;
        $ledger = [];
        if ($this->showPanel && ($review = $this->findRequest($this->reviewId))) {
            $review->load([
                'studentDetail.standard:id,name',
                'studentDetail.section:id,name',
                'feePayment:id,receipt_number',
                'transportFeePayment:id,receipt_number',
                'reviewer:id,name',
            ]);
            $ledger = $this->buildStudentFeeView((int) $review->student_detail_id);
        }

        return view('livewire.admin.qr-payments', [
            'requests' => $requests,
            'review'   => $review,
            'ledger'   => $ledger,
        ]);
    }
}
