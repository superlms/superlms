<div class="min-h-screen bg-gray-50">
    <x-admin.back-to-more />

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, with inline status)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Rate Our LMS</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Share your experience and help us improve {{ $organization->name ?? 'our platform' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">
                            Status:
                            @if ($rated)
                                <strong class="text-emerald-600">Submitted</strong>
                            @else
                                <strong class="text-amber-500">Pending</strong>
                            @endif
                        </span>
                        @if ($rated)
                            <span class="px-4">Rating: <strong class="text-gray-800">{{ $rating }}/5</strong></span>
                            <span class="pl-4">Submitted: <strong class="text-gray-800">{{ $submittedAt }}</strong></span>
                        @else
                            <span class="pl-4">Rating: <strong class="text-gray-400">Not rated yet</strong></span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Mobile / Tablet status --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>
                    Status:
                    @if ($rated)
                        <strong class="text-emerald-600">Submitted</strong>
                    @else
                        <strong class="text-amber-500">Pending</strong>
                    @endif
                </span>
                @if ($rated)
                    <span>Rating: <strong class="text-gray-800">{{ $rating }}/5</strong></span>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         CONTENT
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        <div class="{{ $rated ? '' : 'max-w-2xl mx-auto' }}">

            @if (!$rated)
                {{-- ════════ Rating Form ════════ --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

                    {{-- Card Header --}}
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h2 class="text-base font-semibold text-gray-900">How was your experience?</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Your honest feedback helps us improve the platform</p>
                    </div>

                    <div class="px-6 py-6 space-y-6">

                        {{-- Star Rating --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Select your rating</label>
                            <div class="flex items-center gap-1.5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" wire:click="setRating({{ $i }})"
                                        class="focus:outline-none transition-transform duration-150 hover:scale-110 active:scale-95">
                                        <svg class="w-11 h-11 transition-colors duration-150 {{ $i <= $rating ? 'text-amber-400 fill-amber-400' : 'text-gray-200 fill-gray-100 hover:text-amber-300 hover:fill-amber-200' }}"
                                            stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </button>
                                @endfor
                                @if ($rating > 0)
                                    <span class="ml-3 text-sm font-medium text-gray-700">
                                        @if ($rating == 1) Poor
                                        @elseif ($rating == 2) Fair
                                        @elseif ($rating == 3) Good
                                        @elseif ($rating == 4) Very Good
                                        @else Excellent
                                        @endif
                                    </span>
                                @endif
                            </div>
                            @error('rating')
                                <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Feedback --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Your Comments <span class="text-red-500">*</span>
                            </label>
                            <textarea wire:model="feedback" rows="5"
                                placeholder="Tell us what you liked or what we can improve..."
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800
                                       focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none transition-colors
                                       placeholder:text-gray-400"></textarea>
                            @error('feedback')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-end">
                        <button type="button" wire:click="submit" wire:loading.attr="disabled"
                            class="px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md
                                   transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-1.5">
                            <span wire:loading.remove wire:target="submit">Submit Feedback</span>
                            <span wire:loading wire:target="submit" class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Submitting...
                            </span>
                        </button>
                    </div>
                </div>

            @else
                {{-- ════════ Thank You / Submitted ════════ --}}
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

                    {{-- Success Header --}}
                    <div class="px-6 py-6 border-b border-gray-100 text-center">
                        <div class="w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">Thank you for your feedback!</h2>
                        <p class="text-xs text-gray-500 mt-1">Submitted on {{ $submittedAt }}</p>
                    </div>

                    <div class="px-6 py-6 space-y-5">

                        {{-- Rating Display --}}
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Your Rating</p>
                            <div class="flex items-center gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-7 h-7 {{ $i <= $rating ? 'text-amber-400 fill-amber-400' : 'text-gray-200 fill-gray-100' }}"
                                        stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                @endfor
                                <span class="ml-2.5 text-sm font-medium text-gray-700">
                                    {{ $rating }}/5 —
                                    @if ($rating == 1) Poor
                                    @elseif ($rating == 2) Fair
                                    @elseif ($rating == 3) Good
                                    @elseif ($rating == 4) Very Good
                                    @else Excellent
                                    @endif
                                </span>
                            </div>
                        </div>

                        {{-- Feedback Display --}}
                        @if ($feedback)
                            <div class="border-t border-gray-100 pt-5">
                                <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Your Comments</p>
                                <div class="bg-gray-50 border-l-2 border-blue-500 rounded-r-md px-4 py-3">
                                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $feedback }}</p>
                                </div>
                            </div>
                        @endif

                        <p class="text-xs text-gray-400 text-center pt-3 border-t border-gray-100">
                            Your feedback has been recorded. We appreciate your time!
                        </p>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
