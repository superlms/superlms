<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, with inline analytics)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Announcements</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage and publish announcements for your organization</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $stats['total'] }}</strong></span>
                        <span class="px-4">This Month: <strong class="text-blue-600">{{ $stats['this_month'] }}</strong></span>
                        <span class="pl-4">Last Month: <strong class="text-gray-800">{{ $stats['last_month'] }}</strong></span>
                    </div>
                    <button wire:click="openModal"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Add Announcement</span>
                        <span class="sm:hidden">New</span>
                    </button>
                </div>
            </div>

            {{-- Mobile / Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $stats['total'] }}</strong></span>
                <span>This Month: <strong class="text-blue-600">{{ $stats['this_month'] }}</strong></span>
                <span>Last Month: <strong class="text-gray-800">{{ $stats['last_month'] }}</strong></span>
            </div>
        </div>

        {{-- Filter bar (attached, sub-header style) --}}
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
                    <span class="text-xs text-gray-500 hidden sm:inline">Period:</span>
                    <div class="flex gap-1">
                        @foreach ([['all', 'All'], ['7', '7d'], ['15', '15d'], ['30', '30d'], ['60', '60d']] as $opt)
                            <button wire:click="$set('dateFilter', '{{ $opt[0] }}')"
                                class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors
                                       {{ $dateFilter == $opt[0]
                                           ? 'bg-blue-600 text-white'
                                           : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                                {{ $opt[1] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <span class="hidden sm:inline-block w-px h-5 bg-gray-200"></span>

                {{-- Audience filter (All / Student / Teacher) --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 hidden sm:inline">Audience:</span>
                    <div class="flex gap-1">
                        @foreach ([['all', 'All'], ['user', 'Student'], ['teacher', 'Teacher']] as $opt)
                            <button wire:click="$set('typeFilter', '{{ $opt[0] }}')"
                                class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors
                                       {{ $typeFilter == $opt[0]
                                           ? 'bg-blue-600 text-white'
                                           : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                                {{ $opt[1] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         ANNOUNCEMENT LIST
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        <div class="space-y-3">
            @forelse ($announcements as $announcement)
                @php
                    $typeColors = [
                        'all'     => ['bar' => 'bg-purple-500', 'bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'pill' => 'bg-purple-100 text-purple-700'],
                        'user'    => ['bar' => 'bg-emerald-500', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'pill' => 'bg-emerald-100 text-emerald-700'],
                        'teacher' => ['bar' => 'bg-orange-500', 'bg' => 'bg-orange-50', 'text' => 'text-orange-600', 'pill' => 'bg-orange-100 text-orange-700'],
                    ];
                    $tc = $typeColors[$announcement->type] ?? $typeColors['all'];
                @endphp
                <div wire:click="viewAnnouncement({{ $announcement->id }})"
                    class="group bg-white rounded-xl border border-gray-200 hover:border-blue-200 hover:shadow-md
                            transition-all duration-200 overflow-hidden cursor-pointer">
                    <div class="flex items-stretch">

                        {{-- Status accent bar --}}
                        <div class="w-1 flex-shrink-0 {{ $tc['bar'] }}"></div>

                        <div class="flex-1 p-4 sm:p-5 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3 flex-1 min-w-0">

                                    {{-- Megaphone icon --}}
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $tc['bg'] }}">
                                        <svg class="w-5 h-5 {{ $tc['text'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                        </svg>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        {{-- Title row --}}
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <h3 class="text-base font-semibold text-gray-900 truncate group-hover:text-blue-700 transition-colors">
                                                {{ $announcement->announcement_name }}
                                            </h3>
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide {{ $tc['pill'] }}">
                                                {{ $announcement->type === 'user' ? 'Student' : ucfirst($announcement->type) }}
                                            </span>
                                            @if ($announcement->announcement_image)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    Image
                                                </span>
                                            @endif
                                            @if ($announcement->announcement_pdf)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-red-600 bg-red-50 px-2 py-0.5 rounded-full">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                    PDF
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Content preview --}}
                                        <p class="text-sm text-gray-600 line-clamp-2 mb-2.5 leading-relaxed">
                                            {{ $announcement->announcement_content }}
                                        </p>

                                        {{-- Meta footer --}}
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <span class="font-medium text-gray-600">{{ $announcement->user->name }}</span>
                                            </span>
                                            <span class="text-gray-300">•</span>
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ $announcement->created_at->format('M j, Y · g:i A') }}
                                            </span>
                                            <span class="text-gray-300">•</span>
                                            <span class="text-gray-400">{{ $announcement->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex items-center gap-1 flex-shrink-0" onclick="event.stopPropagation()">
                                    <button wire:click="viewAnnouncement({{ $announcement->id }})" title="View"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button wire:click="edit({{ $announcement->id }})" title="Edit"
                                        class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="onDelete({{ $announcement->id }})" title="Delete"
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
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800 mb-1">No announcements yet</h3>
                    <p class="text-sm text-gray-400 mb-4">Create your first announcement to share important information.</p>
                    <button wire:click="openModal"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Announcement
                    </button>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($announcements->hasPages())
            <div class="mt-6">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($open)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>

            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $editId ? 'Edit Announcement' : 'New Announcement' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editId ? 'Update announcement details' : 'Share information with your organization' }}</p>
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

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Title <span class="text-red-500">*</span>
                        </label>
                        <input wire:model.defer="announcementName" type="text" placeholder="Announcement title"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors
                                   placeholder:text-gray-400" />
                        @error('announcementName')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Content --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Content <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model.defer="announcementContent" rows="6" placeholder="Write your announcement content here..."
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none transition-colors
                                   placeholder:text-gray-400"></textarea>
                        @error('announcementContent')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Audience Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Audience <span class="text-red-500">*</span>
                        </label>
                        @php
                            // Full, static class strings so Tailwind compiles the peer-checked
                            // variants. Dynamically built class names (e.g. "border-{$c}-500")
                            // are NOT seen by the build-time scanner, so the selected state
                            // never rendered — which made options look unselectable.
                            $audienceOptions = [
                                'all'     => ['label' => 'All',      'classes' => 'peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:text-purple-700'],
                                'user'    => ['label' => 'Students', 'classes' => 'peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700'],
                                'teacher' => ['label' => 'Teachers', 'classes' => 'peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-700'],
                            ];
                        @endphp
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ($audienceOptions as $value => $opt)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.defer="type" value="{{ $value }}" class="peer sr-only">
                                    <div class="px-3 py-2.5 text-center text-sm font-medium border-2 rounded-md transition-all border-gray-200 text-gray-600 hover:bg-gray-50 {{ $opt['classes'] }}">
                                        {{ $opt['label'] }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('type')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ── Unified attachment uploader — Image OR PDF ── --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Attachment <span class="font-normal text-gray-400">(Optional · Image or PDF, max 5MB)</span>
                        </label>

                        {{-- Existing attachments when editing (icon tiles, click to open, x to remove) --}}
                        @if ($editId && !$announcementFile)
                            @php $ann = \App\Models\Admin\Announcement::find($editId) @endphp
                            @if ($ann && ($ann->announcement_image || $ann->announcement_pdf))
                                <div class="mb-2 flex flex-wrap gap-2">
                                    @if ($ann->announcement_image)
                                        <div class="inline-flex items-center gap-1.5 pl-2.5 pr-1 py-1 rounded-full border border-blue-200 bg-blue-50 text-xs font-medium text-blue-700">
                                            <a href="{{ $ann->announcement_image }}" target="_blank" rel="noopener" title="Open image"
                                                class="inline-flex items-center gap-1.5 hover:underline">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                Image
                                            </a>
                                            <button type="button" wire:click="deleteFile('image')" title="Remove image"
                                                class="w-4 h-4 flex items-center justify-center rounded-full hover:bg-white/70">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                    @if ($ann->announcement_pdf)
                                        <div class="inline-flex items-center gap-1.5 pl-2.5 pr-1 py-1 rounded-full border border-red-200 bg-red-50 text-xs font-medium text-red-700">
                                            <a href="{{ $ann->announcement_pdf }}" target="_blank" rel="noopener" title="Open PDF"
                                                class="inline-flex items-center gap-1.5 hover:underline">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                                PDF
                                            </a>
                                            <button type="button" wire:click="deleteFile('pdf')" title="Remove PDF"
                                                class="w-4 h-4 flex items-center justify-center rounded-full hover:bg-white/70">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif

                        {{-- Compact uploader (Standard add-subject style) — image OR PDF --}}
                        <input id="annFileInput" type="file" wire:model="announcementFile"
                            accept="image/*,application/pdf"
                            class="block w-full text-sm text-gray-500 cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-400 mt-1">Image (JPG/PNG/GIF/WebP) or PDF · max 5MB</p>

                        <div wire:loading wire:target="announcementFile" class="text-xs text-blue-600 mt-2">Uploading...</div>

                        {{-- Pending upload — small chip --}}
                        @if ($announcementFile)
                            @php
                                $pendingExt  = strtolower($announcementFile->getClientOriginalExtension());
                                $pendingMime = (string) $announcementFile->getMimeType();
                                $pendingIsPdf = $pendingExt === 'pdf' || $pendingMime === 'application/pdf';
                            @endphp
                            <div wire:loading.remove wire:target="announcementFile"
                                class="mt-2 inline-flex items-center gap-1.5 pl-2.5 pr-1 py-1 rounded-full border text-xs font-medium
                                       {{ $pendingIsPdf ? 'bg-red-50 border-red-200 text-red-700' : 'bg-blue-50 border-blue-200 text-blue-700' }}">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    @if ($pendingIsPdf)
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    @endif
                                </svg>
                                <span class="max-w-[180px] truncate">{{ $announcementFile->getClientOriginalName() }}</span>
                                <button type="button" wire:click="$set('announcementFile', null)" title="Remove"
                                    class="w-4 h-4 flex items-center justify-center rounded-full hover:bg-white/70 flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                        @error('announcementFile')
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
                    <button type="button" wire:click="save" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md
                               transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="save">
                            {{ $editId ? 'Update' : 'Create Announcement' }}
                        </span>
                        <span wire:loading wire:target="save" class="flex items-center gap-1.5">
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
         VIEW SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($viewModal && $selectedAnnouncement)
        @php
            $typeColors = [
                'all'     => 'bg-purple-100 text-purple-700',
                'user'    => 'bg-emerald-100 text-emerald-700',
                'teacher' => 'bg-orange-100 text-orange-700',
            ];
            $typeDot = [
                'all'     => 'bg-purple-500',
                'user'    => 'bg-emerald-500',
                'teacher' => 'bg-orange-500',
            ];
        @endphp
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>

            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="block w-2 h-2 rounded-full flex-shrink-0 {{ $typeDot[$selectedAnnouncement->type] ?? 'bg-gray-400' }}"></span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 truncate">Announcement Details</h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $selectedAnnouncement->created_at->format('d M Y · g:i A') }}
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

                {{-- Panel Body — simple label/value rows (exam-style) --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Title</span>
                        <span class="col-span-2 text-gray-900 font-semibold">{{ $selectedAnnouncement->announcement_name }}</span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Audience</span>
                        <span class="col-span-2">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $typeColors[$selectedAnnouncement->type] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $selectedAnnouncement->type === 'user' ? 'Students' : ucfirst($selectedAnnouncement->type) }}
                            </span>
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Content</span>
                        <span class="col-span-2 text-gray-800 whitespace-pre-line leading-relaxed">{{ $selectedAnnouncement->announcement_content }}</span>
                    </div>

                    @if ($selectedAnnouncement->announcement_image || $selectedAnnouncement->announcement_pdf)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Attachments</span>
                            <span class="col-span-2 flex flex-wrap gap-2">
                                @if ($selectedAnnouncement->announcement_image)
                                    <a href="{{ $selectedAnnouncement->announcement_image }}" target="_blank" rel="noopener" title="Open image"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-blue-200 bg-blue-50 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Image
                                    </a>
                                @endif
                                @if ($selectedAnnouncement->announcement_pdf)
                                    <a href="{{ $selectedAnnouncement->announcement_pdf }}" target="_blank" rel="noopener" title="Open PDF"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-red-200 bg-red-50 text-xs font-medium text-red-700 hover:bg-red-100">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        PDF
                                    </a>
                                @endif
                            </span>
                        </div>
                    @endif

                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Posted By</span>
                        <span class="col-span-2 text-gray-800">{{ $selectedAnnouncement->user->name }}</span>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Posted</span>
                        <span class="col-span-2 text-gray-800">{{ $selectedAnnouncement->created_at->format('d M Y · g:i A') }}</span>
                    </div>
                </div>

                {{-- Panel Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button type="button" wire:click="editFromView({{ $selectedAnnouncement->id }})"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700
                               hover:bg-gray-100 rounded-md transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </button>
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
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete announcement?</h3>
                        <p class="text-sm text-gray-500">This action cannot be undone. The announcement and all attachments will be permanently removed.</p>
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
