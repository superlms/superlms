<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, with inline analytics)
    ══════════════════════════════════════════════════ --}}
    @php
        $totalCount   = $contacts->count();
        $pendingCount = $contacts->filter(fn($c) => empty($c->super_admin_reply))->count();
        $repliedCount = $contacts->filter(fn($c) => !empty($c->super_admin_reply))->count();
    @endphp
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Contact Super Admin</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Send and manage your queries to Super Admin</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalCount }}</strong></span>
                        <span class="px-4">Pending: <strong class="text-amber-500">{{ $pendingCount }}</strong></span>
                        <span class="pl-4">Replied: <strong class="text-emerald-600">{{ $repliedCount }}</strong></span>
                    </div>
                    <button wire:click="onAddContact"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Contact Super Admin</span>
                        <span class="sm:hidden">New</span>
                    </button>
                </div>
            </div>

            {{-- Mobile / Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $totalCount }}</strong></span>
                <span>Pending: <strong class="text-amber-500">{{ $pendingCount }}</strong></span>
                <span>Replied: <strong class="text-emerald-600">{{ $repliedCount }}</strong></span>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             FILTER BAR (full-width, attached to header)
        ══════════════════════════════════════════════════ --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 hidden sm:inline">Last:</span>
                    <div class="flex gap-1">
                        @foreach ([7, 15, 30] as $days)
                            <button wire:click="applyFilter('days', {{ $days }})"
                                class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors
                                       {{ $filterDays == $days
                                           ? 'bg-blue-600 text-white'
                                           : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                                {{ $days }}d
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="h-5 w-px bg-gray-300 hidden sm:block"></div>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 hidden sm:inline">Status:</span>
                    <select wire:model.live="statusFilter"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1 text-gray-700
                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All</option>
                        <option value="pending">Pending Reply</option>
                        <option value="replied">Replied</option>
                    </select>
                </div>

                @if ($filterDays || $statusFilter)
                    <button wire:click="clearFilters"
                        class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600
                               bg-white border border-red-200 rounded-md hover:bg-red-50 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         CONTACTS LIST
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        <div class="space-y-3">
            @forelse($contacts as $contact)
                <div class="group bg-white rounded-xl border border-gray-200 hover:border-blue-200 hover:shadow-md
                            transition-all duration-200 overflow-hidden">
                    <div class="flex items-stretch">

                        {{-- Status accent bar --}}
                        <div class="w-1 flex-shrink-0
                            {{ $contact->super_admin_reply ? 'bg-emerald-500' : 'bg-amber-400' }}"></div>

                        <div class="flex-1 p-4 sm:p-5 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3 flex-1 min-w-0">

                                    {{-- Status icon --}}
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0
                                        {{ $contact->super_admin_reply ? 'bg-emerald-50' : 'bg-amber-50' }}">
                                        @if ($contact->super_admin_reply)
                                            <svg class="w-4.5 h-4.5 text-emerald-600 w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        {{-- Title row --}}
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <h3 class="text-base font-semibold text-gray-900">{{ $contact->topic }}</h3>
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide
                                                {{ $contact->super_admin_reply ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ $contact->super_admin_reply ? 'Replied' : 'Pending' }}
                                            </span>
                                            @if ($contact->image)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                    </svg>
                                                    Attachment
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Query preview --}}
                                        <p class="text-sm text-gray-600 line-clamp-2 mb-2.5 leading-relaxed">
                                            {{ Str::limit($contact->admin_query, 140) }}
                                        </p>

                                        {{-- Meta footer --}}
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span class="font-medium text-gray-600">{{ $contact->user->name }}</span>
                                            </span>
                                            <span class="text-gray-300">•</span>
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ $contact->created_at->format('M j, Y · g:i A') }}
                                            </span>
                                            <span class="text-gray-300">•</span>
                                            <span class="text-gray-400">{{ $contact->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button wire:click="onViewContact({{ $contact->id }})" title="View"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    @if (!$contact->super_admin_reply)
                                        <button wire:click="onEditContact({{ $contact->id }})" title="Edit"
                                            class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                    @endif

                                    <button wire:click="onDeleteContact({{ $contact->id }})" title="Delete"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-20 bg-white rounded-xl border border-gray-200">
                    <div class="w-14 h-14 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800 mb-1">No messages found</h3>
                    <p class="text-sm text-gray-400">Messages sent to Super Admin will appear here.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT SLIDE-IN PANEL (clean & minimal)
    ══════════════════════════════════════════════════ --}}
    @if ($open)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>

            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $editId ? 'Edit Message' : 'New Message' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editId ? 'Update your query' : 'Send a query to Super Admin' }}</p>
                    </div>
                    <button wire:click="closeModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Panel Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                    {{-- Topic --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Topic <span class="text-red-500">*</span>
                        </label>
                        <input wire:model.defer="topic" type="text" placeholder="What is this about?"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors
                                   placeholder:text-gray-400" />
                        @error('topic')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Message --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Your Message <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model.defer="admin_query" rows="6" placeholder="Describe your query in detail..."
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none transition-colors
                                   placeholder:text-gray-400"></textarea>
                        @error('admin_query')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Existing attachment when editing --}}
                    @if ($editId && $existingImage && !$image)
                        @php $extExisting = strtolower(pathinfo($existingImage, PATHINFO_EXTENSION)); @endphp
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Attachment</label>
                            <div class="border border-gray-200 rounded-md p-3 flex items-center gap-3">
                                @if (in_array($extExisting, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                    <img src="{{ $existingImage }}"
                                        class="w-12 h-12 rounded object-cover border border-gray-200">
                                @elseif($extExisting === 'pdf')
                                    <div class="w-12 h-12 bg-red-50 rounded flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-700 font-medium">{{ in_array($extExisting, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? 'Image' : 'PDF' }} attached</p>
                                    <a href="{{ $existingImage }}" target="_blank"
                                        class="text-xs text-blue-600 hover:underline">View ↗</a>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1.5">Upload a new file below to replace.</p>
                        </div>
                    @endif

                    {{-- File upload --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Attachment <span class="font-normal text-gray-400">(Optional)</span>
                        </label>
                        <div class="border border-dashed border-gray-300 hover:border-gray-400 rounded-md p-5
                                    text-center transition-colors cursor-pointer"
                            onclick="document.getElementById('contactFileInput').click()">
                            <svg class="w-6 h-6 text-gray-400 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="text-sm text-gray-600">Click to upload</p>
                            <p class="text-xs text-gray-400 mt-0.5">JPG, PNG or PDF · max 2MB</p>
                            <input id="contactFileInput" type="file" wire:model="image" accept="image/*,.pdf" class="hidden">
                        </div>

                        <div wire:loading wire:target="image" class="flex items-center gap-1.5 text-xs text-blue-600 mt-2">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Uploading...
                        </div>

                        @if ($image)
                            @php
                                $ext = strtolower(
                                    method_exists($image, 'getClientOriginalExtension')
                                        ? $image->getClientOriginalExtension()
                                        : pathinfo($image->getFilename(), PATHINFO_EXTENSION),
                                );
                            @endphp
                            <div class="mt-2 border border-gray-200 rounded-md p-3 flex items-center gap-3" wire:loading.remove wire:target="image">
                                @if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                    <img src="{{ $image->temporaryUrl() }}"
                                        class="w-12 h-12 rounded object-cover border border-gray-200">
                                @elseif($ext === 'pdf')
                                    <div class="w-12 h-12 bg-red-50 rounded flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-700 truncate">{{ $image->getClientOriginalName() }}</p>
                                    <p class="text-xs text-emerald-600">Ready to upload</p>
                                </div>
                            </div>
                        @endif
                        @error('image')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Panel Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                        Cancel
                    </button>
                    <button type="button" wire:click="onSave" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md
                               transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="onSave">
                            {{ $editId ? 'Update' : 'Send Message' }}
                        </span>
                        <span wire:loading wire:target="onSave" class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW SLIDE-IN PANEL (clean & minimal)
    ══════════════════════════════════════════════════ --}}
    @if ($showViewModal && !empty($viewData))
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>

            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="block w-2 h-2 rounded-full flex-shrink-0
                            {{ $viewData['contact']->super_admin_reply ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 truncate">Message Details</h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $viewData['contact']->super_admin_reply ? 'Replied' : 'Pending reply' }}
                                · {{ $viewData['contact']->created_at->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="closeViewModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Panel Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

                    {{-- Sender info --}}
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Organization</p>
                            <p class="text-sm text-gray-800 truncate">{{ $viewData['organization']->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Sent By</p>
                            <p class="text-sm text-gray-800 truncate">{{ $viewData['user']->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $viewData['user']->email ?? '' }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-100"></div>

                    {{-- Topic --}}
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Topic</p>
                        <p class="text-base font-medium text-gray-900">{{ $viewData['contact']->topic }}</p>
                    </div>

                    {{-- Message --}}
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Message</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">
                            {{ $viewData['contact']->admin_query }}
                        </p>
                    </div>

                    {{-- My Attachment --}}
                    @if (!empty($viewData['contact']->image))
                        @php
                            $attUrl = $viewData['contact']->image;
                            $attExt = strtolower(pathinfo(parse_url($attUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                        @endphp
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Attachment</p>
                            @if (in_array($attExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                <img src="{{ $attUrl }}" class="w-full rounded-md border border-gray-200" alt="Attachment">
                                <a href="{{ $attUrl }}" target="_blank"
                                    class="text-xs text-blue-600 hover:underline mt-2 inline-block">Open in new tab ↗</a>
                            @elseif($attExt === 'pdf')
                                <iframe src="{{ $attUrl }}" class="w-full h-80 border border-gray-200 rounded-md"></iframe>
                                <a href="{{ $attUrl }}" target="_blank"
                                    class="text-xs text-blue-600 hover:underline mt-2 inline-block">Open / Download PDF ↗</a>
                            @else
                                <a href="{{ $attUrl }}" target="_blank" class="text-sm text-blue-600 hover:underline">View Attachment ↗</a>
                            @endif
                        </div>
                    @endif

                    <div class="border-t border-gray-100"></div>

                    {{-- Super Admin Reply --}}
                    @if ($viewData['contact']->super_admin_reply)
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Super Admin's Reply</p>
                                <span class="text-xs text-gray-400">
                                    {{ $viewData['contact']->updated_at->format('d M Y · g:i A') }}
                                </span>
                            </div>
                            <div class="bg-gray-50 border-l-2 border-blue-500 rounded-r-md px-4 py-3">
                                <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">
                                    {{ $viewData['contact']->super_admin_text }}
                                </p>
                            </div>

                            @if (!empty($viewData['contact']->super_admin_attachment))
                                @php
                                    $replyUrl = $viewData['contact']->super_admin_attachment;
                                    $replyExt = strtolower(pathinfo(parse_url($replyUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                                @endphp
                                <div class="mt-3">
                                    <p class="text-xs text-gray-400 mb-1.5">Reply attachment:</p>
                                    @if (in_array($replyExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                        <img src="{{ $replyUrl }}" class="w-full rounded-md border border-gray-200">
                                        <a href="{{ $replyUrl }}" target="_blank"
                                            class="text-xs text-blue-600 hover:underline mt-1.5 inline-block">Open in new tab ↗</a>
                                    @elseif($replyExt === 'pdf')
                                        <iframe src="{{ $replyUrl }}" class="w-full h-72 border border-gray-200 rounded-md"></iframe>
                                        <a href="{{ $replyUrl }}" target="_blank"
                                            class="text-xs text-blue-600 hover:underline mt-1.5 inline-block">Open / Download PDF ↗</a>
                                    @else
                                        <a href="{{ $replyUrl }}" target="_blank"
                                            class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:underline">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            Download attachment (.{{ $replyExt }}) ↗
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex items-center gap-2.5 text-sm text-amber-700">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Awaiting response from Super Admin
                        </div>
                    @endif
                </div>

                {{-- Panel Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    @if (!$viewData['contact']->super_admin_reply)
                        <button type="button" wire:click="onEditContact({{ $viewData['contact']->id }})"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700
                                   hover:bg-gray-100 rounded-md transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </button>
                    @else
                        <div></div>
                    @endif
                    <button type="button" wire:click="closeViewModal"
                        class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete message?</h3>
                        <p class="text-sm text-gray-500">This action cannot be undone. The message and its attachment will be permanently removed.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button type="button" wire:click="cancelDelete"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                        Cancel
                    </button>
                    <button type="button" wire:click="confirmDelete" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md
                               transition-colors disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="confirmDelete">Delete</span>
                        <span wire:loading wire:target="confirmDelete" class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Deleting...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
