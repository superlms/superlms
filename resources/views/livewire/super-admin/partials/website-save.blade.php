{{-- The School Website builder's Save, at the foot of each tab (it saves all three). Param: $saveLabel --}}
<button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
    class="px-4 py-1.5 text-sm font-medium rounded-md bg-gray-900 hover:bg-gray-800 text-white disabled:opacity-60">
    <span wire:loading.remove wire:target="save">{{ $saveLabel }}</span>
    <span wire:loading wire:target="save">Saving…</span>
</button>
