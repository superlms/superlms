{{-- A More screen that is only its page so far (Reception, Gatepass, Complaints):
     the header every admin screen has and a note that it is on its way.
     Takes: $title, $icon (a heroicon name). --}}
<div class="min-h-screen bg-gray-50">
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-30">
        <h1 class="text-lg sm:text-xl font-bold text-gray-900">{{ $title }}</h1>
    </div>

    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                <x-icon name="{{ $icon }}" class="w-6 h-6 text-gray-400" />
            </div>
            <p class="text-sm font-semibold text-gray-800">Coming soon</p>
            <p class="text-xs text-gray-400 mt-1">{{ $title }} is on its way.</p>
        </div>
    </div>
</div>
