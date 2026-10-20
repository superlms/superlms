{{-- Website Data's Save, in a page's header strip and at its foot — About App's Save. --}}
<button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
    class="flex-1 sm:flex-none px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60 flex-shrink-0">
    <span wire:loading.remove wire:target="save">Save</span>
    <span wire:loading wire:target="save">Saving…</span>
</button>
