<?php

namespace App\Services;

use App\Models\Admin\Fee\FeeAutopayDebit;
use App\Models\Admin\Fee\FeeAutopayMandate;
use App\Models\OrganizationPaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Support\CustomFee;
use App\Support\FeeInstallments;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Fee Autopay: a parent approves a PhonePe UPI AutoPay mandate once, and each
 * academic installment is then taken from their account on its own.
 *
 *   1. Setup — the app opens PhonePe's hosted page (start()); the parent
 *      approves with their UPI PIN (a ₹2 penny drop). The mandate is VARIABLE,
 *      ON_DEMAND, up to the largest installment (₹15,000 at most) and lasts
 *      to the end of the school year.
 *   2. Notify — an installment due in the next two days that is still owed is
 *      told to the parent through PhonePe (the RBI's pre-debit notification)
 *      and in the app.
 *   3. Debit — 24 hours later, if it is still owed, it is taken (redeem);
 *      PhonePe retries a failed debit for 48 hours. Taken, it settles through
 *      a PaymentTransaction into an ordinary online FeePayment — receipt,
 *      push, ledger, accounts desk as for any online payment.
 *
 * The money goes to the school's own PhonePe merchant (never the platform's),
 * and only where the Super Admin turned Autopay on (OrganizationPaymentSetting
 * ::autopayReady()) and PhonePe enabled AutoPay on that merchant. Debits are
 * capped at the mandate's maximum; what is above it stays for the parent to
 * pay. Penalties are never debited.
 *
 * The scheduler runs run() every 30 minutes (fees:autopay); PhonePe's
 * webhooks (webhook()) bring the same news sooner.
 */
class FeeAutopay
{
    /** Installments due today or in this many days are told to the parent. */
    public const NOTIFY_DAYS_AHEAD = 2;

    /** The most one debit may take: PhonePe's hosted-checkout mandate cap. */
    public const MAX_AMOUNT = 15000;

    /** Hours after the notification before the debit may be taken (PhonePe's 24, and a margin). */
    private const DEBIT_AFTER_MINUTES = 24 * 60 + 10;

    private static ?bool $tracked = null;

    // ─── Availability ───────────────────────────────────────────────────────

    public static function tracked(): bool
    {
        if (self::$tracked === null) {
            try {
                self::$tracked = Schema::hasTable('fee_autopay_mandates')
                    && Schema::hasTable('fee_autopay_debits')
                    && Schema::hasColumn('organization_payment_settings', 'autopay_enabled');
            } catch (\Throwable $e) {
                self::$tracked = false;
            }
        }

        return self::$tracked;
    }

    /** Whether a school's parents can set up Autopay. */
    public static function available(?int $orgId): bool
    {
        if (!$orgId || !self::tracked() || CustomFee::enabled($orgId)) {
            return false;
        }

        $setting = OrganizationPaymentSetting::forOrg($orgId);

        return (bool) $setting?->autopayReady();
    }

    /** The school's own PhonePe merchant — Autopay never runs on the platform's. */
    private static function service(int $orgId): PhonePeService
    {
        $service = PhonePeService::fromOrganization($orgId);
        if ($service->scope !== 'organization') {
            throw new RuntimeException("Autopay needs the school's own PhonePe account.");
        }

        return $service;
    }

    public static function liveMandate(int $studentId): ?FeeAutopayMandate
    {
        return FeeAutopayMandate::where('student_detail_id', $studentId)
            ->whereIn('status', FeeAutopayMandate::LIVE)
            ->latest('id')
            ->first();
    }

    /** The largest installment the student has (still owed first), as the mandate's maximum. */
    public static function suggestedMax(int $orgId, int $studentId): float
    {
        $installments = collect(FeeInstallments::forOrg($orgId, [$studentId])[$studentId] ?? []);
        $largest = (float) ($installments->where('outstanding', '>', 0)->max('amount') ?: $installments->max('amount') ?: 0);

        return (float) min(self::MAX_AMOUNT, max(1, ceil($largest)));
    }

    // ─── The app: what it shows, setting up and stopping ────────────────────

    public static function summary(StudentDetail $student): array
    {
        $available = self::available((int) $student->organization_id);
        $mandate   = self::tracked() ? self::liveMandate((int) $student->id) : null;
        $today     = Carbon::today();

        $upcoming = [];
        if ($available || $mandate) {
            $cap = $mandate ? (float) $mandate->max_amount : self::MAX_AMOUNT;
            foreach (FeeInstallments::forOrg((int) $student->organization_id, [(int) $student->id])[$student->id] ?? [] as $i) {
                if ($i['outstanding'] > 0 && $i['due_date'] && $i['due_date']->gte($today)) {
                    $upcoming[] = [
                        'label'        => $i['label'],
                        'due_date'     => $i['due_date']->toDateString(),
                        'outstanding'  => $i['outstanding'],
                        'autopay'      => round(min($i['outstanding'], $cap), 2),
                    ];
                }
            }
        }

        $debits = self::tracked()
            ? FeeAutopayDebit::where('student_detail_id', $student->id)->latest('id')->limit(10)->get()
                ->map(fn (FeeAutopayDebit $d) => [
                    'label'        => $d->label,
                    'amount'       => (float) $d->amount,
                    'status'       => $d->status,
                    'status_label' => $d->statusLabel(),
                    'debit_on'     => $d->valid_after?->toDateString(),
                    'paid_on'      => $d->completed_at?->toDateString(),
                    'reason'       => $d->status === 'failed' ? $d->failure_reason : null,
                ])->all()
            : [];

        return [
            'available'     => $available,
            'max_allowed'   => self::MAX_AMOUNT,
            'suggested_max' => $available ? self::suggestedMax((int) $student->organization_id, (int) $student->id) : null,
            'mandate'       => $mandate ? [
                'id'           => $mandate->id,
                'status'       => $mandate->status,
                'status_label' => $mandate->statusLabel(),
                'max_amount'   => (float) $mandate->max_amount,
                'activated_at' => $mandate->activated_at?->toDateString(),
                'expires_at'   => $mandate->expires_at?->toDateString(),
            ] : null,
            'upcoming' => $upcoming,
            'debits'   => $debits,
        ];
    }

    /**
     * Open a mandate on PhonePe and give the page the parent approves it on.
     *
     * @return array{redirect_url:string, mandate:FeeAutopayMandate}
     */
    public static function start(StudentDetail $student, ?User $user): array
    {
        $orgId = (int) $student->organization_id;
        if (!self::available($orgId)) {
            throw new RuntimeException('Autopay is not available for your school.');
        }

        $live = self::liveMandate((int) $student->id);
        if ($live && $live->status !== 'pending') {
            throw new RuntimeException('Autopay is already on. Stop it first to set it up again.');
        }
        // A setup never approved is replaced.
        if ($live) {
            $live->update(['status' => 'failed', 'status_reason' => 'Replaced by a new setup', 'ended_at' => now()]);
        }

        $max = self::suggestedMax($orgId, (int) $student->id);
        if ($max < 1) {
            throw new RuntimeException('There is no fee to pay by Autopay.');
        }

        // To the end of the school year (April–March), with April for late installments.
        $expires = Carbon::create(now()->month >= 4 ? now()->year + 1 : now()->year, 4, 30, 23, 59, 59);
        $tail = $orgId . '-' . $student->id . '-' . Str::upper(Str::random(8));

        $mandate = FeeAutopayMandate::create([
            'organization_id'          => $orgId,
            'student_detail_id'        => $student->id,
            'user_id'                  => $user?->id,
            'merchant_subscription_id' => 'FAS' . $tail,
            'setup_order_id'           => 'FAO' . $tail,
            'max_amount'               => $max,
            'status'                   => 'pending',
            'expires_at'               => $expires,
        ]);

        try {
            $result = self::service($orgId)->setupSubscription(
                $mandate->setup_order_id,
                $mandate->merchant_subscription_id,
                (int) round($max * 100),
                $expires->getTimestampMs(),
                route('autopay.return', ['merchantOrderId' => $mandate->setup_order_id]),
            );
        } catch (\Throwable $e) {
            $mandate->update(['status' => 'failed', 'status_reason' => Str::limit($e->getMessage(), 250), 'ended_at' => now()]);
            report($e);
            throw new RuntimeException('Could not start Autopay with PhonePe. Please try again.');
        }

        if (empty($result['redirectUrl'])) {
            $mandate->update(['status' => 'failed', 'status_reason' => 'PhonePe gave no page to approve on', 'ended_at' => now()]);
            throw new RuntimeException('Could not start Autopay with PhonePe. Please try again.');
        }

        $mandate->update(['meta' => ['setup_phonepe_order_id' => $result['orderId']]]);

        return ['redirect_url' => $result['redirectUrl'], 'mandate' => $mandate];
    }

    /** Stop a mandate — the parent from the app, or the school. */
    public static function cancel(FeeAutopayMandate $mandate, string $by = 'parent'): FeeAutopayMandate
    {
        if (!$mandate->isLive()) {
            return $mandate;
        }

        try {
            self::service((int) $mandate->organization_id)->cancelSubscription($mandate->merchant_subscription_id);
        } catch (\Throwable $e) {
            // A setup never approved has nothing on PhonePe to cancel.
            if ($mandate->status !== 'pending') {
                report($e);
                throw new RuntimeException('Could not stop Autopay with PhonePe. Please try again.');
            }
        }

        $mandate->update([
            'status'        => $mandate->status === 'pending' ? 'cancelled' : 'cancelling',
            'status_reason' => 'Stopped by the ' . $by,
            'ended_at'      => $mandate->status === 'pending' ? now() : null,
        ]);

        // A debit told but not yet taken is not taken.
        FeeAutopayDebit::where('mandate_id', $mandate->id)->whereIn('status', ['notifying', 'notified'])->get()
            ->each(fn (FeeAutopayDebit $d) => self::fail($d, 'Autopay was stopped'));

        return self::syncMandate($mandate);
    }

    /** Ask PhonePe for the mandate's state. */
    public static function syncMandate(FeeAutopayMandate $mandate): FeeAutopayMandate
    {
        try {
            $data = self::service((int) $mandate->organization_id)->subscriptionStatus($mandate->merchant_subscription_id);
        } catch (\Throwable $e) {
            Log::info('Autopay: mandate status not read', ['mandate' => $mandate->id, 'error' => $e->getMessage()]);

            return $mandate;
        }

        return self::applyMandateState($mandate, $data['state'] ?? null, $data);
    }

    private static function applyMandateState(FeeAutopayMandate $mandate, ?string $state, array $data = []): FeeAutopayMandate
    {
        $status = match (strtoupper((string) $state)) {
            'ACTIVE'                                         => 'active',
            'ACTIVATION_IN_PROGRESS'                         => 'pending',
            'PAUSED', 'PAUSE_IN_PROGRESS', 'UNPAUSE_IN_PROGRESS' => 'paused',
            'CANCEL_IN_PROGRESS'                             => 'cancelling',
            'CANCELLED'                                      => 'cancelled',
            'REVOKED', 'REVOKE_IN_PROGRESS'                  => 'revoked',
            'EXPIRED'                                        => 'expired',
            'FAILED'                                         => 'failed',
            default                                          => null,
        };

        $values = ['synced_at' => now()];
        if (!empty($data['subscriptionId'])) {
            $values['phonepe_subscription_id'] = (string) $data['subscriptionId'];
        }
        // A setup still in progress on our side but stopped from our side stays stopped.
        if ($status && !($mandate->status === 'cancelled' && $status === 'pending')) {
            $values['status'] = $status;
            if ($status === 'active' && !$mandate->activated_at) {
                $values['activated_at'] = now();
            }
            if (in_array($status, ['cancelled', 'revoked', 'expired', 'failed'], true) && !$mandate->ended_at) {
                $values['ended_at'] = now();
            }
        }
        $mandate->update($values);

        return $mandate;
    }

    // ─── The job: every 30 minutes ──────────────────────────────────────────

    /** @return array{synced:int, notified:int, debited:int, settled:int} */
    public static function run(?Carbon $now = null): array
    {
        $done = ['synced' => 0, 'notified' => 0, 'debited' => 0, 'settled' => 0];
        if (!self::tracked()) {
            return $done;
        }
        $now = ($now ?? now())->copy();

        $done['synced']   = self::syncMandates($now);
        $done['notified'] = self::notifyDue($now);
        $done['debited']  = self::debitReady($now);
        $done['settled']  = self::syncDebits($now);

        return $done;
    }

    /** Setups waiting for the parent, and now and then the live ones (a revoke the webhook missed). */
    private static function syncMandates(Carbon $now): int
    {
        $n = 0;

        FeeAutopayMandate::where('status', 'pending')->where('created_at', '<=', $now->copy()->subMinute())->get()
            ->each(function (FeeAutopayMandate $m) use ($now, &$n) {
                self::syncMandate($m);
                $n++;
                // Never approved in a day: it lapsed.
                if ($m->status === 'pending' && $m->created_at->lt($now->copy()->subDay())) {
                    $m->update(['status' => 'failed', 'status_reason' => 'Not approved', 'ended_at' => now()]);
                }
            });

        FeeAutopayMandate::whereIn('status', ['active', 'paused', 'cancelling'])
            ->where(fn ($q) => $q->whereNull('synced_at')->orWhere('synced_at', '<', $now->copy()->subHours(12)))
            ->limit(200)->get()
            ->each(function (FeeAutopayMandate $m) use (&$n) {
                self::syncMandate($m);
                $n++;
            });

        return $n;
    }

    /** Tell the parent of each installment due in the next two days that is still owed. */
    private static function notifyDue(Carbon $now): int
    {
        $today = $now->copy()->startOfDay();
        $last  = $today->copy()->addDays(self::NOTIFY_DAYS_AHEAD);
        $n = 0;

        foreach (FeeAutopayMandate::where('status', 'active')->get()->groupBy('organization_id') as $orgId => $mandates) {
            if (!self::available((int) $orgId)) {
                continue;
            }
            $schedule = FeeInstallments::forOrg((int) $orgId, $mandates->pluck('student_detail_id')->all());

            foreach ($mandates as $mandate) {
                if ($mandate->expires_at && $mandate->expires_at->lt($now)) {
                    continue;
                }
                foreach ($schedule[$mandate->student_detail_id] ?? [] as $inst) {
                    if ($inst['outstanding'] <= 0 || !$inst['due_date'] || $inst['due_date']->lt($today) || $inst['due_date']->gt($last)) {
                        continue;
                    }
                    $amount = round(min($inst['outstanding'], (float) $mandate->max_amount), 2);
                    if ($amount < 1 || FeeAutopayDebit::where('mandate_id', $mandate->id)->where('fee_cycle_id', $inst['cycle_id'])->exists()) {
                        continue;
                    }
                    if (self::notifyOne($mandate, $inst, $amount, $now)) {
                        $n++;
                    }
                }
            }
        }

        return $n;
    }

    private static function notifyOne(FeeAutopayMandate $mandate, array $inst, float $amount, Carbon $now): bool
    {
        $order = 'FAD' . $mandate->organization_id . '-' . $mandate->student_detail_id . '-' . Str::upper(Str::random(10));

        try {
            $debit = FeeAutopayDebit::create([
                'mandate_id'        => $mandate->id,
                'organization_id'   => $mandate->organization_id,
                'student_detail_id' => $mandate->student_detail_id,
                'fee_cycle_id'      => $inst['cycle_id'],
                'due_date'          => $inst['due_date']?->toDateString(),
                'label'             => $inst['label'],
                'amount'            => $amount,
                'merchant_order_id' => $order,
                'status'            => 'notifying',
            ]);
        } catch (QueryException $e) {
            return false; // another run made it
        }

        $txn = PaymentTransaction::create([
            'organization_id'   => $mandate->organization_id,
            'student_detail_id' => $mandate->student_detail_id,
            'user_id'           => $mandate->user_id,
            'fee_type'          => 'academic',
            'gateway'           => 'phonepe',
            'merchant_order_id' => $order,
            'amount'            => $amount,
            'state'             => PaymentTransaction::STATE_PENDING,
            'meta'              => [
                'merchant_scope' => 'organization',
                'autopay'        => true,
                'mandate_id'     => $mandate->id,
                'debit_id'       => $debit->id,
                'fee_cycle_id'   => $inst['cycle_id'],
                'installment'    => $inst['label'],
            ],
        ]);
        $debit->update(['payment_transaction_id' => $txn->id]);

        try {
            $result = self::service((int) $mandate->organization_id)
                ->notifyRedemption($order, $mandate->merchant_subscription_id, (int) round($amount * 100));
        } catch (\Throwable $e) {
            // Tried again on the next runs — three times — before it is left failed.
            $key = 'autopay-notify-fail:' . $mandate->id . ':' . $inst['cycle_id'];
            $tries = (int) Cache::get($key, 0) + 1;
            Cache::put($key, $tries, now()->addDays(3));
            if ($tries < 3) {
                $txn->delete();
                $debit->delete();
            } else {
                self::fail($debit, 'PhonePe could not tell the parent: ' . Str::limit($e->getMessage(), 180));
            }
            report($e);

            return false;
        }

        $debitOn = $now->copy()->addMinutes(self::DEBIT_AFTER_MINUTES);
        $debit->update([
            'status'      => strtoupper((string) ($result['state'] ?? '')) === 'NOTIFIED' ? 'notified' : 'notifying',
            'notified_at' => $now,
            'valid_after' => $debitOn,
        ]);
        $txn->update(['phonepe_order_id' => $result['orderId'] ?: null]);

        self::push($mandate, 'Autopay on ' . $debitOn->format('d M'),
            'Your ' . $inst['label'] . ' of ' . self::rupees($amount) . ' will be paid by Autopay from your bank on '
            . $debitOn->format('d M Y') . '. Keep the money in your account. You don\'t need to pay it yourself.');

        return true;
    }

    /**
     * Take each told debit whose 24 hours are up — if it is still owed — in
     * the hours PhonePe allows debits (1–5 pm and 9:30 pm–10 am).
     */
    private static function debitReady(Carbon $now): int
    {
        if (!self::inDebitWindow($now)) {
            return 0;
        }
        $n = 0;

        foreach (FeeAutopayDebit::whereIn('status', ['notifying', 'notified'])->where('valid_after', '<=', $now)->with('mandate')->get() as $debit) {
            $mandate = $debit->mandate;
            if (!$mandate || $mandate->status !== 'active') {
                self::fail($debit, 'Autopay is not active');
                continue;
            }
            if (!self::available((int) $debit->organization_id)) {
                continue;
            }

            // Paid some other way meanwhile: not taken.
            $inst = collect(FeeInstallments::forOrg((int) $debit->organization_id, [(int) $debit->student_detail_id])[$debit->student_detail_id] ?? [])
                ->firstWhere('cycle_id', (int) $debit->fee_cycle_id);
            if (!$inst || $inst['outstanding'] <= 0) {
                $debit->update(['status' => 'skipped', 'failure_reason' => 'Already paid']);
                $debit->transaction?->settle(PaymentTransaction::STATE_FAILED, ['skipped' => 'already paid']);
                continue;
            }

            $service = self::service((int) $debit->organization_id);
            try {
                if ($debit->status === 'notifying') {
                    $state = strtoupper((string) ($service->subscriptionOrderStatus($debit->merchant_order_id)['state'] ?? ''));
                    if ($state === 'FAILED') {
                        self::fail($debit, 'PhonePe could not tell the parent');
                        continue;
                    }
                    if ($state !== 'NOTIFIED') {
                        continue; // not yet
                    }
                }

                $result = $service->redeem($debit->merchant_order_id);
            } catch (\Throwable $e) {
                Log::warning('Autopay: debit not taken', ['debit' => $debit->id, 'error' => $e->getMessage()]);
                continue;
            }

            $debit->update(['status' => 'debiting', 'executed_at' => $now]);
            $n++;
            self::applyDebitState($debit, $result['state'] ?? 'PENDING', $result['raw'] ?? []);
        }

        return $n;
    }

    /** PhonePe allows debits outside the busy hours: 1:01–4:59 pm and 9:31 pm–9:59 am (IST). */
    public static function inDebitWindow(Carbon $now): bool
    {
        $t = $now->copy()->setTimezone('Asia/Kolkata');
        $m = $t->hour * 60 + $t->minute;

        return ($m >= 13 * 60 + 1 && $m <= 16 * 60 + 59) || $m >= 21 * 60 + 31 || $m <= 9 * 60 + 59;
    }

    /** Debits on their way: PhonePe's answer. */
    private static function syncDebits(Carbon $now): int
    {
        $n = 0;

        foreach (FeeAutopayDebit::where('status', 'debiting')->get() as $debit) {
            try {
                $data = self::service((int) $debit->organization_id)->subscriptionOrderStatus($debit->merchant_order_id);
            } catch (\Throwable $e) {
                continue;
            }
            $state = strtoupper((string) ($data['state'] ?? 'PENDING'));
            // PhonePe retries for 48 hours; after three days with no answer it has lapsed.
            if ($state === 'PENDING' && $debit->executed_at && $debit->executed_at->lt($now->copy()->subDays(3))) {
                $state = 'FAILED';
            }
            if (self::applyDebitState($debit, $state, $data)) {
                $n++;
            }
        }

        // Told, but never taken (the window passed, or no answer): lapsed after three days.
        FeeAutopayDebit::whereIn('status', ['notifying', 'notified'])->where('notified_at', '<', $now->copy()->subDays(3))->get()
            ->each(fn (FeeAutopayDebit $d) => self::fail($d, 'Not taken in time'));

        return $n;
    }

    /** A debit's PhonePe state, applied. True when it ended (paid or failed). */
    private static function applyDebitState(FeeAutopayDebit $debit, string $state, array $raw = []): bool
    {
        $state = strtoupper($state);

        if ($state === 'COMPLETED') {
            $txn = $debit->transaction;
            if ($txn) {
                $txn->settle(PaymentTransaction::STATE_COMPLETED, $raw);
            }
            $debit->update(['status' => 'completed', 'completed_at' => $debit->completed_at ?? now(), 'failure_reason' => null]);

            return true;
        }

        if ($state === 'FAILED') {
            $reason = $raw['errorCode'] ?? ($raw['paymentDetails'][0]['errorCode'] ?? null);
            self::fail($debit, $reason ? 'Debit failed: ' . str_replace('_', ' ', strtolower((string) $reason)) : 'Debit failed');
            $mandate = $debit->mandate;
            if ($mandate) {
                self::push($mandate, 'Autopay could not pay your fee',
                    'Your ' . $debit->label . ' of ' . self::rupees((float) $debit->amount) . ' could not be paid by Autopay. Please pay it in the app.');
            }

            return true;
        }

        return false;
    }

    private static function fail(FeeAutopayDebit $debit, string $reason): void
    {
        if (in_array($debit->status, ['completed', 'failed', 'skipped'], true)) {
            return;
        }
        $debit->update(['status' => 'failed', 'failure_reason' => Str::limit($reason, 250)]);
        $debit->transaction?->settle(PaymentTransaction::STATE_FAILED, ['autopay' => $reason]);
    }

    // ─── PhonePe's webhooks ─────────────────────────────────────────────────

    /**
     * A subscription.* webhook. Returns [http status, body]; the caller has
     * found it is one (PhonePeController::handleWebhook).
     */
    public static function webhook(string $event, array $payload, ?string $authorization): array
    {
        if (!self::tracked()) {
            return [200, ['success' => true]];
        }

        $orderId = (string) ($payload['merchantOrderId'] ?? '');
        $subId   = (string) ($payload['merchantSubscriptionId'] ?? ($payload['paymentFlow']['merchantSubscriptionId'] ?? ''));

        $debit   = $orderId !== '' ? FeeAutopayDebit::where('merchant_order_id', $orderId)->first() : null;
        $mandate = $debit?->mandate
            ?? ($orderId !== '' ? FeeAutopayMandate::where('setup_order_id', $orderId)->first() : null)
            ?? ($subId !== '' ? FeeAutopayMandate::where('merchant_subscription_id', $subId)->first() : null);

        if (!$mandate) {
            Log::info('Autopay webhook: nothing of ours', ['event' => $event, 'order' => $orderId, 'subscription' => $subId]);

            return [200, ['success' => true]];
        }

        if (!PhonePeService::fromOrganization((int) $mandate->organization_id)->verifyWebhook($authorization)) {
            Log::warning('Autopay webhook: invalid signature', ['event' => $event, 'mandate' => $mandate->id]);

            return [401, ['success' => false]];
        }

        $state = (string) ($payload['state'] ?? '');

        if (str_starts_with($event, 'subscription.setup.order.')) {
            $before = $mandate->status;
            self::syncMandate($mandate);
            // PhonePe not answering the status: the webhook's word stands.
            if ($mandate->status === $before && $before === 'pending') {
                self::applyMandateState($mandate, $event === 'subscription.setup.order.completed' ? 'ACTIVE' : 'FAILED', $payload['paymentFlow'] ?? []);
            }
        } elseif (in_array($event, ['subscription.paused', 'subscription.unpaused', 'subscription.revoked', 'subscription.cancelled'], true)) {
            self::applyMandateState($mandate, $state ?: strtoupper(substr($event, strlen('subscription.'))), $payload);
        } elseif ($debit && $event === 'subscription.notification.completed') {
            if ($debit->status === 'notifying') {
                $debit->update(['status' => 'notified']);
            }
        } elseif ($debit && $event === 'subscription.notification.failed') {
            self::fail($debit, 'PhonePe could not tell the parent');
        } elseif ($debit && in_array($event, ['subscription.redemption.order.completed', 'subscription.redemption.transaction.completed'], true)) {
            self::applyDebitState($debit, 'COMPLETED', $payload);
        } elseif ($debit && $event === 'subscription.redemption.order.failed') {
            self::applyDebitState($debit, 'FAILED', $payload);
        }

        return [200, ['success' => true]];
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private static function push(FeeAutopayMandate $mandate, string $title, string $body): void
    {
        try {
            $user = $mandate->user_id ? User::find($mandate->user_id) : User::find($mandate->student?->user_id);
            app(FirebaseNotificationService::class)->notifyUser($user, 'fee_due', ['title' => $title, 'body' => $body, 'screen' => 'Fees']);
        } catch (\Throwable $e) {
            Log::info('Autopay push not sent', ['mandate' => $mandate->id, 'error' => $e->getMessage()]);
        }
    }

    private static function rupees(float $v): string
    {
        return '₹' . number_format($v, fmod($v, 1) ? 2 : 0);
    }
}
