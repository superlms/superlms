{{-- Website Data's Save, in a page's header and at its foot. --}}
<button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
    class="px-4 py-1.5 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-sm disabled:opacity-60 flex-shrink-0">
    <span wire:loading.remove wire:target="save">Save</span>
    <span wire:loading wire:target="save">Saving…</span>
</button>
