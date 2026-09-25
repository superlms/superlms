<?php

namespace App\Support;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * One academic fee receipt — what goes on it, and its PDF. The panel's print
 * page shows the same numbers; the student app downloads this sheet, laid out
 * exactly as the transport receipt is.
 */
class FeeReceipt
{
    /** The relations a receipt reads; load them with the payment. */
    public const WITH = [
        'organization',
        'studentDetail.user:id,name',
        'studentDetail.standard:id,name',
        'studentDetail.section:id,name',
    ];

    public static function data(FeePayment $payment): array
    {
        $orgId   = (int) $payment->organization_id;
        $student = $payment->studentDetail;

        // What this student's academic fee comes to for the year, net of any
        // concession, and what has come in against it so far.
        $gross = $student
            ? (float) FeeStructure::where('organization_id', $orgId)
                ->where('standard_id', $student->standard_id)
                ->where(fn ($q) => $q->whereNull('section_id')->orWhere('section_id', $student->section_id))
                ->where('is_active', true)
                ->where('fee_type', 'academic')
                ->sum('amount')
                // …and the student's own — Last Year Dues.
                + (float) (FeeStructure::ownTotals($orgId, [$student->id])[$student->id] ?? 0)
            : (float) $payment->amount;

        $concessions = $student
            ? FeeConcession::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)
                ->where('is_penalty', false)
                ->get()
            : collect();

        $net = self::netOf($gross, 'academic', $concessions);

        $paidSoFar = $student
            ? (float) FeePayment::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)
                ->where('fee_type', 'academic')
                ->sum('amount')
            : (float) $payment->amount;

        return [
            'payment'    => $payment,
            'org'        => $payment->organization,
            'student'    => $student,
            'yearTotal'  => $net,
            'concession' => round(max(0, $gross - $net), 2),
            'paidSoFar'  => round($paidSoFar, 2),
            // fee_payments.submitted_by holds the collector's NAME today; rows
            // from older builds hold a user id instead.
            'collectedBy' => self::collectedBy($payment),
            'payMode'     => TransportReceipt::modeLabel($payment->payment_mode),
            'payType'     => self::type($payment),
        ];
    }

    /** The receipt as an A5 PDF, in the bundled Poppins faces. */
    public static function pdf(FeePayment $payment)
    {
        $fontDir = PdfFonts::cacheDir();
        // Without the embedded faces it still renders, in dompdf's own font.
        $fontCss = rescue(fn () => PdfFonts::faceCss(), '', false);

        return Pdf::loadView('pdf.fee-receipt', self::data($payment) + compact('fontCss'))
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
     * staff member) or at the school counter.
     */
    public static function type(FeePayment $payment): string
    {
        return !$payment->submitted_by && strtolower((string) $payment->payment_mode) === 'online'
            ? 'Online'
            : 'Counter';
    }

    /** Whoever took it — a name as stored, an old user id resolved, else the student. */
    public static function collectedBy(FeePayment $payment): string
    {
        $value = $payment->submitted_by;

        if ($value === null || $value === '') {
            return $payment->studentDetail?->user?->name ?? '—';
        }

        if (ctype_digit((string) $value)) {
            return User::find((int) $value)?->name ?: (string) $value;
        }

        return (string) $value;
    }

    /** A concession-adjusted total for one side of the ledger. */
    private static function netOf(float $gross, string $type, $concessions): float
    {
        $taken = 0.0;

        foreach ($concessions as $c) {
            if ($c->fee_type !== 'all' && $c->fee_type !== $type) {
                continue;
            }

            $off = $c->concession_type === 'percent'
                ? $gross * ((float) $c->value) / 100
                : (float) $c->value;

            $taken += min($off, max(0, $gross - $taken));
        }

        return round(max(0, $gross - $taken), 2);
    }
}
