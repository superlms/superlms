<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Store\StoreInvoice;
use App\Support\NumberToWords;
use App\Support\PdfFonts;
use App\Support\Platform;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Super Admin → Store → Invoices: the numbers, the sums, the PDF (the invoice,
 * then its agreement) and the email that takes them to the school.
 *
 * Who SuperLMS is on an invoice — name, address, GSTIN, contacts, where to pay,
 * the default terms and agreement — is kept in platform_settings (Platform),
 * set from the Invoices tab's Settings.
 */
class StoreInvoices
{
    /** Setting key => default. */
    public const SETTINGS = [
        'store_seller_name'     => 'SuperLMS',
        'store_seller_address'  => '',
        'store_seller_gstin'    => '',
        'store_seller_phone'    => '',
        'store_seller_email'    => '',
        'store_payment_details' => '',
        'store_invoice_terms'   => 'Goods once sold will be replaced only against a manufacturing defect reported within 7 days of delivery.',
        'store_invoice_prefix'  => 'INV',
        'store_agreement_title' => 'Purchase Agreement',
        'store_agreement_body'  => self::DEFAULT_AGREEMENT,
    ];

    /** Placeholders an agreement may use, filled in for each invoice. */
    public const PLACEHOLDERS = ['{school}', '{seller}', '{invoice_number}', '{invoice_date}', '{due_date}', '{total}'];

    private const DEFAULT_AGREEMENT = <<<'TXT'
This agreement is made between {seller} ("the Seller") and {school} ("the Buyer") for the goods and services listed in invoice {invoice_number} dated {invoice_date}, for a total of {total}.

1. Delivery — The Seller will deliver the goods listed in the invoice to the Buyer's address in working condition.
2. Payment — The Buyer will pay the invoice total by {due_date}, to the account named on the invoice.
3. Warranty — Products carry their manufacturer's warranty where one applies. Physical damage is not covered.
4. Replacement — A manufacturing defect must be reported within 7 days of delivery; the item will then be repaired or replaced.
5. Support — The Seller will help the Buyer set up and use what was bought.
6. Disputes — Any disagreement will first be settled by talking it through in good faith.

Both parties have read and agree to these terms.
TXT;

    public const PAYMENT_MODES = ['UPI', 'Bank Transfer', 'Cheque', 'Cash', 'Card', 'Other'];

    // ─── Settings ───────────────────────────────────────────────────────────

    public static function setting(string $key): string
    {
        $value = Platform::get($key);

        return $value ?? (string) (self::SETTINGS[$key] ?? '');
    }

    public static function saveSetting(string $key, ?string $value): void
    {
        if (array_key_exists($key, self::SETTINGS)) {
            Platform::set($key, trim((string) $value));
        }
    }

    /**
     * SuperLMS as the invoice names it. What the Invoices settings leave
     * blank comes from Profile → Platform Details.
     */
    public static function seller(): array
    {
        $company = fn (string $key) => (string) Platform::get($key);
        $mobile  = $company('company_mobile');
        $mobile  = strlen($mobile) === 10 ? \App\Support\OtpNumbers::format($mobile) : $mobile;

        return [
            'name'    => Platform::get('store_seller_name') ?? (Platform::get('company_name') ?? 'SuperLMS'),
            'address' => self::setting('store_seller_address') ?: $company('company_address'),
            'gstin'   => self::setting('store_seller_gstin') ?: $company('company_gstin'),
            'phone'   => self::setting('store_seller_phone') ?: $mobile,
            'email'   => self::setting('store_seller_email') ?: ($company('company_email') ?: (string) config('mail.from.address')),
            'website' => (string) parse_url(Platform::get('company_website') ?? (string) config('app.url'), PHP_URL_HOST),
        ];
    }

    // ─── Numbers ────────────────────────────────────────────────────────────

    /** The Indian financial year a day falls in, e.g. "2026-27" (April to March). */
    public static function financialYear(Carbon $date): string
    {
        $start = $date->month >= 4 ? $date->year : $date->year - 1;

        return $start . '-' . substr((string) ($start + 1), -2);
    }

    /** The next invoice number for a day: INV/2026-27/0001, counting afresh each financial year. */
    public static function nextNumber(Carbon $date): string
    {
        $prefix = preg_replace('/[^A-Za-z0-9-]/', '', self::setting('store_invoice_prefix')) ?: 'INV';
        $stem   = $prefix . '/' . self::financialYear($date) . '/';

        $last = StoreInvoice::where('number', 'like', $stem . '%')->pluck('number')
            ->map(fn ($n) => (int) substr((string) $n, strlen($stem)))
            ->max() ?? 0;

        return $stem . str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    // ─── Sums ───────────────────────────────────────────────────────────────

    /**
     * Lines and a discount → what each line and the invoice come to. The
     * discount comes off before GST: each line's tax is on its share of the
     * amount left.
     *
     * @param  array<int,array{quantity:mixed,unit_price:mixed,gst_rate:mixed}>  $lines
     * @return array{lines: array, subtotal: float, discount: float, taxable: float, tax_total: float, total: float, by_rate: array<string,float>}
     */
    public static function totals(array $lines, $discount = 0): array
    {
        $out      = [];
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $qty    = max(0, (float) ($line['quantity'] ?? 0));
            $price  = max(0, (float) ($line['unit_price'] ?? 0));
            $amount = round($qty * $price, 2);
            $subtotal += $amount;
            $out[] = $line + ['amount' => $amount];
        }

        $subtotal = round($subtotal, 2);
        $discount = round(min(max(0, (float) $discount), $subtotal), 2);
        $factor   = $subtotal > 0 ? ($subtotal - $discount) / $subtotal : 0;

        $taxTotal = 0.0;
        $byRate   = [];
        foreach ($out as $i => $line) {
            $rate = max(0, (float) ($line['gst_rate'] ?? 0));
            $tax  = round($line['amount'] * $factor * $rate / 100, 2);
            $out[$i]['tax']   = $tax;
            $out[$i]['total'] = round($line['amount'] * $factor + $tax, 2);
            $taxTotal += $tax;
            if ($tax > 0) {
                $key = rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');
                $byRate[$key] = round(($byRate[$key] ?? 0) + $tax, 2);
            }
        }

        $taxable = round($subtotal - $discount, 2);
        $taxTotal = round($taxTotal, 2);

        return [
            'lines'     => $out,
            'subtotal'  => $subtotal,
            'discount'  => $discount,
            'taxable'   => $taxable,
            'tax_total' => $taxTotal,
            'total'     => round($taxable + $taxTotal, 2),
            'by_rate'   => $byRate,
        ];
    }

    /** The school as the invoice bills it, kept with the invoice. */
    public static function billTo(Organization $org): array
    {
        return array_filter([
            'name'    => $org->name,
            'address' => $org->address,
            'state'   => $org->state,
            'email'   => $org->email,
            'phone'   => $org->mobile_number,
            'code'    => $org->school_code,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** The agreement's text with this invoice's details filled in ("Rs. " for the PDF, whose font has no ₹). */
    public static function agreementText(StoreInvoice $invoice, string $rupee = '₹'): string
    {
        $inr = fn ($v) => $rupee . number_format((float) $v, 2);

        return strtr((string) $invoice->agreement_body, [
            '{school}'         => $invoice->schoolName(),
            '{seller}'         => self::seller()['name'],
            '{invoice_number}' => $invoice->number,
            '{invoice_date}'   => $invoice->invoice_date?->format('d M Y') ?? '',
            '{due_date}'       => $invoice->due_date?->format('d M Y') ?? 'the agreed date',
            '{total}'          => $inr($invoice->total),
        ]);
    }

    // ─── PDF ────────────────────────────────────────────────────────────────

    /** @return array{0: string, 1: string} the PDF's bytes and its file name */
    public static function pdf(StoreInvoice $invoice): array
    {
        $invoice->loadMissing('items', 'organization');

        $logo = public_path('admin-image/SuperLMS logo.png');
        $data = [
            'invoice'   => $invoice,
            'seller'    => self::seller(),
            'logoSrc'   => is_file($logo) ? $logo : null,
            'billTo'    => $invoice->bill_to ?: ($invoice->organization ? self::billTo($invoice->organization) : []),
            'sums'      => self::totals($invoice->items->map(fn ($i) => [
                'quantity' => $i->quantity, 'unit_price' => $i->unit_price, 'gst_rate' => $i->gst_rate,
            ])->all(), $invoice->discount),
            'words'     => NumberToWords::rupees((float) $invoice->total),
            'payment'   => self::setting('store_payment_details'),
            'terms'     => self::setting('store_invoice_terms'),
            'agreement' => trim((string) $invoice->agreement_body) !== '' ? self::agreementText($invoice, 'Rs. ') : null,
        ];

        $cache = PdfFonts::cacheDir();
        $load  = fn (string $fontCss) => Pdf::loadView('pdf.store-invoice', $data + compact('fontCss'))
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 130)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('fontDir', $cache)
            ->setOption('fontCache', $cache)
            ->setOption('defaultFont', 'DejaVu Sans');

        try {
            $pdf = $load(PdfFonts::faceCss());
        } catch (\Throwable $e) {
            $pdf = $load('');
        }

        return [$pdf->output(), self::fileName($invoice)];
    }

    public static function fileName(StoreInvoice $invoice): string
    {
        return 'invoice_' . preg_replace('/[^A-Za-z0-9-]+/', '_', (string) $invoice->number) . '.pdf';
    }

    // ─── Email ──────────────────────────────────────────────────────────────

    /**
     * Email the invoice (PDF attached, with its agreement) to the school, and
     * mark it sent. The uploaded agreement document goes along too when it
     * can be read, else as a link.
     *
     * @param  string[]  $emails
     * @throws \RuntimeException when the email could not go
     */
    public static function send(StoreInvoice $invoice, array $emails, string $message = ''): void
    {
        $emails = array_values(array_unique(array_filter(
            array_map(fn ($e) => strtolower(trim((string) $e)), $emails),
            fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL),
        )));

        if (empty($emails)) {
            throw new \RuntimeException('Give at least one email address.');
        }

        [$bytes, $fileName] = self::pdf($invoice);

        $attachments = [[
            'content'   => base64_encode($bytes),
            'mime_type' => 'application/pdf',
            'name'      => $fileName,
        ]];

        $agreementFile = self::agreementAttachment($invoice);
        if ($agreementFile) {
            $attachments[] = $agreementFile;
        }

        $seller  = self::seller();
        $subject = 'Invoice ' . $invoice->number . ' from ' . $seller['name']
            . ($invoice->hasAgreement() ? ' (with agreement)' : '');

        ZeptoMailService::sendRaw(
            $subject,
            self::emailHtml($invoice, $message, $agreementFile === null),
            array_map(fn ($e) => ['email' => $e, 'name' => $invoice->schoolName()], $emails),
            null,
            null,
            $seller['name'],
            $attachments,
        );

        $invoice->forceFill([
            'status'  => $invoice->status === 'draft' ? 'sent' : $invoice->status,
            'sent_at' => now(),
            'sent_to' => mb_substr(implode(', ', $emails), 0, 1000),
        ])->save();
    }

    /** A Store file's key on the S3 disk from its public URL ('' when it is not one of ours). */
    public static function storagePath(?string $url): string
    {
        $url = (string) $url;
        if ($url === '') {
            return '';
        }

        try {
            $base = rtrim(Storage::disk('s3')->url(''), '/');
        } catch (\Throwable $e) {
            $base = '';
        }

        $path = $base !== '' && str_starts_with($url, $base . '/')
            ? substr($url, strlen($base) + 1)
            : ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        $path = rawurldecode(strtok($path, '?') ?: '');

        return str_starts_with($path, 'store/') ? $path : '';
    }

    /** The uploaded agreement document as an email attachment (up to 8 MB), or null. */
    private static function agreementAttachment(StoreInvoice $invoice): ?array
    {
        $url = (string) $invoice->agreement_file_url;
        if ($url === '') {
            return null;
        }

        try {
            $path = self::storagePath($url);
            $disk = Storage::disk('s3');
            if ($path === '' || !$disk->exists($path) || $disk->size($path) > 8 * 1024 * 1024) {
                return null;
            }

            $name = $invoice->agreement_file_name ?: basename($path);

            return [
                'content'   => base64_encode((string) $disk->get($path)),
                'mime_type' => $disk->mimeType($path) ?: 'application/octet-stream',
                'name'      => $name,
            ];
        } catch (\Throwable $e) {
            Log::warning('Store invoice: agreement file not attached', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public static function emailHtml(StoreInvoice $invoice, string $message = '', bool $linkAgreementFile = false): string
    {
        $e      = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $inr    = fn ($v) => '₹' . number_format((float) $v, 2);
        $seller = self::seller();
        $school = $invoice->schoolName();

        $intro = trim($message) !== ''
            ? nl2br($e(trim($message)))
            : 'Please find attached invoice <strong>' . $e($invoice->number) . '</strong> for your recent purchase from ' . $e($seller['name']) . '.'
                . ($invoice->hasAgreement() ? ' The agreement for this purchase is attached as well.' : '');

        $rows = [
            ['Invoice', $e($invoice->number)],
            ['Date', $e($invoice->invoice_date?->format('d M Y'))],
        ];
        if ($invoice->due_date) {
            $rows[] = ['Due by', $e($invoice->due_date->format('d M Y'))];
        }
        $rows[] = ['Amount', '<strong>' . $e($inr($invoice->total)) . '</strong>'];

        $table = '';
        foreach ($rows as [$k, $v]) {
            $table .= '<tr><td style="padding:6px 0;color:#6b7280;font-size:13px;width:110px">' . $k . '</td>'
                . '<td style="padding:6px 0;color:#111827;font-size:14px">' . $v . '</td></tr>';
        }

        $payment = self::setting('store_payment_details');
        $paymentHtml = $payment !== ''
            ? '<p style="margin:18px 0 4px;font-size:13px;color:#6b7280">How to pay</p><p style="margin:0;font-size:14px;color:#111827;line-height:1.6">' . nl2br($e($payment)) . '</p>'
            : '';

        $agreementLink = $linkAgreementFile && $invoice->agreement_file_url
            ? '<p style="margin:14px 0 0;font-size:14px"><a href="' . $e($invoice->agreement_file_url) . '" style="color:#2563eb">Open the agreement document' . ($invoice->agreement_file_name ? ' (' . $e($invoice->agreement_file_name) . ')' : '') . '</a></p>'
            : '';

        $contact = implode(' · ', array_filter([$seller['email'], $seller['phone'], $seller['website']]));

        return '<!DOCTYPE html><html><body style="margin:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif">'
            . '<div style="max-width:600px;margin:0 auto;padding:24px 16px">'
            . '<div style="background:#ffffff;border-radius:12px;padding:28px">'
            . '<p style="margin:0 0 4px;font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#6b7280">' . $e($seller['name']) . '</p>'
            . '<h1 style="margin:0 0 18px;font-size:20px;color:#111827">Invoice ' . $e($invoice->number) . '</h1>'
            . '<p style="margin:0 0 14px;font-size:14px;color:#111827">Dear ' . $e($school) . ',</p>'
            . '<p style="margin:0 0 18px;font-size:14px;color:#111827;line-height:1.6">' . $intro . '</p>'
            . '<table style="border-collapse:collapse;width:100%;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb">' . $table . '</table>'
            . '<p style="margin:20px 0 0"><a href="' . $e($invoice->publicPdfUrl()) . '" style="display:inline-block;background:#111827;color:#ffffff;text-decoration:none;font-size:14px;padding:10px 18px;border-radius:8px">View / download invoice</a></p>'
            . $agreementLink
            . $paymentHtml
            . '<p style="margin:22px 0 0;font-size:14px;color:#111827">Thank you,<br>' . $e($seller['name']) . '</p>'
            . '</div>'
            . ($contact !== '' ? '<p style="text-align:center;margin:14px 0 0;font-size:12px;color:#6b7280">' . $e($contact) . '</p>' : '')
            . '</div></body></html>';
    }
}
