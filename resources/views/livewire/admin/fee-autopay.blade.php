<div class="space-y-4">
    @php
        $inr = fn ($v) => '₹' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
        $mandateCls = [
            'pending'    => 'bg-amber-50 text-amber-700',
            'active'     => 'bg-emerald-50 text-emerald-700',
            'paused'     => 'bg-gray-100 text-gray-700',
            'cancelling' => 'bg-gray-100 text-gray-600',
            'cancelled'  => 'bg-gray-100 text-gray-500',
            'revoked'    => 'bg-red-50 text-red-600',
            'expired'    => 'bg-gray-100 text-gray-500',
            'failed'     => 'bg-red-50 text-red-600',
        ];
        $debitCls = [
            'notifying' => 'bg-blue-50 text-blue-700',
            'notified'  => 'bg-blue-50 text-blue-700',
            'debiting'  => 'bg-amber-50 text-amber-700',
            'completed' => 'bg-emerald-50 text-emerald-700',
            'failed'    => 'bg-red-50 text-red-600',
            'skipped'   => 'bg-gray-100 text-gray-600',
        ];
        $sel = 'text-xs bg-white border border-gray-200 rounded-md pl-2.5 pr-8 py-1.5 text-gray-700';
    @endphp

    @if (!$ready)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">Autopay is not set up yet — its tables come with the next migration.</div>
    @else
        @unless ($available)
            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3">
                Autopay is off for your school, so parents can't set it up in the app. Mandates already made are listed below. Ask SuperLMS to turn it on.
            </div>
        @endunless

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ([
                ['Autopay on', number_format($stats['active']), $stats['pending'] ? $stats['pending'] . ' waiting for the parent to approve' : 'students whose fee is paid on its own', 'text-gray-900'],
                ['Paid by Autopay', $inr($stats['paid']), 'this month', 'text-emerald-600'],
                ['On its way', $inr($stats['upcoming']), 'parents told, to be taken', 'text-blue-600'],
                ['Failed', number_format($stats['failed']), 'this month — the parent pays it in the app', $stats['failed'] ? 'text-red-600' : 'text-gray-900'],
            ] as [$label, $value, $sub, $cls])
                <div class="bg-white border border-gray-200 rounded-xl px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $label }}</p>
                    <p class="text-lg font-bold mt-0.5 {{ $cls }}">{{ $value }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $sub }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="flex flex-wrap items-center gap-2 px-4 py-3 border-b border-gray-200">
                <div class="inline-flex rounded-lg border border-gray-200 p-0.5 bg-gray-50">
                    @foreach (['mandates' => 'Students on Autopay', 'debits' => 'Debits'] as $k => $l)
                        <button type="button" wire:click="$set('list', '{{ $k }}')"
                            class="px-3 py-1.5 text-xs font-medium rounded-md {{ $list === $k ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">{{ $l }}</button>
                    @endforeach
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search student or admission no…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 max-w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <select wire:model.live="status" aria-label="Status" class="{{ $sel }}">
                    @if ($list === 'debits')
                        <option value="">Every debit</option>
                        @foreach (\App\Models\Admin\Fee\FeeAutopayDebit::STATUSES as $k => $l)
                            <option value="{{ $k }}">{{ $l }}</option>
                        @endforeach
                    @else
                        <option value="">On, or waiting</option>
                        @foreach (\App\Models\Admin\Fee\FeeAutopayMandate::STATUSES as $k => $l)
                            <option value="{{ $k }}">{{ $l }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            @if ($rows->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-semibold text-gray-900">{{ $list === 'debits' ? 'No debits yet' : 'No student is on Autopay yet' }}</p>
                    <p class="mt-1 text-sm text-gray-500">Parents set up Autopay from Fees in the app. Each installment is then told to them a day ahead and taken from their bank on its own.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500">
                            @if ($list === 'debits')
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold">Student</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Installment</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">Amount</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Taken on</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Status</th>
                                </tr>
                            @else
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold">Student</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Status</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">Up to</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Since</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">Paid</th>
                                    <th class="px-4 py-2.5"></th>
                                </tr>
                            @endif
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($rows as $row)
                                @php
                                    $s = $row->student;
                                    $class = trim(($s?->standard?->name ?? '') . ($s?->section ? ' - ' . $s->section->name : ''));
                                @endphp
                                <tr wire:key="ap-{{ $list }}-{{ $row->id }}" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ $s?->full_name ?? 'Student' }}</p>
                                        <p class="text-xs text-gray-500">{{ $class ?: '—' }}{{ $s?->admission_no ? ' · ' . $s->admission_no : '' }}</p>
                                    </td>
                                    @if ($list === 'debits')
                                        <td class="px-4 py-3 text-gray-700">
                                            {{ $row->label }}
                                            @if ($row->due_date)<p class="text-xs text-gray-500">due {{ $row->due_date->format('d M Y') }}</p>@endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">{{ $inr($row->amount) }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{{ ($row->completed_at ?? $row->valid_after)?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $debitCls[$row->status] ?? '' }}">{{ $row->statusLabel() }}</span>
                                            @if ($row->status === 'failed' && $row->failure_reason)
                                                <p class="text-xs text-gray-500 mt-0.5">{{ $row->failure_reason }}</p>
                                            @endif
                                        </td>
                                    @else
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $mandateCls[$row->status] ?? '' }}">{{ $row->statusLabel() }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-800 whitespace-nowrap">{{ $inr($row->max_amount) }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{{ ($row->activated_at ?? $row->created_at)?->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-right text-xs text-gray-600">{{ $row->paid_count }} installment{{ $row->paid_count === 1 ? '' : 's' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            @if ($row->isLive())
                                                <button wire:click="confirmStop({{ $row->id }})" class="px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded-md">Stop</button>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-100">{{ $rows->links() }}</div>
            @endif
        </div>
    @endif

    @if ($stopId)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4 lms-cover" wire:click.self="$set('stopId', null)">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <h3 class="text-sm font-semibold text-gray-900">Stop Autopay for this student?</h3>
                <p class="text-xs text-gray-500 mt-1">No more installments are taken from the parent's bank. A debit already told to the parent is not taken. The parent can set it up again in the app.</p>
                <div class="mt-4 flex items-center gap-2">
                    <button wire:click="$set('stopId', null)" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg">Keep</button>
                    <button wire:click="stop" class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg">Stop Autopay</button>
                </div>
            </div>
        </div>
    @endif
</div>
