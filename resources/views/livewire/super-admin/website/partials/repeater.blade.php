{{-- Generic repeater for a list inside $meta.
     Params:
       $key      — meta key holding the array (e.g. 'items', 'jobs', 'faqs')
       $label    — section heading
       $singular — singular row label (e.g. 'Card', 'Job', 'FAQ')
       $fields   — [['name'=>..,'label'=>..,'type'=>'text|textarea','placeholder'=>..,'full'=>bool], ...]
       $cols     — (optional) grid columns for the fields, default 1
--}}
@php $rows = $meta[$key] ?? []; $cols = $cols ?? 1; @endphp
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-purple-50 flex items-center justify-between">
        <div>
            <h2 class="text-base font-semibold text-gray-900">{{ $label }}</h2>
            <p class="text-xs text-gray-400">{{ count($rows) }} {{ \Illuminate\Support\Str::plural($singular, count($rows)) }}</p>
        </div>
        <button wire:click="addRow('{{ $key }}')"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add {{ $singular }}
        </button>
    </div>
    <div class="p-6 space-y-4">
        @foreach ($rows as $i => $row)
            <div class="border border-gray-200 rounded-xl p-4 hover:border-indigo-200 transition-colors" wire:key="{{ $key }}-{{ $i }}">
                <div class="flex items-center justify-between mb-3">
                    <span class="flex items-center gap-2">
                        <span class="w-6 h-6 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-full flex items-center justify-center">{{ $i + 1 }}</span>
                        <span class="text-sm font-medium text-gray-700">{{ $singular }} {{ $i + 1 }}</span>
                    </span>
                    @if (count($rows) > 1)
                        <button wire:click="confirmRemoveRow('{{ $key }}', {{ $i }})"
                            class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    @endif
                </div>
                <div class="grid gap-3 {{ $cols == 2 ? 'sm:grid-cols-2' : '' }}">
                    @foreach ($fields as $f)
                        <div class="{{ ($f['full'] ?? false) ? 'sm:col-span-2' : '' }}">
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ $f['label'] }}</label>
                            @if (($f['type'] ?? 'text') === 'textarea')
                                <textarea wire:model="meta.{{ $key }}.{{ $i }}.{{ $f['name'] }}" rows="3"
                                    placeholder="{{ $f['placeholder'] ?? '' }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 resize-y"></textarea>
                            @else
                                <input type="text" wire:model="meta.{{ $key }}.{{ $i }}.{{ $f['name'] }}"
                                    placeholder="{{ $f['placeholder'] ?? '' }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <button wire:click="addRow('{{ $key }}')"
            class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-400 hover:border-indigo-400 hover:text-indigo-500 transition-colors flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add another {{ \Illuminate\Support\Str::lower($singular) }}
        </button>
    </div>
</div>
