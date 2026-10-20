{{--
    One block of a Website Data page (App\Support\WebsitePages): a heading, a
    note, a link to the page that fills a section, what the site reads live
    from the school's records, or a card with the label, its help and the
    block's input. Params: $b, $bare (no card: it sits in a card shared with
    the text blocks around it).
--}}
@php
    $id    = $b['id'];
    $type  = $b['type'];
    $val   = $data[$id] ?? null;
    $input = 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500';
@endphp

@if ($type === 'heading')
    <h2 class="px-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $b['label'] }}</h2>
@elseif ($type === 'note')
    <p class="flex items-start gap-2 px-4 py-3 rounded-xl border border-dashed border-gray-300 text-sm text-gray-500">
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ $b['label'] }}</span>
    </p>
@elseif ($type === 'link')
    @php $to = \App\Models\SchoolWebsite::allPages()[$b['to']] ?? $b['to']; @endphp
    <button type="button" x-on:click="{{ $leave }} $wire.openPage('{{ $b['to'] }}')"
        class="w-full flex items-center gap-3 px-4 py-3 bg-white border border-gray-200 rounded-xl text-left hover:bg-gray-50 transition-colors">
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-medium text-gray-800">{{ $b['label'] }}</span>
            <span class="block text-xs text-gray-500">{{ $b['help'] ?? '' }} Filled in on {{ $to }}.</span>
        </span>
        <span class="text-xs font-medium text-blue-600 whitespace-nowrap">Open {{ $to }} &rarr;</span>
    </button>
@elseif ($type === 'live')
    @include('livewire.admin.website-data.live')
@else
    <div class="{{ $bare ? '' : 'bg-white border border-gray-200 rounded-xl p-4' }}" wire:key="blk-{{ $id }}">
        <div class="{{ $bare ? 'mb-1.5' : 'mb-2.5' }}">
            <label @if (in_array($type, ['text', 'textarea'], true)) for="f-{{ $id }}" @endif class="block text-sm font-medium text-gray-800">
                {{ $b['label'] }}@if (!empty($b['required']))<span class="text-red-500"> *</span>@endif
            </label>
            @if (!empty($b['help']))
                <p class="text-xs text-gray-500 mt-0.5">{{ $b['help'] }}</p>
            @endif
        </div>

        @switch($type)
            @case('text')
                <input id="f-{{ $id }}" type="text" wire:model="data.{{ $id }}" placeholder="{{ $b['placeholder'] ?? '' }}" class="{{ $input }}">
                @break

            @case('textarea')
                <textarea id="f-{{ $id }}" wire:model="data.{{ $id }}" rows="{{ $b['rows'] ?? 4 }}" placeholder="{{ $b['placeholder'] ?? '' }}"
                    class="{{ $input }} leading-relaxed resize-y"></textarea>
                @break

            @case('image')
            @case('file')
                @include('livewire.admin.website-data.media', ['kind' => $type, 'path' => $id, 'value' => (string) $val])
                @break

            @case('images')
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
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
                @break

            @case('list')
                @include('livewire.admin.website-data.list')
                @break

            @case('location')
                @include('livewire.admin.website-data.location')
                @break
        @endswitch

        @error("data.{$id}")<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
@endif
