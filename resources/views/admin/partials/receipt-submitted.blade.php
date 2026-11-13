{{-- A receipt's "Fee Submitted" row: when the fee was entered, or — for a
     school with Fees → "Fee submitted date = fee date" on — the payment's own
     date at that time (App\Support\FeeSubmittedDate). --}}
@php($submittedAt = \App\Support\FeeSubmittedDate::for($payment))
<tr>
    <td class="k">Fee Submitted</td>
    <td class="v" colspan="3">
        {{ $submittedAt?->format('d M Y') ?? '—' }}
        @if ($submittedAt)
            <span style="font-weight:normal; color:#6b7280;">· {{ $submittedAt->format('h:i A') }}</span>
        @endif
    </td>
</tr>
