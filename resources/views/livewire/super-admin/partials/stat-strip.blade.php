{{-- Compact analytics section: a single card with "label + number" stats.
     Usage: @include('livewire.super-admin.partials.stat-strip', ['items' => [
        ['label' => 'Total', 'value' => 10, 'color' => 'text-gray-900'], ...
     ]]) --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-2 mb-6">
    <div class="flex flex-wrap gap-1">
        @foreach (($items ?? []) as $it)
            <div class="flex-1 basis-[45%] sm:basis-[150px] px-3.5 py-2.5 rounded-xl hover:bg-gray-50 transition-colors">
                <p class="text-[11px] text-gray-500 uppercase tracking-wide truncate">{{ $it['label'] }}</p>
                <p class="text-lg sm:text-xl font-bold {{ $it['color'] ?? 'text-gray-900' }} mt-0.5 truncate">{{ $it['value'] }}</p>
            </div>
        @endforeach
    </div>
</div>
