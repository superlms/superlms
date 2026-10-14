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
             HEADER — as the Super Admin's view: the name with the company
             and CIN beside it, and the last update as plain text
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                <div class="flex items-center gap-3 min-w-0">
                    <x-admin.back-to-more />
                    <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                        <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">Terms &amp; Conditions</h1>
                        @if ($companyName || $companyCin)
                            <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">
                                {{ $companyName }}{{ $companyName && $companyCin ? ' · ' : '' }}{{ $companyCin ? 'CIN: ' . $companyCin : '' }}
                            </p>
                        @endif
                    </div>
                </div>
                @if ($termsData?->last_updated)
                    <span class="flex-shrink-0 text-xs text-gray-400">
                        Last updated {{ $termsData->last_updated->format('d M Y') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- The sections in the Super Admin view's look: one white card on the
             grey page — S.No and the section, its heading in bold and the
             text under it (super-admin/partials/sections-view). --}}
        <div class="p-4 sm:p-6">
            @include('livewire.super-admin.partials.sections-view', ['sections' => $sections, 'title' => 'Terms & Conditions'])
        </div>
    @endif

</div>
