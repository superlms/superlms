{{-- Sticky header bar with the page name, live URL and a Save button.
     Params: $heading, $description, $url (relative path of the live page) --}}
<div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sticky top-0 z-40">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <img src="{{ asset('website-image/Group 11525.png') }}" alt="EDYONE LMS"
                class="w-11 h-11 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $heading }}</h1>
                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $description }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ url($url) }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                View Live
            </a>
            <button wire:click="save"
                class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm hover:shadow transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Save Changes
            </button>
        </div>
    </div>
</div>
