{{--
    A list block in Website Data (slides, people, facilities, documents…): a
    row a card, its photo on the left (a click uploads one), its other columns
    beside it, and up / down / remove; Add, and for photo lists "Upload
    several photos" (a row a photo). Uses $b, $id, $input from the block.
--}}
@php
    $fields   = $b['fields'];
    $photoKey = array_key_first(array_filter($fields, fn ($f) => $f[0] === 'image'));
    $rest     = array_filter($fields, fn ($k) => $k !== $photoKey, ARRAY_FILTER_USE_KEY);
    $short    = count(array_filter($rest, fn ($f) => in_array($f[0], ['text', 'number'], true)));
    $rows     = $data[$id] ?? [];
    $last     = count($rows) - 1;
    $tool     = 'w-7 h-7 rounded-md flex items-center justify-center text-gray-400 hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent';
@endphp
<div class="space-y-2">
    @foreach ($rows as $i => $row)
        <div class="flex gap-3 p-3 rounded-lg border border-gray-200" wire:key="{{ $id }}-row-{{ $i }}">
            @if ($photoKey)
                @php $photo = (string) ($row[$photoKey] ?? ''); @endphp
                <label class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex flex-col items-center justify-center gap-0.5 flex-shrink-0 cursor-pointer hover:border-gray-300"
                    title="{{ $photo !== '' ? 'Change photo' : 'Upload photo' }}">
                    @if ($photo !== '')
                        <img src="{{ \App\Models\SchoolWebsite::media($photo) }}" alt="" class="w-full h-full object-cover">
                    @else
                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 9a2 2 0 012-2h.9a2 2 0 001.7-.9l.8-1.2A2 2 0 0110.1 4h3.8a2 2 0 011.7.9l.8 1.2a2 2 0 001.7.9h.9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="text-[10px] text-gray-400">{{ $fields[$photoKey][1] }}</span>
                    @endif
                    <span wire:loading.flex wire:target="uploads.{{ $id }}.{{ $i }}.{{ $photoKey }}"
                        class="absolute inset-0 bg-white/85 items-center justify-center text-[10px] text-gray-500">Uploading…</span>
                    <input type="file" accept="image/*" class="sr-only" wire:model="uploads.{{ $id }}.{{ $i }}.{{ $photoKey }}">
                </label>
            @endif

            <div class="min-w-0 flex-1 grid gap-2 {{ $short > 1 ? 'sm:grid-cols-2' : '' }}">
                @foreach ($rest as $f => $spec)
                    @php [$ftype, $flabel] = $spec; $ph = $spec[2] ?? ''; @endphp
                    <div class="{{ in_array($ftype, ['textarea', 'file'], true) ? 'sm:col-span-2' : '' }}">
                        <label class="block text-[11px] text-gray-500 mb-0.5">{{ $flabel }}</label>
                        @if ($ftype === 'textarea')
                            <textarea wire:model="data.{{ $id }}.{{ $i }}.{{ $f }}" rows="3" placeholder="{{ $ph }}" class="{{ $input }} resize-y"></textarea>
                        @elseif ($ftype === 'file')
                            @include('livewire.admin.website-data.media', ['kind' => 'file', 'path' => "{$id}.{$i}.{$f}", 'value' => (string) ($row[$f] ?? ''), 'compact' => true])
                        @elseif ($ftype === 'number')
                            <input type="number" min="0" wire:model.live.debounce.400ms="data.{{ $id }}.{{ $i }}.{{ $f }}" placeholder="{{ $ph }}" class="{{ $input }}">
                        @else
                            <input type="text" wire:model="data.{{ $id }}.{{ $i }}.{{ $f }}" placeholder="{{ $ph }}" class="{{ $input }}">
                        @endif
                    </div>
                @endforeach

                @if (!empty($b['percent']))
                    @php [$part, $whole] = $b['percent']; $of = (int) ($row[$whole] ?? 0); @endphp
                    <p class="sm:col-span-2 text-xs text-gray-500">Pass percentage:
                        <b class="text-gray-800 tabular-nums">{{ $of > 0 ? round((int) ($row[$part] ?? 0) / $of * 100, 1) : 0 }}%</b></p>
                @endif
                @if ($photoKey)
                    @error("uploads.{$id}.{$i}.{$photoKey}")<p class="sm:col-span-2 text-xs text-red-500">{{ $message }}</p>@enderror
                @endif
            </div>

            <div class="flex flex-col items-center gap-0.5 flex-shrink-0">
                <button type="button" wire:click="moveRow('{{ $id }}', {{ $i }}, -1)" @disabled($i === 0) title="Move up" class="{{ $tool }} hover:text-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                </button>
                <button type="button" wire:click="moveRow('{{ $id }}', {{ $i }}, 1)" @disabled($i === $last) title="Move down" class="{{ $tool }} hover:text-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <button type="button" wire:click="removeRow('{{ $id }}', {{ $i }})" title="Remove" class="{{ $tool }} hover:text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.9 12.1A2 2 0 0116.1 21H7.9a2 2 0 01-2-1.9L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>
    @endforeach

    @if (! $rows)
        <p class="px-1 text-xs text-gray-400">Nothing added yet.</p>
    @endif

    <div class="flex flex-wrap items-center gap-2 pt-1">
        <button type="button" wire:click="addRow('{{ $id }}')"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ $b['add'] ?? 'Add' }}
        </button>
        @if (!empty($b['bulk']))
            @include('livewire.admin.website-data.many', [
                'target' => "uploads.{$id}",
                'label'  => 'Upload several photos',
                'class'  => 'inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 cursor-pointer',
                'icon'   => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4m0 0L8 8m4-4v12"/></svg>',
            ])
        @endif
    </div>
    @error("uploads.{$id}")<p class="text-xs text-red-500">{{ $message }}</p>@enderror
</div>
