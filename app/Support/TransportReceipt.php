<?php

namespace App\Support;

use App\Models\Admin\TransportFeePayment;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * One transport fee receipt — what goes on it, and its PDF. The admin/accounts
 * print page and the student app's download read the same numbers from here.
 */
class TransportReceipt
{
    /** The relations a receipt reads; load them with the payment. */
    public const WITH = [
        'organization',
        'transportation:id,route_name,pickup_time,monthly_fee',
        'studentDetail.user:id,name',
        'studentDetail.standard:id,name',
        'studentDetail.section:id,name',
        'submittedBy:id,name',
    ];

    public static function data(TransportFeePayment $payment): array
    {
        $orgId   = (int) $payment->organization_id;
        $student = $payment->studentDetail;

        // The route on the payment is what was actually paid for; the student's
        // current route is what the year is billed on. They are the same route
        // in every ordinary case, and the payment's wins when they are not.
        [$currentRoute, $yearTotal, $billedMonths] = $student
            ? TransportBilling::forStudent($orgId, $student->id)
            : [null, 0.0, 0];

        $route = $payment->transportation ?: $currentRoute;

        if ($route && $currentRoute && $route->id !== $currentRoute->id) {
            $billedMonths = TransportBilling::billedMonths($orgId, $route->id, $student->id);
            $yearTotal    = round((float) $route->monthly_fee * $billedMonths, 2);
        }

        $paidSoFar = $student
            ? (float) TransportFeePayment::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)
                ->when($payment->academic_year, fn ($q) => $q->where('academic_year', $payment->academic_year))
                ->sum('amount')
            : (float) $payment->amount;

        return [
            'payment'      => $payment,
            'org'          => $payment->organization,
            'student'      => $student,
            'route'        => $route,
            'yearTotal'    => $yearTotal,
            'billedMonths' => $billedMonths,
            'paidSoFar'    => $paidSoFar,
            // transport_fee_payments.submitted_by IS a user id here (unlike the
            // academic receipt, where the column holds the collector's name).
            'collectedBy'  => $payment->submittedBy->name ?? ($payment->submitted_by ?: '—'),
            'submittedBy'  => self::submittedBy($payment),
            'payType'      => self::type($payment),
            'payMode'      => self::modeLabel($payment->payment_mode),
        ];
    }

    /** The receipt as an A5 PDF, in the bundled Poppins faces. */
    public static function pdf(TransportFeePayment $payment)
    {
        $fontDir = PdfFonts::cacheDir();
        // Without the embedded faces it still renders, in dompdf's own font.
        $fontCss = rescue(fn () => PdfFonts::faceCss(), '', false);

        return Pdf::loadView('pdf.transport-receipt', self::data($payment) + compact('fontCss'))
            ->setPaper('a5', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('fontDir', $fontDir)
            ->setOption('fontCache', $fontDir)
            ->setOption('defaultFont', 'DejaVu Sans');
    }

    /**
     * Paid in the app (the online checkout records an online payment with no
     * staff member) or at the school counter (anything recorded by staff, or
     * taken in cash, cheque and the like).
     */
    public static function type(TransportFeePayment $payment): string
    {
        return !$payment->submitted_by && strtolower((string) $payment->payment_mode) === 'online'
            ? 'Online'
            : 'Counter';
    }

    /** The staff member who recorded it, else the student who paid in the app. */
    public static function submittedBy(TransportFeePayment $payment): string
    {
        if ($payment->submitted_by) {
            return $payment->submittedBy?->name ?? '—';
        }

        return $payment->studentDetail?->user?->name ?? '—';
    }

    /** cash / upi / cheque / online → Cash / UPI / Cheque / Online */
    public static function modeLabel(?string $mode): string
    {
        $mode = trim((string) $mode);
        if ($mode === '') return '—';

        return strtolower($mode) === 'upi' ? 'UPI' : ucwords(str_replace('_', ' ', strtolower($mode)));
    }
}
