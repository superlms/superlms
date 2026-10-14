{{-- A policy document's sections in the Students list's look: one white card
     on the grey page, a quiet S.No / Section header row, then a row per
     section — its number, the heading in bold and the text under it.
     $sections — [['head' => …, 'desc' => …], …] (blank sections skipped)
     $title    — the card's name, e.g. "Privacy Policy" --}}
@php
    $rows = array_values(array_filter($sections, fn ($s) => trim((string) ($s['head'] ?? '')) !== '' || trim((string) ($s['desc'] ?? '')) !== ''));
@endphp
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-100 flex items-baseline justify-between gap-3">
        <h2 class="text-sm font-semibold text-gray-800 truncate">{{ $title }}</h2>
        <span class="text-xs text-gray-400 flex-shrink-0">{{ count($rows) }} {{ count($rows) === 1 ? 'section' : 'sections' }}</span>
    </div>
    @if (count($rows))
        <table class="w-full table-fixed min-w-0!">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">S.No</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($rows as $i => $section)
                    <tr class="hover:bg-gray-50/60 transition-colors align-top">
                        <td class="px-4 py-4 text-sm text-gray-500 font-medium tabular-nums">{{ $i + 1 }}</td>
                        <td class="px-4 py-4">
                            @if (trim((string) ($section['head'] ?? '')) !== '')
                                <p class="text-sm font-semibold text-gray-900 break-words">{{ $section['head'] }}</p>
                            @endif
                            @if (trim((string) ($section['desc'] ?? '')) !== '')
                                <p class="text-sm text-gray-600 leading-relaxed break-words {{ trim((string) ($section['head'] ?? '')) !== '' ? 'mt-1' : '' }}">{!! nl2br(e($section['desc'])) !!}</p>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="px-5 py-10 text-center text-sm text-gray-400">Nothing written yet.</p>
    @endif
</div>
