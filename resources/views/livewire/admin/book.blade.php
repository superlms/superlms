<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, analytics + Add button)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Books</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Manage library books — covers, PDFs, and class assignments</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalBooks }}</strong></span>
                        <span class="px-4">Active: <strong class="text-emerald-600">{{ $activeBooks }}</strong></span>
                        <span class="px-4">Inactive: <strong class="text-gray-500">{{ $inactiveBooks }}</strong></span>
                        <span class="pl-4">With PDF: <strong class="text-blue-600">{{ $withPdfCount }}</strong></span>
                    </div>
                    <button wire:click="onAddBook"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Add Book</span>
                        <span class="sm:hidden">New</span>
                    </button>
                </div>
            </div>

            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $totalBooks }}</strong></span>
                <span>Active: <strong class="text-emerald-600">{{ $activeBooks }}</strong></span>
                <span>Inactive: <strong class="text-gray-500">{{ $inactiveBooks }}</strong></span>
                <span>With PDF: <strong class="text-blue-600">{{ $withPdfCount }}</strong></span>
            </div>
        </div>

        {{-- Filter bar (clean, minimal) --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">Filter:</div>

                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Search title, class, subject..."
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                <select wire:model.live="filterStandard"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Classes</option>
                    @foreach ($standards as $std)
                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterSection" @disabled(!$filterStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                    <option value="">All Sections</option>
                    @foreach ($filterSections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterSubject" @disabled(!$filterStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                    <option value="">All Subjects</option>
                    @foreach ($filterSubjects as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterStatus" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         BOOK GRID — clean cards
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        @if (!$filterStandard)
            <div class="bg-white border border-gray-200 text-center py-20 px-4">
                <div class="w-12 h-12 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <p class="text-base font-semibold text-gray-800">Select a class</p>
                <p class="text-sm text-gray-500 mt-1">Pick a class from the filter above to view its books.</p>
            </div>
        @elseif ($books->isEmpty())
            <div class="bg-white border border-gray-200 text-center py-20 px-4">
                <div class="w-12 h-12 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <p class="text-base font-semibold text-gray-800">No books found</p>
                <p class="text-sm text-gray-400 mt-1">Click "Add Book" to add the first book for this class.</p>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                @foreach ($books as $book)
                    <div class="group bg-white rounded-lg border border-gray-200 hover:border-blue-200 hover:shadow-lg transition-all duration-200 overflow-hidden flex flex-col">

                        {{-- Cover (book-shaped 3:4 aspect, full bleed top) --}}
                        <div class="relative aspect-[3/4] bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                            @if ($book->book_logo)
                                <img src="{{ $book->book_logo }}" alt="{{ $book->title }}"
                                    class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 p-4">
                                    <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span class="text-xs">No Cover</span>
                                </div>
                            @endif

                            {{-- Status badge (top-right) --}}
                            <span class="absolute top-2 right-2 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide shadow-sm
                                {{ $book->is_active ? 'bg-emerald-500 text-white' : 'bg-gray-400 text-white' }}">
                                {{ $book->is_active ? 'Active' : 'Inactive' }}
                            </span>

                            {{-- PDF badge (top-left) --}}
                            @if ($book->pdf_file)
                                <span class="absolute top-2 left-2 inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-500 text-white shadow-sm">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    PDF
                                </span>
                            @endif
                        </div>

                        {{-- Body --}}
                        <div class="p-2.5 flex-1 flex flex-col border-t border-gray-100">
                            <h3 class="text-xs font-semibold text-gray-900 line-clamp-2 mb-1 group-hover:text-blue-700 transition-colors" title="{{ $book->title }}">
                                {{ $book->title }}
                            </h3>

                            <div class="space-y-0.5 mb-2 text-[11px] text-gray-500 flex-1">
                                <p class="flex items-center gap-1.5 truncate">
                                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <span class="truncate">{{ $book->standard->name ?? '—' }}{{ $book->section ? ' · ' . $book->section->name : '' }}</span>
                                </p>
                                <p class="flex items-center gap-1.5 truncate">
                                    <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                    </svg>
                                    <span class="truncate">{{ $book->subject->name ?? '—' }}</span>
                                </p>
                            </div>

                            {{-- Action bar --}}
                            <div class="flex items-center justify-between gap-0.5 pt-2 border-t border-gray-100">
                                @if ($book->pdf_file)
                                    <a href="{{ $book->pdf_file }}" target="_blank" rel="noopener" title="View in new tab"
                                        class="p-1 rounded-md text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <button wire:click="downloadBook({{ $book->id }})" title="Download PDF"
                                        class="p-1 rounded-md text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                    </button>
                                @else
                                    <span class="text-[10px] text-gray-300 italic px-1">No PDF</span>
                                @endif
                                <button wire:click="onEditBook({{ $book->id }})" title="Edit"
                                    class="p-1 rounded-md text-gray-500 hover:bg-amber-50 hover:text-amber-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button wire:click="onDeleteBook({{ $book->id }})" title="Delete"
                                    class="p-1 rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($books->hasPages())
                <div class="mt-6">{{ $books->links() }}</div>
            @endif
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT BOOK SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($open)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Book' : 'New Book' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Cover image + PDF + class assignment</p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-6 space-y-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Book Title <span class="text-red-500">*</span> <span class="text-gray-400 font-normal">(max 100 characters)</span></label>
                        <input wire:model.defer="title" type="text" maxlength="100" placeholder="e.g. NCERT Mathematics — Class 8"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('title')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="standard_id" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Class</option>
                                @foreach ($standards as $std)
                                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                                @endforeach
                            </select>
                            @error('standard_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section <span class="text-gray-400 font-normal">(Optional)</span></label>
                            <select wire:model.live="section_id" @disabled(!$standard_id) class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                                <option value="">Select Section</option>
                                @foreach ($sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject <span class="text-red-500">*</span></label>
                        <select wire:model.defer="subject_id" @disabled(!$standard_id) class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm disabled:opacity-50">
                            <option value="">Select Subject</option>
                            @foreach ($subjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                            @endforeach
                        </select>
                        @error('subject_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    @php $existingBook = $editId ? \App\Models\Admin\Book::find($editId) : null; @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- Cover Image (student-style inline: thumb + file input) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Cover Image <span class="text-gray-400 font-normal">(Optional, max 1 MB)</span></label>
                            <div class="flex items-center gap-3 min-w-0">
                                @if ($tempLogoUrl)
                                    <img src="{{ $tempLogoUrl }}" class="w-12 h-16 rounded object-cover border border-gray-200 flex-shrink-0">
                                @elseif ($existingBook && $existingBook->book_logo)
                                    <img src="{{ $existingBook->book_logo }}" class="w-12 h-16 rounded object-cover border border-gray-200 flex-shrink-0">
                                @else
                                    <div class="w-12 h-16 rounded bg-blue-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>
                                @endif
                                <input type="file" wire:model="book_logo" accept="image/*" class="flex-1 min-w-0 w-full text-sm">
                            </div>
                            <div wire:loading wire:target="book_logo" class="text-xs text-blue-600 mt-1">Uploading…</div>
                            @error('book_logo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        {{-- PDF (student-style inline: icon + file input) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">PDF <span class="text-gray-400 font-normal">(Optional, max 5 MB)</span></label>
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 rounded bg-red-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input type="file" wire:model="pdf_file" accept=".pdf" class="flex-1 min-w-0 w-full text-sm">
                            </div>
                            @if ($tempPdfUrl)
                                <p class="text-xs text-gray-600 mt-1 truncate">Selected: {{ $tempPdfUrl }}</p>
                            @elseif ($existingBook && $existingBook->pdf_file)
                                <p class="text-xs text-gray-500 mt-1">Current PDF attached — upload to replace</p>
                            @endif
                            <div wire:loading wire:target="pdf_file" class="text-xs text-blue-600 mt-1">Uploading…</div>
                            @error('pdf_file')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.defer="is_active" class="rounded">
                        <span class="text-sm text-gray-700">Active (visible to students)</span>
                    </label>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="onSave" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="onSave">{{ $editId ? 'Update Book' : 'Add Book' }}</span>
                        <span wire:loading wire:target="onSave">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW BOOK SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showViewModal && $viewBook)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewModalTitle }}</h2>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                    <div class="flex justify-center">
                        @if ($viewBook->book_logo)
                            <img src="{{ $viewBook->book_logo }}" class="h-48 w-36 object-cover rounded-lg border border-gray-200 shadow-md">
                        @else
                            <div class="h-48 w-36 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                            </div>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900 text-center mb-1">{{ $viewBook->title }}</h3>
                        <p class="text-xs text-center">
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide
                                {{ $viewBook->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                                {{ $viewBook->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                    </div>

                    <div class="border-t border-gray-100 pt-5 grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Class</p>
                            <p class="text-sm text-gray-800">{{ $viewBook->standard->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Section</p>
                            <p class="text-sm text-gray-800">{{ $viewBook->section->name ?? '—' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Subject</p>
                            <p class="text-sm text-gray-800">{{ $viewBook->subject->name ?? '—' }}</p>
                        </div>
                    </div>

                    @if ($viewBook->pdf_file)
                        <div class="border-t border-gray-100 pt-5">
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">PDF Document</p>
                            <a href="{{ $viewBook->pdf_file }}" target="_blank" rel="noopener"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-md hover:bg-red-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                Open PDF (new tab)
                            </a>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button wire:click="onEditBook({{ $viewBook->id }})"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Edit
                    </button>
                    <button wire:click="closeViewModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete book?</h3>
                        <p class="text-sm text-gray-500">The book record, cover image, and PDF will be permanently removed.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="confirmDelete" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="confirmDelete">Delete</span>
                        <span wire:loading wire:target="confirmDelete">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
