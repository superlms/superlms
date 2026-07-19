<div class="p-4">
    @php
        $typeColors = [
            'about_app'      => 'bg-blue-100 text-blue-600',
            'privacy_policy' => 'bg-indigo-100 text-indigo-600',
            'terms_condition'=> 'bg-purple-100 text-purple-600',
            'terms_of_use'   => 'bg-violet-100 text-violet-600',
            'rating'         => 'bg-amber-100 text-amber-600',
            'enquiry'        => 'bg-cyan-100 text-cyan-600',
            'support'        => 'bg-rose-100 text-rose-600',
            'credit'         => 'bg-emerald-100 text-emerald-600',
            'activity'       => 'bg-blue-100 text-blue-600',
            'promo'          => 'bg-violet-100 text-violet-600',
        ];
    @endphp

    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold text-gray-800">Notifications</h2>
        @if ($items->isNotEmpty())
            <div class="flex items-center gap-3">
                <button wire:click="markAllAsRead"
                    class="text-blue-500 hover:text-blue-700 text-xs font-medium">
                    Mark all read
                </button>
                <button wire:click="deleteAll" wire:confirm="Delete all notifications?"
                    class="text-red-500 hover:text-red-700 text-xs font-medium">
                    Delete all
                </button>
            </div>
        @endif
    </div>

    {{-- Selected action bar --}}
    @if (count($selected) > 0)
        <div class="flex items-center justify-between mb-3 px-3 py-2 bg-red-50 border border-red-100 rounded-lg">
            <span class="text-xs font-medium text-red-700">{{ count($selected) }} selected</span>
            <div class="flex items-center gap-3">
                <button wire:click="deleteSelected"
                    class="text-xs font-semibold text-red-600 hover:text-red-800">Delete selected</button>
                <button wire:click="$set('selected', [])"
                    class="text-xs font-medium text-gray-500 hover:text-gray-700">Clear</button>
            </div>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($items as $item)
            @php
                $data   = $item->data ?? [];
                $type   = $data['type'] ?? 'activity';
                $color  = $typeColors[$type] ?? 'bg-gray-100 text-gray-500';
                $unread = is_null($item->read_at);
            @endphp
            <div class="p-3 border rounded-lg transition-colors {{ $unread ? 'bg-blue-50/40 border-blue-100' : 'border-gray-200 hover:bg-gray-50' }}">
                <div class="flex items-start space-x-3">
                    <input type="checkbox" wire:model.live="selected" value="{{ $item->id }}"
                        class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $color }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ $data['title'] ?? 'Notification' }}</p>
                        <p class="text-xs text-gray-500">{{ $data['body'] ?? '' }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $item->created_at?->diffForHumans() }}</p>
                    </div>
                    <div class="flex-shrink-0 flex flex-col items-center gap-2">
                        @if ($unread)
                            <span class="w-2 h-2 bg-blue-500 rounded-full inline-block"></span>
                        @endif
                        <button wire:click="deleteOne('{{ $item->id }}')" title="Delete"
                            class="text-gray-300 hover:text-red-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-12 text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <p class="text-sm text-gray-400">No notifications yet</p>
            </div>
        @endforelse
    </div>
</div>
