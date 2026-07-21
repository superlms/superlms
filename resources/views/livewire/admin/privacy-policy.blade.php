<div class="min-h-screen bg-gray-50">

    @if (!$policy)
        <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Privacy Policy Published</h3>
            <p class="text-gray-400 text-sm max-w-sm">The privacy policy has not been set up yet. Please contact your
                administrator.</p>
        </div>
    @else
        {{-- ══════════════════════════════════════════════════
             COMPACT HEADER (super-admin about-app style)
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <x-admin.back-to-more />
                    <img src="{{ $aboutApp?->logo ?: asset('website-image/Group 11525.png') }}" alt="Platform Logo"
                        class="w-12 h-12 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Privacy Policy</h1>
                        <p class="text-sm text-gray-500 mt-0.5 truncate">Please read this policy carefully to understand how we handle your information.</p>
                    </div>
                </div>
                @if ($lastUpdated)
                    <span class="flex-shrink-0 inline-block px-4 py-1.5 bg-indigo-50 text-indigo-700 text-sm font-semibold rounded-full border border-indigo-100">
                        Last updated: {{ $lastUpdated }}
                    </span>
                @endif
            </div>
        </div>

        <div class="max-w-5xl mx-auto px-6 py-8 space-y-6">

            @forelse($sections as $i => $section)
                @if (!empty($section['head']) || !empty($section['desc']))
                    <div
                        class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                        hover:border-indigo-200 hover:shadow-md transition-all duration-200">

                        @if (!empty($section['head']))
                            <div
                                class="px-6 py-4 border-b border-gray-100 flex items-center gap-3
                                bg-gradient-to-r from-indigo-50 to-blue-50">
                                <span
                                    class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-blue-600
                                    text-white text-sm font-bold rounded-xl flex items-center
                                    justify-center flex-shrink-0 shadow-sm">
                                    {{ $i + 1 }}
                                </span>
                                <h2 class="text-base font-semibold text-gray-900">{{ $section['head'] }}</h2>
                            </div>
                        @endif

                        @if (!empty($section['desc']))
                            <div class="px-6 py-5">
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    {!! nl2br(e($section['desc'])) !!}
                                </p>
                            </div>
                        @endif

                    </div>
                @endif
            @empty
                <div class="bg-white rounded-2xl border-2 border-dashed border-gray-200 px-8 py-12 text-center">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-400 text-sm">No sections added yet.</p>
                </div>
            @endforelse

        </div>{{-- end max-w-5xl --}}
    @endif

</div>
