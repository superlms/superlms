{{--
    One block of a Website Data page (App\Support\WebsitePages), in the About
    App editor's look: a heading, a note, a link to the page that fills a
    section, what the site reads live from the school's records, or a white
    card with the label (and its help) in a header row and the block's input
    under it. Params: $b, $bare (a field in a card shared with the text
    blocks around it), $alone (the card's only field: the card carries the label).
--}}
@php
    $id    = $b['id'];
    $type  = $b['type'];
    $val   = $data[$id] ?? null;
    $input = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
    $card  = 'bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden';
    $alone = $alone ?? false;
@endphp

@if ($type === 'heading')
    <h2 class="px-1 pt-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $b['label'] }}</h2>
@elseif ($type === 'note')
    <p class="flex items-start gap-2 px-5 py-3.5 rounded-xl border border-dashed border-gray-300 bg-white/60 text-sm text-gray-500">
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ $b['label'] }}</span>
    </p>
@elseif ($type === 'link')
    @php $to = \App\Models\SchoolWebsite::allPages()[$b['to']] ?? $b['to']; @endphp
    <button type="button" x-on:click="{{ $leave }} $wire.openPage('{{ $b['to'] }}')"
        class="{{ $card }} w-full flex items-center gap-3 px-5 py-3.5 text-left hover:bg-gray-50 transition-colors">
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-gray-800">{{ $b['label'] }}</span>
            <span class="block text-xs text-gray-500">{{ $b['help'] ?? '' }} Filled in on {{ $to }}.</span>
        </span>
        <span class="text-xs font-medium text-blue-600 whitespace-nowrap">Open {{ $to }} &rarr;</span>
    </button>
@elseif ($type === 'live')
    @include('livewire.admin.website-data.live')
@elseif ($bare)
    {{-- A field of a shared card: a text a cell of the grid, a paragraph the whole row. --}}
    <div class="{{ $type === 'textarea' ? 'sm:col-span-2 xl:col-span-3' : '' }}" wire:key="blk-{{ $id }}">
        @if (! $alone)
            <label for="f-{{ $id }}" class="block text-sm font-medium text-gray-700 mb-1.5">
                {{ $b['label'] }}@if (!empty($b['required']))<span class="text-red-500"> *</span>@endif
            </label>
        @endif
        @if (!empty($b['help']))
            <p class="-mt-1 mb-1.5 text-xs text-gray-400">{{ $b['help'] }}</p>
        @endif
        @if ($type === 'text')
            <input id="f-{{ $id }}" type="text" wire:model="data.{{ $id }}" placeholder="{{ $b['placeholder'] ?? '' }}" aria-label="{{ $b['label'] }}" class="{{ $input }}">
        @else
            <textarea id="f-{{ $id }}" wire:model="data.{{ $id }}" rows="{{ $b['rows'] ?? 4 }}" placeholder="{{ $b['placeholder'] ?? '' }}" aria-label="{{ $b['label'] }}"
                class="{{ $input }} leading-relaxed resize-y"></textarea>
        @endif
        @error("data.{$id}")<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
@else
    <div class="{{ $card }}" wire:key="blk-{{ $id }}">
        <div class="px-5 py-3.5 border-b border-gray-100 flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
            <h2 class="text-sm font-semibold text-gray-800">
                {{ $b['label'] }}@if (!empty($b['required']))<span class="text-red-500"> *</span>@endif
            </h2>
            @if ($type === 'list')
                <span class="text-xs text-gray-400">{{ count($val ?? []) }} {{ \Illuminate\Support\Str::plural('row', count($val ?? [])) }}</span>
            @elseif ($type === 'images')
                <span class="text-xs text-gray-400">{{ count($val ?? []) }} {{ \Illuminate\Support\Str::plural('photo', count($val ?? [])) }}</span>
            @endif
            @if (!empty($b['help']))
                <p class="basis-full text-xs text-gray-500">{{ $b['help'] }}</p>
            @endif
        </div>

        @switch($type)
            @case('image')
            @case('file')
                <div class="p-5">
                    @include('livewire.admin.website-data.media', ['kind' => $type, 'path' => $id, 'value' => (string) $val])
                </div>
                @break

            @case('images')
                <div class="p-5">
                    <div class="grid grid-cols-3 sm:grid-cols-6 xl:grid-cols-8 gap-2.5">
                        @foreach ($val ?? [] as $i => $url)
                            <div class="relative aspect-square rounded-lg overflow-hidden border border-gray-200 bg-gray-50" wire:key="{{ $id }}-ph-{{ $i }}-{{ md5($url) }}">
                                <img src="{{ \App\Models\SchoolWebsite::media($url) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                                <button type="button" wire:click="removeRow('{{ $id }}', {{ $i }})" title="Remove"
                                    class="absolute top-1 right-1 w-6 h-6 rounded-full bg-white/90 text-gray-500 hover:text-red-600 shadow-sm flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @endforeach
                        @include('livewire.admin.website-data.many', [
                            'target' => "uploads.{$id}",
                            'label'  => 'Add photos',
                            'class'  => 'aspect-square rounded-lg border border-dashed border-gray-300 flex flex-col items-center justify-center gap-1 px-1 text-center text-xs text-gray-500 hover:bg-gray-50 cursor-pointer',
                            'icon'   => '<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                        ])
                    </div>
                    @error("uploads.{$id}")<p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>@enderror
                </div>
                @break

            @case('list')
                @include('livewire.admin.website-data.list')
                @break

            @case('location')
                <div class="p-5">
                    @include('livewire.admin.website-data.location')
                </div>
                @break
        @endswitch

        @error("data.{$id}")<p class="px-5 pb-4 text-xs text-red-500">{{ $message }}</p>@enderror
    </div>
@endif
