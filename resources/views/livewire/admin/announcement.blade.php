<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, with inline analytics)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Announcements</h1>
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
                                       {{ $dateFilter == $opt[0] && !$specificDate
                                           ? 'bg-blue-600 text-white'
                                           : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                                {{ $opt[1] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <span class="hidden sm:inline-block w-px h-5 bg-gray-200"></span>

                {{-- Specific date filter --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 hidden sm:inline">Date:</span>
                    <input type="date" wire:model.live="specificDate" max="{{ now()->format('Y-m-d') }}"
                        class="px-2.5 py-1 text-xs border border-gray-200 rounded-md bg-white text-gray-700
                               focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
                    @if ($specificDate)
                        <button wire:click="clearDate" title="Clear date"
                            class="inline-flex items-center justify-center w-6 h-6 rounded-md border border-gray-200 bg-white text-gray-500 hover:bg-gray-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
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
        {{-- The list, in the Students list's style: one table — S.No, the
             announcement (its icon, the title, and under it the content on one
             line, cut short with … when it is long) and the actions. Who it is
             for, who posted it, when, and its attachment are on its View. --}}
        @if ($announcements->count())
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Announcement</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($announcements as $index => $announcement)
                                <tr wire:click="viewAnnouncement({{ $announcement->id }})"
                                    class="hover:bg-gray-50/70 transition-colors cursor-pointer">

                                    {{-- S.No --}}
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-500 font-medium">{{ $announcements->firstItem() + $index }}</span>
                                    </td>

                                    {{-- Icon, title, and the content on one line --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $announcement->announcement_name }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $announcement->announcement_content }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-3" onclick="event.stopPropagation()">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="viewAnnouncement({{ $announcement->id }})" title="View"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button wire:click="edit({{ $announcement->id }})" title="Edit"
                                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button wire:click="onDelete({{ $announcement->id }})" title="Delete"
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
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
        @endif

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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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
                    <div x-data="{ len: @js(mb_strlen($announcementName ?? '')) }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-700">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <span class="text-xs text-gray-400"><span x-text="len">0</span>/1000</span>
                        </div>
                        <input wire:model.defer="announcementName" type="text" maxlength="1000" placeholder="Announcement title"
                            x-on:input="len = $event.target.value.length"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors
                                   placeholder:text-gray-400" />
                        @error('announcementName')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Content --}}
                    <div x-data="{ len: @js(mb_strlen($announcementContent ?? '')) }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-700">
                                Content <span class="text-red-500">*</span>
                            </label>
                            <span class="text-xs text-gray-400"><span x-text="len">0</span>/3000</span>
                        </div>
                        <textarea wire:model.defer="announcementContent" rows="6" maxlength="3000" placeholder="Write your announcement content here..."
                            x-on:input="len = $event.target.value.length"
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
                                    {{-- Live, so the class picker can appear the
                                         moment Students is chosen. --}}
                                    <input type="radio" wire:model.live="type" value="{{ $value }}" class="peer sr-only">
                                    <div class="px-3 py-2.5 text-center text-sm font-medium border-2 rounded-md transition-all border-gray-200 text-gray-600 hover:bg-gray-50 {{ $opt['classes'] }}">
                                        {{ $opt['label'] }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('type')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        {{-- Students only: all of them, or just one class. --}}
                        @if ($type === 'user')
                            <div class="mt-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Which students</label>
                                <select wire:model.live="standardId"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">All Classes</option>
                                    @foreach ($standards as $std)
                                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1.5 text-xs text-gray-500">
                                    {{ $standardId
                                        ? 'Only students of ' . (optional($standards->firstWhere('id', (int) $standardId))->name ?? 'this class') . ' will see it.'
                                        : 'Every student in the school will see it.' }}
                                </p>
                                @error('standardId')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    </div>

                    {{-- ── Unified attachment uploader — Image OR PDF ── --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Attachment <span class="font-normal text-gray-400">(Optional · Image or PDF, max 1 MB)</span>
                        </label>

                        {{-- Existing attachments when editing (click to open, x to remove) --}}
                        @if ($editId && !$announcementFile)
                            @php $ann = \App\Models\Admin\Announcement::find($editId) @endphp
                            @if ($ann && ($ann->announcement_image || $ann->announcement_pdf))
                                <div class="mb-2 space-y-1.5">
                                    @foreach ([['image', $ann->announcement_image, 'Image', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'], ['pdf', $ann->announcement_pdf, 'PDF', 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z']] as [$kind, $fileUrl, $fileLabel, $fileIcon])
                                        @if ($fileUrl)
                                            <div class="flex items-center gap-2 text-sm text-gray-700">
                                                <a href="{{ $fileUrl }}" target="_blank" rel="noopener" title="Open {{ $fileLabel }}"
                                                    class="inline-flex items-center gap-2 min-w-0 hover:underline">
                                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $fileIcon }}" />
                                                    </svg>
                                                    <span class="truncate">{{ $fileLabel }} attached</span>
                                                </a>
                                                <button type="button" wire:click="deleteFile('{{ $kind }}')" title="Remove {{ $fileLabel }}"
                                                    class="w-5 h-5 flex items-center justify-center rounded text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        {{-- The picker: a plain button, and beside it the name of the file
                             picked — once, on one line, with an x to take it off. The
                             browser's own file field (which repeats the name) is hidden. --}}
                        <div class="flex items-center gap-3 min-w-0">
                            <label for="annFileInput"
                                class="inline-flex items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer flex-shrink-0">
                                Choose File
                            </label>
                            <input id="annFileInput" type="file" wire:model="announcementFile"
                                accept="image/*,application/pdf" class="sr-only">

                            <span wire:loading wire:target="announcementFile" class="text-sm text-gray-500">Uploading...</span>

                            @if ($announcementFile)
                                @php
                                    $pendingExt  = strtolower($announcementFile->getClientOriginalExtension());
                                    $pendingMime = (string) $announcementFile->getMimeType();
                                    $pendingIsPdf = $pendingExt === 'pdf' || $pendingMime === 'application/pdf';
                                @endphp
                                {{-- wire:loading.remove sets a display of its own on its element, which
                                     undoes a flex row — so it sits on this wrapper, not on the row. --}}
                                <div wire:loading.remove wire:target="announcementFile" class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 min-w-0 text-sm text-gray-700">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $pendingIsPdf ? 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z' : 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' }}" />
                                    </svg>
                                    <span class="truncate" title="{{ $announcementFile->getClientOriginalName() }}">{{ $announcementFile->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="$set('announcementFile', null)" title="Remove"
                                        class="w-5 h-5 flex items-center justify-center rounded text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                </div>
                            @else
                                <span wire:loading.remove wire:target="announcementFile" class="text-sm text-gray-400">No file chosen</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-1.5">Image (JPG/PNG/GIF/WebP) or PDF · max 1 MB</p>
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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

                {{-- Panel Body — one plain card: the title, the content, who posted it,
                     when, and who it is for; under it the attachment, which opens on a click. --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @php
                        $viewAudience = match ($selectedAnnouncement->type) {
                            'user'    => 'Students · ' . ($selectedAnnouncement->standard?->name ?? 'All classes'),
                            'teacher' => 'Teachers',
                            default   => ucfirst((string) $selectedAnnouncement->type),
                        };
                    @endphp
                    <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Title</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 break-words">{{ $selectedAnnouncement->announcement_name }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Content</p>
                            <p class="mt-1 text-sm text-gray-800 whitespace-pre-line leading-relaxed break-words">{{ $selectedAnnouncement->announcement_content }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Posted By</p>
                            <p class="mt-1 text-sm text-gray-800">{{ $selectedAnnouncement->user->name }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Posted</p>
                            <p class="mt-1 text-sm text-gray-800">{{ $selectedAnnouncement->created_at->format('d M Y · g:i A') }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Audience</p>
                            <p class="mt-1 text-sm text-gray-800">{{ $viewAudience }}</p>
                        </div>
                    </div>

                    @if ($selectedAnnouncement->announcement_image || $selectedAnnouncement->announcement_pdf)
                        <div class="rounded-xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
                            @foreach ([[$selectedAnnouncement->announcement_image, 'Image', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'], [$selectedAnnouncement->announcement_pdf, 'PDF', 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z']] as [$fileUrl, $fileLabel, $fileIcon])
                                @if ($fileUrl)
                                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener" title="Open {{ $fileLabel }}"
                                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $fileIcon }}" />
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Attachment</p>
                                            <p class="mt-0.5 text-sm font-medium text-gray-800">{{ $fileLabel }}</p>
                                        </div>
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
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
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
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
