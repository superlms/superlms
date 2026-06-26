<div class="min-h-screen bg-gray-50">

    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">More</h1>
                <p class="text-sm text-gray-500 mt-0.5">Account, policies and quick utilities</p>
            </div>
        </div>
    </div>

    {{-- Tile grid — 5 cols x 2 rows on desktop; collapses gracefully on smaller screens. --}}
    <div class="p-3 sm:p-4 md:p-6">
        <div class="grid gap-3 sm:gap-4 grid-cols-2 sm:grid-cols-3 md:grid-cols-5">
            @php
                $colors = ['blue','indigo','purple','green','yellow','pink','teal','rose','cyan','lime','fuchsia','red','orange','amber','sky','violet','gray'];
            @endphp
            @foreach ($items as $i => $item)
                @php
                    $color = $colors[abs(crc32($item['title'])) % count($colors)];
                @endphp
                <a href="{{ route($item['route'], ['organization' => $organization]) }}"
                   wire:key="more-{{ $item['route'] }}"
                   class="flex flex-col items-center justify-start p-3 sm:p-4 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 hover:border-gray-300 active:scale-[0.98] transition overflow-hidden min-w-0">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 bg-{{ $color }}-100 rounded-full flex items-center justify-center mb-2 shrink-0">
                        <x-icon name="{{ $item['icon'] }}" class="w-5 h-5 text-{{ $color }}-600" />
                    </div>
                    <span class="block w-full text-xs sm:text-sm font-medium text-center text-gray-800 leading-snug break-words"
                          style="overflow-wrap: anywhere;">
                        {{ $item['title'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>
