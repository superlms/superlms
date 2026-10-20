<div>
    @if ($on)
        {{-- A row of the View panel's list: the person's ID card for the machines. --}}
        <div class="grid grid-cols-3 gap-3 text-sm">
            <span class="text-xs text-gray-400 uppercase tracking-wider pt-1">ID Card (RFID)</span>
            <div class="col-span-2">
                @if (!$listening)
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($card)
                            <span class="font-mono text-gray-800 font-medium">{{ $card }}</span>
                            <button type="button" wire:click="listen" class="text-xs font-medium text-violet-600 hover:text-violet-800">Change</button>
                            <button type="button" wire:click="remove" wire:confirm="Remove this card? It stops working on the machines."
                                class="text-xs font-medium text-red-600 hover:text-red-700">Remove</button>
                        @else
                            <span class="text-gray-400">No card</span>
                            <button type="button" wire:click="listen"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-violet-700 border border-violet-200 rounded-md hover:bg-violet-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                                Assign card
                            </button>
                        @endif
                    </div>
                @else
                    {{-- The USB reader types the number and Enter here; it can be typed too. --}}
                    <div class="rounded-md border border-violet-200 bg-violet-50/60 p-3" data-kb-nav="off" x-data x-init="$nextTick(() => $refs.card.focus())">
                        <p class="text-xs font-medium text-violet-800 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-violet-500 animate-pulse"></span>
                            Tap the card on the USB reader…
                        </p>
                        <input x-ref="card" type="text" inputmode="numeric" autocomplete="off" wire:model="cardInput" wire:keydown.enter.prevent="save"
                            x-on:blur="setTimeout(() => { if (document.activeElement === document.body) $refs.card && $refs.card.focus() }, 150)"
                            placeholder="Card number"
                            class="mt-2 w-full text-sm font-mono border border-gray-300 rounded-md px-3 py-1.5 bg-white focus:ring-1 focus:ring-violet-400 focus:border-violet-400">
                        @error('cardInput') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        <div class="flex items-center justify-end gap-2 mt-2">
                            <button type="button" wire:click="cancel" class="px-3 py-1 text-xs font-medium text-gray-600 hover:bg-white rounded-md">Cancel</button>
                            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                                class="px-3 py-1 text-xs font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">Save</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
