<?php

namespace App\Support;

use App\Models\OrganizationModule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * The "Fee Submitted" date on a school's receipts. Normally it is the moment
 * the fee was entered on the portal (created_at). A school the Super Admin
 * has switched over (Schools → Modules → Fees → "Fee submitted date = fee
 * date") gets the payment's own date there instead, at the time it was
 * entered, so both dates on the receipt read the same.
 *
 * Only what the receipt shows changes: created_at is never touched, so
 * switching it off brings the real entry dates back on every receipt.
 *
 * The switch is a row of module_organization under its own key — not one of
 * config('modules'), so it never hides a page or blocks a route.
 */
class FeeSubmittedDate
{
    public const KEY = 'fee_submitted_as_payment_date';

    /** Whether a school shows the payment date as its receipts' submitted date — read once a request per school. */
    public static function enabled(?int $orgId): bool
    {
        if (!$orgId) {
            return false;
        }

        $key = 'fee_submitted_date.' . $orgId;
        if (!app()->bound($key)) {
            app()->instance($key, rescue(fn () => Schema::hasTable('module_organization')
                && (bool) OrganizationModule::where('organization_id', $orgId)
                    ->where('module_key', self::KEY)
                    ->value('enabled'), false, false));
        }

        return (bool) app($key);
    }

    public static function setEnabled(int $orgId, bool $on): void
    {
        OrganizationModule::updateOrCreate(
            ['organization_id' => $orgId, 'module_key' => self::KEY],
            ['enabled' => $on],
        );

        app()->instance('fee_submitted_date.' . $orgId, $on);
    }

    /**
     * When the receipt says the fee was submitted: the entry time, on the
     * payment's own date for a school that has the switch on.
     */
    public static function for(Model $payment): ?Carbon
    {
        $entered = $payment->created_at ? Carbon::parse($payment->created_at) : null;
        $paidOn  = $payment->payment_date ? Carbon::parse($payment->payment_date) : null;

        if (!$paidOn || !self::enabled((int) $payment->organization_id)) {
            return $entered;
        }

        return $entered
            ? $paidOn->copy()->setTime($entered->hour, $entered->minute, $entered->second)
            : $paidOn->copy()->startOfDay();
    }
}
