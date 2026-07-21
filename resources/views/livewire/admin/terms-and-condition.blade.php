<div class="min-h-screen bg-gray-50">

    @if (!$hasData)
        <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Terms & Conditions Found</h3>
            <p class="text-gray-400 text-sm max-w-sm">Terms and conditions have not been set up yet. Please contact your
                administrator.</p>
        </div>
    @else
        {{-- ══════════════════════════════════════════════════
             COMPACT HEADER (super-admin about-app style)
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <x-admin.back-to-more />
                    <img src="{{ $platformLogo ?: asset('website-image/Group 11525.png') }}" alt="Platform Logo"
                        class="w-12 h-12 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-2xl font-bold text-gray-900 truncate">Terms &amp; Conditions</h1>
                        @if ($companyName)
                            <p class="text-sm text-gray-500 mt-0.5 truncate">{{ $companyName }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                    @if ($effectiveDate && $effectiveDate !== 'Not set')
                        <span class="inline-block px-4 py-1.5 bg-indigo-50 text-indigo-700 text-sm font-semibold rounded-full border border-indigo-100">
                            Last updated: {{ $effectiveDate }}
                        </span>
                    @endif
                    @if ($companyCin)
                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-600 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-full transition-colors">
                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CIN: {{ $companyCin }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             MAIN CONTENT
        ══════════════════════════════════════════════════ --}}
        <div class="max-w-5xl mx-auto px-6 py-8 space-y-6">

            {{-- ── Introduction ── --}}
            @if (count($sections) > 0 && !empty($sections[0]['desc']))
                <div
                    class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                    hover:border-indigo-200 hover:shadow-md transition-all duration-200">
                    <div
                        class="px-6 py-4 border-b border-gray-100 flex items-center gap-3
                        bg-gradient-to-r from-indigo-50 to-blue-50">
                        <span
                            class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-blue-600
                            text-white text-sm font-bold rounded-xl flex items-center
                            justify-center flex-shrink-0 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <h2 class="text-base font-semibold text-gray-900">Introduction</h2>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $sections[0]['desc'] }}</p>
                    </div>
                </div>
            @endif

            {{-- ── Terms & Conditions Sections ── --}}
            @if (count($sections) > 1)
                @foreach ($sections as $index => $section)
                    @if ($index > 0)
                        @php
                            $head = $section['head'] ?? '';
                            $desc = $section['desc'] ?? '';
                            preg_match('/^(\d+)\./', $head, $matches);
                            $displayHead = !empty($matches) ? $head : "{$head}";
                        @endphp

                        @if (!empty($head) || !empty($desc))
                            <div
                                class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                                hover:border-indigo-200 hover:shadow-md transition-all duration-200">

                                @if (!empty($head))
                                    <div
                                        class="px-6 py-4 border-b border-gray-100 flex items-center gap-3
                                        bg-gradient-to-r from-indigo-50 to-blue-50">
                                        <span
                                            class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-blue-600
                                            text-white text-sm font-bold rounded-xl flex items-center
                                            justify-center flex-shrink-0 shadow-sm">
                                            {{ $index }}
                                        </span>
                                        <h2 class="text-base font-semibold text-gray-900">{{ $displayHead }}</h2>
                                    </div>
                                @endif

                                @if (!empty($desc))
                                    <div class="px-6 py-5">
                                        <p class="text-sm text-gray-600 leading-relaxed">
                                            {!! nl2br(e($desc)) !!}
                                        </p>
                                    </div>
                                @endif

                            </div>
                        @endif
                    @endif
                @endforeach
            @endif


        </div>{{-- end max-w-5xl --}}
    @endif

</div>
