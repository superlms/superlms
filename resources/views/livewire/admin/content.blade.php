<div class="min-h-screen bg-gray-50" x-data="{ expandedChapters: @entangle('expandedChapters').live }">

{{-- ══════════════════════════════════════════════════
     HEADER + FILTER BAR (exams-style)
══════════════════════════════════════════════════ --}}
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="px-4 sm:px-6 py-3 sm:py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Content Management</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage chapter and topic content</p>
            </div>
            <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                <span class="pr-4">Chapters: <strong class="text-blue-600">{{ $totalChapters }}</strong></span>
                <span class="px-4">Topics: <strong class="text-emerald-600">{{ $totalTopics }}</strong></span>
                <span class="pl-4">With Content: <strong class="text-purple-600">{{ $withContent }}</strong></span>
            </div>
        </div>
        <div class="flex lg:hidden items-center gap-3 text-xs text-gray-500 mt-3 flex-wrap">
            <span>Chapters: <strong class="text-blue-600">{{ $totalChapters }}</strong></span>
            <span>Topics: <strong class="text-emerald-600">{{ $totalTopics }}</strong></span>
            <span>With Content: <strong class="text-purple-600">{{ $withContent }}</strong></span>
        </div>
    </div>

    {{-- Filter bar (exams-style) --}}
    <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter by:
            </div>
            <select wire:model.live="filterStandard"
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                <option value="">Select Class</option>
                @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
            </select>
            <select wire:model.live="filterSection" @disabled(!$filterStandard)
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[140px]">
                <option value="">Section (optional)</option>
                @foreach ($filterSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
            </select>
            <select wire:model.live="filterSubject" @disabled(!$filterStandard)
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[140px]">
                <option value="">Select Subject</option>
                @foreach ($filterSubjects as $subj)<option value="{{ $subj->id }}">{{ $subj->name }}</option>@endforeach
            </select>

            @if ($filterStandard || $filterSection || $filterSubject)
                <button wire:click="clearFilters"
                    class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear
                </button>
            @endif
        </div>
    </div>
</div>

<div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

@if (!$showList)
    {{-- Prompt to select filters --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
        <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        </div>
        <p class="text-sm text-gray-600 font-medium">Pick a Class and Subject to manage content.</p>
        <p class="text-xs text-gray-400 mt-1">Section filter is optional.</p>
    </div>
@else
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-200">
            @forelse ($chapters as $chapter)
                @php $chHasContent = !empty($chapter->description) || !empty($chapter->file_path) || !empty($chapter->image_path) || !empty($chapter->pdf_path); @endphp
                <div>
                    {{-- Chapter Row --}}
                    <div class="px-4 sm:px-6 py-3 flex items-center justify-between hover:bg-gray-50 transition cursor-pointer"
                        @click="expandedChapters.includes({{ $chapter->id }}) ? expandedChapters = expandedChapters.filter(i => i !== {{ $chapter->id }}) : expandedChapters = [...expandedChapters, {{ $chapter->id }}]">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <svg class="w-4 h-4 text-gray-500 transition-transform duration-200 flex-shrink-0"
                                :class="{ 'rotate-90': expandedChapters.includes({{ $chapter->id }}) }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="text-xs font-medium text-gray-500 px-1.5 py-0.5 bg-blue-100 rounded flex-shrink-0">Ch {{ $chapter->order }}</span>
                            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $chapter->name }}</h3>
                            <span class="text-xs text-gray-400 flex-shrink-0">{{ $chapter->topics->count() }} topics</span>
                        </div>

                        {{-- Chapter action: one icon — Add when empty, Edit once content exists --}}
                        <div class="flex items-center gap-1 ml-2 flex-shrink-0" @click.stop>
                            @if ($chHasContent)
                                <button wire:click="onViewContent('chapter', {{ $chapter->id }})"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg border border-blue-200 transition-colors" title="View Content">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <button wire:click="onEditContent('chapter', {{ $chapter->id }})"
                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg border border-emerald-200 transition-colors" title="Edit Content">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                            @else
                                <button wire:click="onAddContent('chapter', {{ $chapter->id }})"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg border border-blue-200 transition-colors" title="Add Content">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Topics --}}
                    <div x-show="expandedChapters.includes({{ $chapter->id }})" x-collapse class="bg-gray-50">
                        @forelse ($chapter->topics as $topic)
                            @php $tpHasContent = !empty($topic->topic_content) || !empty($topic->image_path) || !empty($topic->pdf_path); @endphp
                            <div class="px-8 sm:px-12 py-2.5 flex items-center justify-between hover:bg-white border-b border-gray-100 last:border-b-0 transition">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full flex-shrink-0"></span>
                                    <span class="text-sm text-gray-800 truncate">{{ $topic->topic_name }}</span>
                                </div>
                                <div class="flex items-center gap-1 ml-2 flex-shrink-0">
                                    @if ($tpHasContent)
                                        <button wire:click="onViewContent('topic', {{ $topic->id }})"
                                            class="p-1 text-blue-600 hover:bg-blue-50 rounded-lg border border-blue-200 transition-colors" title="View Content">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button wire:click="onEditContent('topic', {{ $topic->id }})"
                                            class="p-1 text-emerald-600 hover:bg-emerald-50 rounded-lg border border-emerald-200 transition-colors" title="Edit Content">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                    @else
                                        <button wire:click="onAddContent('topic', {{ $topic->id }})"
                                            class="p-1 text-emerald-600 hover:bg-emerald-50 rounded-lg border border-emerald-200 transition-colors" title="Add Content">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="px-12 py-4 text-center text-xs text-gray-400">No topics in this chapter</div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <p class="text-gray-500 text-sm">No chapters found for this selection</p>
                </div>
            @endforelse
        </div>
    </div>
@endif
</div>

{{-- ═══════════════════════════════════════════════════
     ADD / EDIT CONTENT SLIDE-IN
═══════════════════════════════════════════════════ --}}
@if ($openContentModal)
<div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeContentModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $contentEditMode ? 'Edit Content' : 'Add Content' }}</h2>
                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $contentTargetName }}</p>
            </div>
            <button wire:click="closeContentModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

            {{-- Content Type Selection --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Content Type</label>
                <div class="flex flex-wrap gap-2">
                    @foreach (['all' => 'All', 'text' => 'Text', 'url' => 'URL', 'image' => 'Image', 'pdf' => 'PDF'] as $val => $label)
                        <button type="button" wire:click="$set('contentType', '{{ $val }}')"
                            class="px-4 py-2 text-sm font-medium rounded-lg border transition-colors
                                {{ $contentType === $val ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Text --}}
            @if ($contentType === 'text' || $contentType === 'all')
                <div x-data="{ len: $wire.contentText ? $wire.contentText.length : 0 }">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Content Text</label>
                    <textarea wire:model="contentText" maxlength="10000" x-on:input="len = $event.target.value.length"
                        rows="{{ $contentType === 'all' ? 4 : 6 }}" placeholder="Enter content text..."
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500"></textarea>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-gray-400">Max 10,000 characters</span>
                        <span class="text-xs text-gray-400"><span x-text="len"></span>/10000</span>
                    </div>
                    @error('contentText')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            @endif

            {{-- URL --}}
            @if ($contentType === 'url' || $contentType === 'all')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">URL</label>
                    <input type="url" wire:model="contentUrl" placeholder="https://example.com/resource"
                        class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Enter a video link, external resource, or any URL</p>
                    @error('contentUrl')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            @endif

            {{-- Image --}}
            @if ($contentType === 'image' || $contentType === 'all')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Upload Image</label>
                    @if ($existingImage && !$contentImage)
                        <div class="flex items-center gap-3 mb-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                            <img src="{{ $existingImage }}" class="h-16 w-16 rounded object-cover border border-gray-200">
                            <span class="text-xs text-gray-600">Current image</span>
                        </div>
                    @endif
                    <input type="file" wire:model="contentImage" accept="image/*"
                        class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                            file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-400 mt-1">Max: 500KB (JPG, PNG, GIF)</p>
                    @error('contentImage')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            @endif

            {{-- PDF --}}
            @if ($contentType === 'pdf' || $contentType === 'all')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Upload PDF</label>
                    @if ($existingPdf && !$contentPdf)
                        <div class="flex items-center gap-3 mb-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                            <svg class="h-10 w-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span class="text-xs text-gray-600">Current PDF</span>
                        </div>
                    @endif
                    <input type="file" wire:model="contentPdf" accept="application/pdf"
                        class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                            file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                    <p class="text-xs text-gray-400 mt-1">Max: 5MB</p>
                    @error('contentPdf')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            @endif
        </div>

        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeContentModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="onSaveContent" wire:loading.attr="disabled"
                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                <span wire:loading.remove wire:target="onSaveContent">{{ $contentEditMode ? 'Update Content' : 'Save Content' }}</span>
                <span wire:loading wire:target="onSaveContent">Saving…</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════
     VIEW CONTENT SLIDE-IN
═══════════════════════════════════════════════════ --}}
@if ($showViewModal)
<div class="fixed inset-0 z-50 overflow-hidden">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">View Content</h2>
                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $viewContentTitle }}</p>
            </div>
            <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6">
            @if (!empty($viewContentData))
                <div class="space-y-4">
                    @if (!empty($viewContentData['text']))
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Text Content</p>
                            <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ $viewContentData['text'] }}</p>
                        </div>
                    @endif

                    @if (!empty($viewContentData['url']))
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">URL</p>
                            <a href="{{ $viewContentData['url'] }}" target="_blank"
                                class="text-sm text-blue-600 hover:text-blue-800 underline break-all">
                                {{ $viewContentData['url'] }}
                            </a>
                        </div>
                    @endif

                    @if (!empty($viewContentData['image']))
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Image</p>
                            <img src="{{ $viewContentData['image'] }}" alt="Content Image" class="max-w-full rounded-lg border border-gray-200">
                        </div>
                    @endif

                    @if (!empty($viewContentData['pdf']))
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">PDF</p>
                            <iframe src="{{ $viewContentData['pdf'] }}" class="w-full h-96 rounded-lg border border-gray-200"></iframe>
                            <a href="{{ $viewContentData['pdf'] }}" target="_blank"
                                class="inline-flex items-center gap-1 mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Download PDF
                            </a>
                        </div>
                    @endif

                    @if (empty($viewContentData['text']) && empty($viewContentData['url']) && empty($viewContentData['image']) && empty($viewContentData['pdf']))
                        <div class="text-center py-8 text-sm text-gray-400">No content available</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            <button wire:click="closeViewModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Close</button>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════
     DELETE CONFIRM OVERLAY (custom, no WireUI dialog)
═══════════════════════════════════════════════════ --}}
@if ($showDeleteConfirm)
<div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Remove content?</h3>
                <p class="text-sm text-gray-500">
                    This will remove all content from <strong>{{ $deleteTargetName }}</strong>.
                    The {{ $deleteTargetType }} itself will remain. This cannot be undone.
                </p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 mt-5">
            <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
            <button wire:click="confirmDelete" wire:loading.attr="disabled"
                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                <span wire:loading.remove wire:target="confirmDelete">Remove</span>
                <span wire:loading wire:target="confirmDelete">Removing…</span>
            </button>
        </div>
    </div>
</div>
@endif

</div>
