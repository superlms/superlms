{{--
    A list block in Website Data (slides, people, facilities, documents…) as
    About App's tables: S.No, the photo (a click uploads one) when the list
    has one, the other columns side by side, and up / down / remove; under
    the table Add, and for photo lists "Upload several photos" (a row a
    photo). Uses $b, $id, $input from the block.
--}}
@php
    $fields   = $b['fields'];
    $photoKey = array_key_first(array_filter($fields, fn ($f) => $f[0] === 'image'));
    $rest     = array_filter($fields, fn ($k) => $k !== $photoKey, ARRAY_FILTER_USE_KEY);
    $short    = count(array_filter($rest, fn ($f) => in_array($f[0], ['text', 'number'], true)));
    $rows     = $data[$id] ?? [];
    $last     = count($rows) - 1;
    $th       = 'px-3 sm:px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider';
    $tool     = 'p-1.5 rounded-lg transition-colors disabled:opacity-30 disabled:hover:bg-transparent';
@endphp
<div class="overflow-x-auto">
    <table class="w-full table-fixed min-w-[560px]">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="{{ $th }} w-14 sm:w-16">S.No</th>
                @if ($photoKey)<th class="{{ $th }} w-24 sm:w-28">{{ $fields[$photoKey][1] }}</th>@endif
                <th class="{{ $th }}">{{ implode(' · ', array_map(fn ($f) => $f[1], $rest)) }}</th>
                <th class="{{ $th }} !text-center w-28 sm:w-32">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($rows as $i => $row)
                <tr class="align-top" wire:key="{{ $id }}-row-{{ $i }}">
                    <td class="px-3 sm:px-4 pt-6 pb-4 text-sm text-gray-500 font-medium tabular-nums">{{ $i + 1 }}</td>

                    @if ($photoKey)
                        @php $photo = (string) ($row[$photoKey] ?? ''); @endphp
                        <td class="px-3 sm:px-4 py-4">
                            <label class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex flex-col items-center justify-center gap-0.5 cursor-pointer hover:border-gray-300"
                                title="{{ $photo !== '' ? 'Change photo' : 'Upload photo' }}">
                                @if ($photo !== '')
                                    <img src="{{ \App\Models\SchoolWebsite::media($photo) }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 9a2 2 0 012-2h.9a2 2 0 001.7-.9l.8-1.2A2 2 0 0110.1 4h3.8a2 2 0 011.7.9l.8 1.2a2 2 0 001.7.9h.9a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="text-[10px] text-gray-400">Upload</span>
                                @endif
                                <span wire:loading.flex wire:target="uploads.{{ $id }}.{{ $i }}.{{ $photoKey }}"
                                    class="absolute inset-0 bg-white/85 items-center justify-center text-[10px] text-gray-500">Uploading…</span>
                                <input type="file" accept="image/*" class="sr-only" wire:model="uploads.{{ $id }}.{{ $i }}.{{ $photoKey }}">
                            </label>
                            @error("uploads.{$id}.{$i}.{$photoKey}")<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </td>
                    @endif

                    <td class="px-3 sm:px-4 py-4">
                        <div class="grid gap-2.5 {{ $short > 1 ? 'sm:grid-cols-2' : '' }} {{ $short > 2 ? 'xl:grid-cols-3' : '' }}">
                            @foreach ($rest as $f => $spec)
                                @php [$ftype, $flabel] = $spec; $ph = $spec[2] ?? ''; @endphp
                                <div class="{{ in_array($ftype, ['textarea', 'file'], true) ? 'col-span-full' : '' }}">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ $flabel }}</label>
                                    @if ($ftype === 'textarea')
                                        <textarea wire:model="data.{{ $id }}.{{ $i }}.{{ $f }}" rows="3" placeholder="{{ $ph }}" class="{{ $input }} leading-relaxed resize-y"></textarea>
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
                                <p class="col-span-full text-xs text-gray-500">Pass percentage:
                                    <b class="text-gray-800 tabular-nums">{{ $of > 0 ? round((int) ($row[$part] ?? 0) / $of * 100, 1) : 0 }}%</b></p>
                            @endif
                        </div>
                    </td>

                    <td class="px-2 sm:px-4 pt-5 pb-4">
                        <div class="flex items-center justify-center gap-0.5">
                            <button type="button" wire:click="moveRow('{{ $id }}', {{ $i }}, -1)" @disabled($i === 0) title="Move up" class="{{ $tool }} text-gray-500 hover:bg-gray-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <button type="button" wire:click="moveRow('{{ $id }}', {{ $i }}, 1)" @disabled($i === $last) title="Move down" class="{{ $tool }} text-gray-500 hover:bg-gray-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <button type="button" wire:click="removeRow('{{ $id }}', {{ $i }})" title="Remove" class="{{ $tool }} text-red-600 hover:bg-red-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $photoKey ? 4 : 3 }}" class="px-5 py-8 text-center text-sm text-gray-400">Nothing added yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="px-5 py-3.5 border-t border-gray-100 flex flex-wrap items-center gap-x-5 gap-y-2">
    <button type="button" wire:click="addRow('{{ $id }}')" class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        {{ $b['add'] ?? 'Add' }}
    </button>
    @if (!empty($b['bulk']))
        @include('livewire.admin.website-data.many', [
            'target' => "uploads.{$id}",
            'label'  => 'Upload several photos',
            'class'  => 'inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 cursor-pointer',
            'icon'   => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4m0 0L8 8m4-4v12"/></svg>',
        ])
    @endif
    @error("uploads.{$id}")<p class="basis-full text-xs text-red-500">{{ $message }}</p>@enderror
</div>
