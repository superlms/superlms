{{-- Common page-header fields (tag / title / subtitle).
     Bound to meta.tag / meta.title / meta.subtitle. --}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-purple-50 flex items-center gap-3">
        <div class="w-8 h-8 bg-indigo-500 rounded-xl flex items-center justify-center shadow-sm">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h7" />
            </svg>
        </div>
        <h2 class="text-base font-semibold text-gray-900">Page Header</h2>
    </div>
    <div class="p-6 space-y-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tag / Badge</label>
            <input type="text" wire:model="meta.tag" placeholder="e.g., Why SUPERLMS"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
            <input type="text" wire:model="meta.title" placeholder="Main heading for the page"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Subtitle</label>
            <textarea wire:model="meta.subtitle" rows="3" placeholder="Short paragraph shown under the title"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 resize-y"></textarea>
        </div>
    </div>
</div>
