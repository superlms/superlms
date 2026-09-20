<div>
    @php
        $statusPill = [
            'pending'  => ['To check', 'bg-amber-50 text-amber-700 border-amber-100', 'bg-amber-500'],
            'approved' => ['Approved', 'bg-green-50 text-green-700 border-green-100', 'bg-green-500'],
            'rejected' => ['Rejected', 'bg-red-50 text-red-600 border-red-100', 'bg-red-500'],
        ];
        $feeLabel = fn ($r) => $r->fee_type === 'transport' ? 'Transport' : 'Academic';
        $forLine  = function ($r) {
            if ($months = $r->months()) {
                return strtoupper(implode(', ', $months));
            }
            return $r->meta['installment'] ?? null;
        };
        $orgId = auth()->user()->organization_id;
        $filtered = $search !== '' || $feeType !== '' || $date !== '';
    @endphp

    {{-- ══════════════════════════════════════════════════
         DESKTOP TABLE (hidden on mobile)
    ══════════════════════════════════════════════════ --}}
    <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">UTR</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Paid On</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($requests as $index => $r)
                        @php
                            $st = $r->studentDetail;
                            [$pillText, $pillClass] = $statusPill[$r->status] ?? [ucfirst($r->status), 'bg-gray-50 text-gray-600 border-gray-100'];
                            $for = $forLine($r);
                        @endphp
                        <tr wire:key="qrp-{{ $r->id }}" class="hover:bg-gray-50/70 transition-colors">

                            {{-- S.No --}}
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-500 font-medium">{{ $requests->firstItem() + $index }}</span>
                            </td>

                            {{-- Student --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($st?->image)
                                        <img src="{{ $st->image }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(mb_substr($st?->full_name ?: 'S', 0, 1)) }}</span>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $st?->full_name ?: 'Student removed' }}</p>
                                        <p class="text-xs text-gray-400 truncate">
                                            {{ $st?->standard?->name ?? '—' }}{{ $st?->section ? ' · ' . $st->section->name : '' }}
                                            @if ($st?->admission_no) · {{ $st->admission_no }} @endif
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- Fee --}}
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium border {{ $r->fee_type === 'transport' ? 'bg-purple-50 text-purple-700 border-purple-100' : 'bg-blue-50 text-blue-700 border-blue-100' }}">
                                    {{ $feeLabel($r) }}
                                </span>
                                @if ($for)<p class="text-xs text-gray-400 truncate max-w-[160px] mt-1">{{ $for }}</p>@endif
                            </td>

                            {{-- Amount --}}
                            <td class="px-4 py-3 text-right">
                                <p class="text-sm font-semibold text-gray-900">₹{{ number_format((float) ($r->approved_amount ?? $r->amount), 2) }}</p>
                                @if ($r->approved_amount !== null && (float) $r->approved_amount !== (float) $r->amount)
                                    <p class="text-xs text-gray-400 line-through">₹{{ number_format((float) $r->amount, 2) }}</p>
                                @endif
                            </td>

                            {{-- UTR --}}
                            <td class="px-4 py-3">
                                <span class="text-sm font-mono text-gray-700">{{ $r->utr ?: '—' }}</span>
                            </td>

                            {{-- Paid on --}}
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-700">{{ $r->paid_on?->format('d M Y') ?? '—' }}</p>
                                <p class="text-xs text-gray-400">sent {{ $r->created_at?->diffForHumans() }}</p>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium border {{ $pillClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusPill[$r->status][2] ?? 'bg-gray-400' }}"></span>
                                    {{ $pillText }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    @if ($r->screenshot_path)
                                        <span title="Screenshot attached" class="p-1.5 text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        </span>
                                    @endif
                                    <button wire:click="openReview({{ $r->id }})" title="{{ $r->isPending() ? 'Check' : 'View' }}"
                                        class="p-1.5 rounded-lg transition-colors {{ $r->isPending() ? 'text-emerald-600 hover:bg-emerald-50' : 'text-blue-600 hover:bg-blue-50' }}">
                                        @if ($r->isPending())
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        @endif
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <p class="text-gray-500 text-sm">
                                    @if ($filtered)
                                        Nothing matches this filter
                                    @elseif ($status === 'pending')
                                        Nothing to check — you are all caught up
                                    @else
                                        No {{ $status === '' ? '' : $status . ' ' }}QR payments yet
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400 mt-1">Payments students report from the app after paying on your QR land here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination (Desktop) --}}
        @if ($requests->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-sm text-gray-500">
                    Showing <span class="font-medium text-gray-700">{{ $requests->firstItem() }}</span>
                    to <span class="font-medium text-gray-700">{{ $requests->lastItem() }}</span>
                    of <span class="font-medium text-gray-700">{{ $requests->total() }}</span> payments
                </p>
                <div class="flex items-center gap-1">
                    @if ($requests->onFirstPage())
                        <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">&laquo; Prev</span>
                    @else
                        <button wire:click="previousPage" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">&laquo; Prev</button>
                    @endif

                    @foreach ($requests->getUrlRange(max(1, $requests->currentPage() - 2), min($requests->lastPage(), $requests->currentPage() + 2)) as $page => $url)
                        <button wire:click="gotoPage({{ $page }})"
                            class="px-3 py-1.5 text-sm rounded-lg transition-colors {{ $page == $requests->currentPage() ? 'bg-blue-600 text-white border border-blue-600' : 'text-gray-600 border border-gray-300 hover:bg-gray-50' }}">
                            {{ $page }}
                        </button>
                    @endforeach

                    @if ($requests->hasMorePages())
                        <button wire:click="nextPage" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Next &raquo;</button>
                    @else
                        <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">Next &raquo;</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         MOBILE CARDS (shown only on mobile)
    ══════════════════════════════════════════════════ --}}
    <div class="md:hidden space-y-3">
        @forelse ($requests as $index => $r)
            @php
                $st = $r->studentDetail;
                [$pillText, $pillClass] = $statusPill[$r->status] ?? [ucfirst($r->status), 'bg-gray-50 text-gray-600 border-gray-100'];
                $for = $forLine($r);
            @endphp
            <div wire:key="qrp-m-{{ $r->id }}" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                {{-- Card Header --}}
                <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                    <span class="text-xs font-bold text-gray-400 w-6 text-center">{{ $requests->firstItem() + $index }}</span>
                    @if ($st?->image)
                        <img src="{{ $st->image }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
                    @else
                        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-semibold text-indigo-600">{{ strtoupper(mb_substr($st?->full_name ?: 'S', 0, 1)) }}</span>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $st?->full_name ?: 'Student removed' }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $st?->standard?->name ?? '—' }}{{ $st?->section ? ' · ' . $st->section->name : '' }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium border {{ $pillClass }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusPill[$r->status][2] ?? 'bg-gray-400' }}"></span>
                        {{ $pillText }}
                    </span>
                </div>

                {{-- Card Body --}}
                <div class="px-4 py-3">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">Amount</p>
                            <p class="text-gray-900 font-semibold">₹{{ number_format((float) ($r->approved_amount ?? $r->amount), 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Fee</p>
                            <p class="text-gray-700 font-medium">{{ $feeLabel($r) }}{{ $for ? ' · ' . $for : '' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">UTR</p>
                            <p class="text-gray-700 font-mono text-xs font-medium">{{ $r->utr ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Paid on</p>
                            <p class="text-gray-700 font-medium">{{ $r->paid_on?->format('d M Y') ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Card Action --}}
                <div class="border-t border-gray-100">
                    <button wire:click="openReview({{ $r->id }})"
                        class="w-full flex items-center justify-center gap-1.5 py-2.5 text-xs font-medium transition-colors {{ $r->isPending() ? 'text-emerald-600 hover:bg-emerald-50' : 'text-blue-600 hover:bg-blue-50' }}">
                        @if ($r->isPending())
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Check
                        @else
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            View
                        @endif
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
                <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <p class="text-gray-500 text-sm">
                    @if ($filtered)
                        Nothing matches this filter
                    @elseif ($status === 'pending')
                        Nothing to check — you are all caught up
                    @else
                        No {{ $status === '' ? '' : $status . ' ' }}QR payments yet
                    @endif
                </p>
            </div>
        @endforelse

        {{-- Pagination (Mobile) --}}
        @if ($requests->hasPages())
            <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center justify-between gap-3">
                <button wire:click="previousPage" @disabled($requests->onFirstPage())
                    class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg disabled:opacity-40">&laquo; Prev</button>
                <span class="text-xs text-gray-500">Page {{ $requests->currentPage() }} of {{ $requests->lastPage() }}</span>
                <button wire:click="nextPage" @disabled(!$requests->hasMorePages())
                    class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg disabled:opacity-40">Next &raquo;</button>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         SLIDE-IN PANEL — check one payment
    ══════════════════════════════════════════════════ --}}
    @if ($showPanel && $review)
        @php
            $st = $review->studentDetail;
            [$pillText, $pillClass] = $statusPill[$review->status] ?? [ucfirst($review->status), 'bg-gray-100 text-gray-600'];
            $for = $forLine($review);
            $shot = $review->screenshotUrl();
            $receipt = $review->receiptNumber();
            $receiptUrl = $review->fee_payment_id
                ? route('admin.fee.receipt', ['organization' => $orgId, 'id' => $review->fee_payment_id])
                : ($review->transport_fee_payment_id
                    ? route('admin.transport.receipt', ['organization' => $orgId, 'id' => $review->transport_fee_payment_id])
                    : null);
            $side = $review->fee_type === 'transport' ? ($ledger['transport'] ?? null) : ($ledger['academic'] ?? null);
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" wire:key="qrp-panel-{{ $review->id }}">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-semibold text-gray-900">{{ $review->isPending() ? 'Check payment' : 'QR payment' }}</h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $pillClass }}">{{ $pillText }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">Sent {{ $review->created_at?->format('d M Y, h:i A') }} from the app</p>
                    </div>
                    <button wire:click="closePanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                    {{-- Student --}}
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-full bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center flex-shrink-0">
                            {{ strtoupper(mb_substr($st?->full_name ?: 'S', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ $st?->full_name ?: 'Student removed' }}</p>
                            <p class="text-xs text-gray-500 truncate">
                                {{ $st?->standard?->name ? 'Class ' . $st->standard->name : '—' }}{{ $st?->section ? ' · ' . $st->section->name : '' }}
                                @if ($st?->admission_no) · Adm. {{ $st->admission_no }} @endif
                                @if ($st?->roll_no) · Roll {{ $st->roll_no }} @endif
                            </p>
                        </div>
                        @if ($st)
                            <button type="button" wire:click="$parent.openStudentLedger({{ $st->id }})"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex-shrink-0">Fee ledger →</button>
                        @endif
                    </div>

                    {{-- What they reported --}}
                    <div class="rounded-xl border border-gray-200 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-baseline justify-between gap-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Reported by the student</p>
                            <p class="text-xl font-bold text-gray-900">₹{{ number_format((float) $review->amount, 2) }}</p>
                        </div>
                        <dl class="divide-y divide-gray-100 text-sm">
                            <div class="px-4 py-2.5 flex justify-between gap-4">
                                <dt class="text-gray-500">Fee</dt>
                                <dd class="text-gray-800 text-right">{{ $feeLabel($review) }}{{ $for ? ' · ' . $for : '' }}</dd>
                            </div>
                            <div class="px-4 py-2.5 flex justify-between gap-4" x-data="{ copied: false }">
                                <dt class="text-gray-500">UTR</dt>
                                <dd class="text-right">
                                    @if ($review->utr)
                                        <span class="font-mono text-gray-900">{{ $review->utr }}</span>
                                        <button type="button" class="ml-2 text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                            @click="navigator.clipboard.writeText('{{ $review->utr }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                            x-text="copied ? 'Copied' : 'Copy'">Copy</button>
                                    @else
                                        <span class="text-gray-400">Not given — see the screenshot</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="px-4 py-2.5 flex justify-between gap-4">
                                <dt class="text-gray-500">Paid on</dt>
                                <dd class="text-gray-800">{{ $review->paid_on?->format('l, d M Y') ?? '—' }}</dd>
                            </div>
                            @if ($review->note)
                                <div class="px-4 py-2.5">
                                    <dt class="text-gray-500 mb-0.5">Note</dt>
                                    <dd class="text-gray-800 whitespace-pre-line">{{ $review->note }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Screenshot --}}
                    @if ($shot)
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Screenshot</p>
                                <a href="{{ $shot }}" target="_blank" rel="noopener" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Open full size ↗</a>
                            </div>
                            <a href="{{ $shot }}" target="_blank" rel="noopener" class="block rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
                                <img src="{{ $shot }}" alt="Payment screenshot" class="w-full max-h-[420px] object-contain">
                            </a>
                        </div>
                    @endif

                    {{-- Where the student stands --}}
                    @if ($side)
                        <div class="rounded-xl border border-gray-200 px-4 py-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $feeLabel($review) }} fee this year</p>
                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">₹{{ number_format($side['net'] ?? 0, 0) }}</p>
                                    <p class="text-xs text-gray-400">Payable</p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-600">₹{{ number_format($side['paid'] ?? 0, 0) }}</p>
                                    <p class="text-xs text-gray-400">Paid</p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold {{ ($side['remaining'] ?? 0) > 0 ? 'text-rose-600' : 'text-gray-900' }}">₹{{ number_format($side['remaining'] ?? 0, 0) }}</p>
                                    <p class="text-xs text-gray-400">Remaining</p>
                                </div>
                            </div>
                            @if ($review->isPending() && ($side['remaining'] ?? 0) > 0 && (float) $review->amount > ($side['remaining'] ?? 0) + 0.01)
                                <p class="text-xs text-amber-700 bg-amber-50 rounded-md px-2.5 py-1.5 mt-3">This is more than what is left to pay. Approve it only if that much reached your account.</p>
                            @endif
                        </div>
                    @endif

                    {{-- Decision --}}
                    @if ($review->isPending())
                        @if (!$rejecting)
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 px-4 py-4 space-y-3">
                                <p class="text-sm font-semibold text-gray-900">Approve</p>
                                <p class="text-xs text-gray-500 -mt-2">Check that it reached your account, then approve. The receipt is made now and the student is told.</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Amount received <span class="text-rose-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">₹</span>
                                            <input type="number" step="0.01" min="1" wire:model="approveAmount"
                                                class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        </div>
                                        @error('approveAmount') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Note <span class="text-gray-400 font-normal">(optional)</span></label>
                                        <input type="text" wire:model="approveNote" maxlength="300" placeholder="e.g. Matched in bank statement"
                                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        @error('approveNote') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rounded-xl border border-rose-200 bg-rose-50/40 px-4 py-4 space-y-2">
                                <p class="text-sm font-semibold text-gray-900">Reject</p>
                                <label class="block text-xs font-medium text-gray-600">Reason <span class="text-rose-500">*</span> <span class="text-gray-400 font-normal">— the student sees this</span></label>
                                <textarea wire:model="rejectReason" rows="3" maxlength="300"
                                    placeholder="e.g. No payment with this UTR reached our account."
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
                                @error('rejectReason') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    @else
                        <div class="rounded-xl border {{ $review->status === 'approved' ? 'border-emerald-200 bg-emerald-50/40' : 'border-rose-200 bg-rose-50/40' }} px-4 py-4 space-y-1.5">
                            <p class="text-sm font-semibold text-gray-900">
                                {{ $review->status === 'approved' ? 'Approved' : 'Rejected' }}
                                @if ($review->reviewer) by {{ $review->reviewer->name }} @endif
                                <span class="font-normal text-gray-500">· {{ $review->reviewed_at?->format('d M Y, h:i A') }}</span>
                            </p>
                            @if ($review->status === 'approved')
                                <p class="text-sm text-gray-700">₹{{ number_format((float) $review->approved_amount, 2) }} booked{{ $receipt ? ' — receipt ' . $receipt : '' }}.</p>
                                @if ($receiptUrl)
                                    <a href="{{ $receiptUrl }}" target="_blank" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:text-emerald-900">Open receipt ↗</a>
                                @endif
                            @endif
                            @if ($review->review_note)
                                <p class="text-sm text-gray-600">{{ $review->status === 'approved' ? 'Note' : 'Reason' }}: {{ $review->review_note }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                @if ($review->isPending())
                    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between gap-3 flex-shrink-0">
                        @if (!$rejecting)
                            <button type="button" wire:click="startReject"
                                class="px-4 py-2 text-sm font-semibold rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50">Reject</button>
                            <button type="button" wire:click="approve" wire:loading.attr="disabled" wire:target="approve"
                                class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-semibold rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                Approve &amp; make receipt
                            </button>
                        @else
                            <button type="button" wire:click="cancelReject"
                                class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Back</button>
                            <button type="button" wire:click="reject" wire:loading.attr="disabled" wire:target="reject"
                                class="px-5 py-2 text-sm font-semibold rounded-lg text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 shadow-sm">Reject payment</button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
