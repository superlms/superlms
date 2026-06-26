<div class="min-h-screen bg-gray-50">

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Lists</h1>
                <p class="text-sm text-gray-500 mt-0.5">School roster and custom lists</p>
            </div>
        </div>
    </div>

    {{-- ─── Coming Soon body ───────────────────────────────── --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 sm:p-14 text-center">
            <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-5 rounded-full bg-blue-50 flex items-center justify-center">
                <svg class="w-9 h-9 sm:w-10 sm:h-10 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Lists are coming soon</h2>
            <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
                This is where custom lists for your school will live. The page is set up — content will arrive in a future update.
            </p>
            <a href="{{ route('admin.home', ['organization' => $organization]) }}"
                class="inline-flex items-center gap-1.5 mt-6 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-md transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Home
            </a>
        </div>
    </div>
</div>
