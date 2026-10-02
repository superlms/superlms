{{--
    A day's attendance list — the teachers', or a class's students'.

    Two columns side by side with a line down between them, filled across: the
    first person on the left, the second on the right, the third on the left
    again, and so on. Each reads number, photo, name over the login (username
    or admission number; the email where there is none), status, remark. The
    status is plain text; only an absent one is red. On a narrow screen there
    is one column, in the same order.

    Expects: $rows (name, image, status, remark, and username / admission_no /
    email), $who (the column's heading), $empty (what to say with no rows),
    $tint (the initial's colours), $key (wire:key prefix), $statusText.
--}}
@php
    $rows = $rows->values();
    $two  = $rows->count() > 1;
    $cols = 'grid grid-cols-[3rem_minmax(0,1.5fr)_6rem_minmax(0,1fr)] items-center';
@endphp
<div class="overflow-x-auto">
    <div class="min-w-[520px] lg:min-w-0 text-sm">
        <div class="grid {{ $two ? 'lg:grid-cols-2' : '' }} bg-gray-50 text-gray-500 text-xs font-bold uppercase">
            @foreach ($two ? [0, 1] : [0] as $side)
                <div class="{{ $cols }} {{ $side ? 'hidden lg:grid lg:border-l lg:border-gray-200' : '' }}">
                    <span class="px-4 py-3">#</span>
                    <span class="px-4 py-3">{{ $who }}</span>
                    <span class="px-4 py-3">Status</span>
                    <span class="px-4 py-3">Remark</span>
                </div>
            @endforeach
        </div>

        @if ($rows->isEmpty())
            <p class="px-4 py-10 text-center text-gray-400">{{ $empty }}</p>
        @else
            <div class="grid {{ $two ? 'lg:grid-cols-2' : '' }}">
                @foreach ($rows as $i => $row)
                    @php $sub = ($row['username'] ?? '') ?: (($row['admission_no'] ?? '') ?: ($row['email'] ?? '')); @endphp
                    <div wire:key="{{ $key }}-{{ $i }}" class="{{ $cols }} border-t border-gray-100 {{ $two && $i % 2 === 1 ? 'lg:border-l lg:border-l-gray-200' : '' }}">
                        <span class="px-4 py-3 text-gray-400">{{ $i + 1 }}</span>
                        <div class="px-4 py-3 min-w-0">
                            <div class="flex items-center gap-3">
                                @if ($row['image'])
                                    <img src="{{ $row['image'] }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                @else
                                    <div class="w-9 h-9 rounded-full {{ $tint }} flex items-center justify-center font-bold text-xs flex-shrink-0">{{ strtoupper(substr($row['name'], 0, 1)) }}</div>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-800 truncate">{{ $row['name'] }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $sub }}</p>
                                </div>
                            </div>
                        </div>
                        <span class="px-4 py-3 whitespace-nowrap {{ $row['status'] === 'absent' ? 'text-red-600 font-medium' : 'text-gray-700' }}">{{ $statusText($row['status']) }}</span>
                        <span class="px-4 py-3 text-gray-500 break-words">{{ $row['remark'] ?: '—' }}</span>
                    </div>
                @endforeach
                {{-- An odd count leaves the last place on the right empty; it
                     still carries the line between the columns to the foot. --}}
                @if ($two && $rows->count() % 2 === 1)
                    <div class="hidden lg:block border-t border-gray-100 lg:border-l lg:border-l-gray-200"></div>
                @endif
            </div>
        @endif
    </div>
</div>
