{{-- Edit page of a policy document (Terms & Conditions, Terms of Use,
     Privacy Policy) in the Students list's look: the header strip with the
     title and Cancel / Save (the same height as every Super Admin header),
     then one white card on the grey page — a quiet S.No / Heading & text
     header row, a row per section with its number, heading box and text box
     and the Students list's red delete icon, and "Add section" / Save at the
     card's foot. Same sections, adds, removes (with their confirm) and save.
     $title     — "Terms & Conditions"
     $exists    — a saved copy exists (Edit) or not (Create)
     $sections  — [['head' => …, 'desc' => …], …] (wire:model sections.N.*)
     $cancel    — the Livewire call that goes back to the view
     $headHint  — placeholder for a heading --}}

{{-- Header strip --}}
<div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
        <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
            <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $exists ? 'Edit' : 'Create' }} {{ $title }}</h1>
            <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">
                {{ count($sections) }} {{ count($sections) === 1 ? 'section' : 'sections' }}
                · last updated becomes {{ now()->format('d M Y') }} when you save
            </p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button type="button" wire:click="{{ $cancel }}"
                class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="flex-1 sm:flex-none px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Save</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </div>
</div>

<div class="p-4 sm:p-6">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            {{-- Fits a phone (min-w-0! beats app.css's 640px phone floor for tables):
                 S.No and Actions narrow there, no sideways scroll. --}}
            <table class="w-full table-fixed min-w-0!">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-3 sm:px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-14 sm:w-16">S.No</th>
                        <th class="px-3 sm:px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Heading &amp; Text</th>
                        <th class="px-1 sm:px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-16 sm:w-20">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($sections as $index => $section)
                        <tr class="align-top" wire:key="doc-section-{{ $index }}">
                            <td class="px-3 sm:px-4 pb-4 pt-6 text-sm text-gray-500 font-medium tabular-nums">{{ $index + 1 }}</td>
                            <td class="px-3 sm:px-4 py-4 space-y-2">
                                <input type="text" wire:model="sections.{{ $index }}.head" placeholder="{{ $headHint }}" aria-label="Heading {{ $index + 1 }}"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-900 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error("sections.{$index}.head") border-red-400 @enderror">
                                @error("sections.{$index}.head")
                                    <p class="text-xs text-red-500">{{ $message }}</p>
                                @enderror
                                <textarea wire:model="sections.{{ $index }}.desc" rows="4" placeholder="Write this section…" aria-label="Text {{ $index + 1 }}"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800 leading-relaxed resize-y focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error("sections.{$index}.desc") border-red-400 @enderror"></textarea>
                                @error("sections.{$index}.desc")
                                    <p class="text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-1 sm:px-4 pb-4 pt-5">
                                <div class="flex items-center justify-center">
                                    @if (count($sections) > 1)
                                        <button type="button" wire:click="confirmRemoveSection({{ $index }})" title="Delete section"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-300">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Add section · Save, at the card's foot --}}
        <div class="px-5 py-3.5 border-t border-gray-100 flex items-center justify-between gap-3">
            <button type="button" wire:click="addSection"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Add section
            </button>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">Save</button>
        </div>
    </div>
</div>
