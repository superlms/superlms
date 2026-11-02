<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        {!! $fontCss ?? '' !!}

        @page { margin: 26px 34px 46px 34px; }
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #111; line-height: 1.45; }
        .semi { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }
        .bold { font-family: 'Poppins Bold', 'Poppins', sans-serif; }
        .muted { color: #555; }
        .num { text-align: right; white-space: nowrap; }

        table { border-collapse: collapse; }
        .head { width: 100%; }
        .head td { vertical-align: top; }
        .logo { height: 54px; width: auto; }
        .seller-name { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 17px; margin-top: 4px; }
        .doc-title { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 22px; letter-spacing: 2px; text-align: right; }
        .meta { margin-left: auto; margin-top: 6px; }
        .meta td { padding: 1.5px 0 1.5px 14px; font-size: 10.5px; }
        .meta .k { color: #555; text-align: right; }
        .status { display: inline-block; padding: 2px 8px; border: 1px solid #111; border-radius: 10px; font-size: 9px; letter-spacing: 1px; text-transform: uppercase; }

        .rule { height: 1.5px; background: #111; margin: 12px 0; }

        .parties { width: 100%; margin-bottom: 14px; }
        .parties td { width: 50%; vertical-align: top; padding-right: 16px; }
        .label { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #555; margin-bottom: 3px; }
        .party-name { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 12.5px; }

        table.items { width: 100%; }
        table.items th { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; padding: 7px 6px; border-top: 1.5px solid #111; border-bottom: 1px solid #111; }
        table.items td { padding: 7px 6px; border-bottom: 1px solid #dcdcdc; vertical-align: top; }
        table.items tbody tr:nth-child(even) td { background: #f6f6f6; }
        .item-title { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 11px; }
        .item-desc { color: #444; font-size: 9.5px; margin-top: 1px; }

        .bottom { width: 100%; margin-top: 12px; }
        .bottom td { vertical-align: top; }
        table.sums { width: 100%; }
        table.sums td { padding: 4px 6px; }
        table.sums .grand td { border-top: 1.5px solid #111; border-bottom: 1.5px solid #111; font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 13px; padding: 7px 6px; }
        .words { margin-top: 8px; font-size: 10px; }
        .box { margin-top: 12px; }
        .box p { margin: 0; white-space: pre-line; }

        .sign { width: 100%; margin-top: 36px; }
        .sign td { width: 50%; vertical-align: bottom; }
        .sign-line { border-top: 1px solid #111; padding-top: 4px; font-size: 9.5px; width: 200px; }

        .page-break { page-break-before: always; }
        .agreement-title { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 16px; text-align: center; margin: 4px 0 4px; }
        .agreement-sub { text-align: center; color: #555; font-size: 10px; margin-bottom: 16px; }
        .agreement-body { font-size: 11px; line-height: 1.7; white-space: pre-line; }

        .footer { position: fixed; bottom: -28px; left: 0; right: 0; text-align: center; font-size: 8.5px; color: #555; }
    </style>
</head>
<body>
    @php
        $inr = fn ($v) => 'Rs. ' . number_format((float) $v, 2);
        $qty = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $sellerBits = array_values(array_filter([$seller['email'] ?? null, $seller['phone'] ?? null, $seller['website'] ?? null]));
    @endphp

    <div class="footer">{{ $seller['name'] }} · Invoice {{ $invoice->number }} · This is a computer-generated invoice.</div>

    {{-- ─── Seller and invoice details ─────────────────────────────────── --}}
    <table class="head">
        <tr>
            <td>
                @if ($logoSrc)
                    <img src="{{ $logoSrc }}" class="logo" alt="">
                @endif
                <div class="seller-name">{{ $seller['name'] }}</div>
                @if (!empty($seller['address']))
                    <div style="white-space: pre-line;">{{ $seller['address'] }}</div>
                @endif
                @if ($sellerBits)
                    <div>{{ implode('  ·  ', $sellerBits) }}</div>
                @endif
                @if (!empty($seller['gstin']))
                    <div class="semi">GSTIN: {{ $seller['gstin'] }}</div>
                @endif
            </td>
            <td style="text-align: right;">
                <div class="doc-title">{{ !empty($seller['gstin']) ? 'TAX INVOICE' : 'INVOICE' }}</div>
                <table class="meta">
                    <tr><td class="k">Invoice No.</td><td class="semi">{{ $invoice->number }}</td></tr>
                    <tr><td class="k">Date</td><td>{{ $invoice->invoice_date?->format('d M Y') }}</td></tr>
                    @if ($invoice->due_date)
                        <tr><td class="k">Due by</td><td>{{ $invoice->due_date->format('d M Y') }}</td></tr>
                    @endif
                    @if ($invoice->status === 'paid')
                        <tr><td class="k">Paid on</td><td>{{ $invoice->paid_at?->format('d M Y') }}{{ $invoice->payment_mode ? ' · ' . $invoice->payment_mode : '' }}</td></tr>
                    @endif
                    @if (in_array($invoice->status, ['paid', 'cancelled'], true))
                        <tr><td class="k"></td><td><span class="status">{{ \App\Models\Store\StoreInvoice::STATUSES[$invoice->status] }}</span></td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    {{-- ─── Bill to ─────────────────────────────────────────────────────── --}}
    <table class="parties">
        <tr>
            <td>
                <div class="label">Bill to</div>
                <div class="party-name">{{ $billTo['name'] ?? $invoice->schoolName() }}</div>
                @if (!empty($billTo['address']))
                    <div style="white-space: pre-line;">{{ $billTo['address'] }}</div>
                @endif
                @if (!empty($billTo['state']))
                    <div>{{ $billTo['state'] }}</div>
                @endif
                @php $billBits = array_values(array_filter([$billTo['email'] ?? null, $billTo['phone'] ?? null])); @endphp
                @if ($billBits)
                    <div>{{ implode('  ·  ', $billBits) }}</div>
                @endif
                @if (!empty($billTo['code']))
                    <div class="muted">School code: {{ $billTo['code'] }}</div>
                @endif
            </td>
            <td></td>
        </tr>
    </table>

    {{-- ─── Items ───────────────────────────────────────────────────────── --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 26px;">#</th>
                <th>Item</th>
                <th class="num" style="width: 60px;">Qty</th>
                <th class="num" style="width: 86px;">Rate</th>
                <th class="num" style="width: 48px;">GST</th>
                <th class="num" style="width: 96px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <div class="item-title">{{ $item->title }}</div>
                        @if ($item->description)
                            <div class="item-desc">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="num">{{ $qty($item->quantity) }}{{ $item->unit ? ' ' . $item->unit : '' }}</td>
                    <td class="num">{{ $inr($item->unit_price) }}</td>
                    <td class="num">{{ $qty($item->gst_rate) }}%</td>
                    <td class="num">{{ $inr($item->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ─── Totals, amount in words, how to pay ────────────────────────── --}}
    <table class="bottom">
        <tr>
            <td style="width: 56%; padding-right: 18px;">
                <div class="words"><span class="muted">Amount in words:</span> <span class="semi">{{ $words }}</span></div>
                @if ($payment)
                    <div class="box">
                        <div class="label">How to pay</div>
                        <p>{{ $payment }}</p>
                    </div>
                @endif
                @if ($invoice->notes)
                    <div class="box">
                        <div class="label">Notes</div>
                        <p>{{ $invoice->notes }}</p>
                    </div>
                @endif
                @if ($terms)
                    <div class="box">
                        <div class="label">Terms</div>
                        <p>{{ $terms }}</p>
                    </div>
                @endif
            </td>
            <td style="width: 44%;">
                <table class="sums">
                    <tr><td>Subtotal</td><td class="num">{{ $inr($sums['subtotal']) }}</td></tr>
                    @if ($sums['discount'] > 0)
                        <tr><td>Discount</td><td class="num">- {{ $inr($sums['discount']) }}</td></tr>
                        <tr><td>Taxable amount</td><td class="num">{{ $inr($sums['taxable']) }}</td></tr>
                    @endif
                    @foreach ($sums['by_rate'] as $rate => $tax)
                        <tr><td>GST @ {{ $rate }}%</td><td class="num">{{ $inr($tax) }}</td></tr>
                    @endforeach
                    <tr class="grand"><td>Total</td><td class="num">{{ $inr($invoice->total) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="sign">
        <tr>
            <td></td>
            <td style="text-align: right;">
                <div style="display: inline-block; text-align: left;">
                    <div class="semi" style="margin-bottom: 30px;">For {{ $seller['name'] }}</div>
                    <div class="sign-line">Authorised Signatory</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ─── The agreement, on its own page ─────────────────────────────── --}}
    @if ($agreement || $invoice->agreement_file_url)
        <div class="page-break"></div>
        <div class="agreement-title">{{ $invoice->agreement_title ?: 'Agreement' }}</div>
        <div class="agreement-sub">Invoice {{ $invoice->number }} · {{ $invoice->invoice_date?->format('d M Y') }}</div>

        @if ($agreement)
            <div class="agreement-body">{{ $agreement }}</div>
        @endif

        @if ($invoice->agreement_file_url)
            <p style="margin-top: 14px;">
                <span class="muted">Agreement document:</span>
                <span class="semi">{{ $invoice->agreement_file_name ?: 'attached' }}</span>
                — sent with this invoice.
            </p>
        @endif

        <table class="sign" style="margin-top: 56px;">
            <tr>
                <td>
                    <div class="sign-line">For {{ $seller['name'] }} (Seller)</div>
                    <div class="muted" style="font-size: 9px; margin-top: 2px;">Name, signature and date</div>
                </td>
                <td style="text-align: right;">
                    <div style="display: inline-block; text-align: left;">
                        <div class="sign-line">For {{ $billTo['name'] ?? $invoice->schoolName() }} (Buyer)</div>
                        <div class="muted" style="font-size: 9px; margin-top: 2px;">Name, signature, seal and date</div>
                    </div>
                </td>
            </tr>
        </table>
    @endif
</body>
</html>
