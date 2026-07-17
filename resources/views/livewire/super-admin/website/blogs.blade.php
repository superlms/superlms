<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════ HEADER ══════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('website-image/Group 11525.png') }}" alt="SUPERLMS"
                        class="w-11 h-11 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">Blogs</h1>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">Write and manage the articles shown on the website.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Add Blog
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Filter bar (FAQ-style sub-header) ── --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <div class="relative">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by heading…"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-64 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <select wire:model.live="dateFilter"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All time</option>
                    <option value="7">Last 7 days</option>
                    <option value="15">Last 15 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="60">Last 60 days</option>
                </select>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 space-y-5">

        {{-- ── Blog cards (same look as website) ── --}}
        @if ($blogs->isEmpty())
            <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center shadow-sm">
                <div class="text-4xl mb-3">📝</div>
                <h3 class="text-base font-semibold text-gray-800">No blogs found</h3>
                <p class="text-sm text-gray-500 mt-1">Click <span class="font-semibold text-indigo-600">Add Blog</span> to publish your first article.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach ($blogs as $blog)
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow flex flex-col" wire:key="blog-{{ $blog->id }}">
                        <div class="h-28 bg-gradient-to-br from-pink-400 via-fuchsia-500 to-indigo-500 flex items-center justify-center text-3xl overflow-hidden">
                            @if ($blog->cover_image)
                                <img src="{{ $blog->cover_image }}" alt="{{ $blog->title }}" class="w-full h-full object-cover">
                            @else
                                📝
                            @endif
                        </div>
                        <div class="p-3.5 flex flex-col flex-1">
                            @if ($blog->category)
                                <div class="text-[10px] font-bold tracking-wide uppercase text-indigo-600 mb-1">{{ $blog->category }}</div>
                            @endif
                            <h3 class="text-sm font-semibold text-gray-900 leading-snug line-clamp-2">{{ $blog->title }}</h3>
                            @if ($blog->heading)
                                <p class="text-xs font-medium text-gray-600 mt-1 line-clamp-1">{{ $blog->heading }}</p>
                            @endif
                            <p class="text-xs text-gray-500 mt-1.5 leading-relaxed flex-1 line-clamp-3">{{ $blog->excerpt }}</p>
                            <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-gray-100">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-[11px] text-gray-400">{{ $blog->created_at?->format('d M Y') }}</span>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-500" title="{{ number_format($blog->views) }} views">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        {{ number_format($blog->views) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button wire:click="openEdit({{ $blog->id }})"
                                        class="px-2 py-1 text-[11px] font-medium text-indigo-600 border border-indigo-200 rounded-md hover:bg-indigo-50 transition-colors">Edit</button>
                                    <button wire:click="confirmDelete({{ $blog->id }})"
                                        class="px-2 py-1 text-[11px] font-medium text-red-600 border border-red-200 rounded-md hover:bg-red-50 transition-colors">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div>{{ $blogs->links() }}</div>
        @endif
    </div>

    {{-- ══════════════════ SLIDE-IN PANEL ══════════════════ --}}
    @if ($showPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Blog' : 'Add New Blog' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">This appears on the public Blogs page.</p>
                    </div>
                    <button wire:click="closePanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                    {{-- Cover image --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cover Image</label>
                        <div class="flex items-center gap-4">
                            <div class="w-28 h-20 rounded-lg border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center flex-shrink-0">
                                @if ($coverImage)
                                    <img src="{{ $coverImage->temporaryUrl() }}" class="w-full h-full object-cover" alt="">
                                @elseif ($coverImageUrl)
                                    <img src="{{ $coverImageUrl }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                @endif
                            </div>
                            <div>
                                <input type="file" wire:model="coverImage" accept="image/*"
                                    class="block text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-xs file:font-medium hover:file:bg-indigo-100" />
                                <div wire:loading wire:target="coverImage" class="text-xs text-gray-400 mt-1">Uploading…</div>
                                <p class="text-xs text-gray-400 mt-1">JPG / PNG, up to 4 MB.</p>
                                @error('coverImage') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <input type="text" wire:model="category" placeholder="e.g. School Tech"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        @error('category') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="title" placeholder="5 ways an LMS saves your school hours every week"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        @error('title') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Heading --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Heading / Subtitle</label>
                        <input type="text" wire:model="heading" placeholder="A short supporting line shown under the title"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        @error('heading') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea wire:model="description" rows="10" placeholder="Write the full article here…"
                            id="blogDescription"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 resize-y leading-relaxed"></textarea>
                        <div class="flex items-center justify-between gap-2 mt-1.5">
                            <p class="text-xs text-gray-400">
                                Tip: select some words and click <span class="font-semibold text-indigo-600">Add link</span> to hyperlink them.
                                Pasted <span class="font-mono">https://…</span> URLs also become clickable automatically.
                            </p>
                            <button type="button" onclick="blogInsertLink()"
                                class="inline-flex items-center gap-1 flex-shrink-0 px-2.5 py-1 text-[11px] font-medium text-indigo-600 border border-indigo-200 rounded-md hover:bg-indigo-50 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m8.656-2.828a4 4 0 00-5.656 0l-3 3" /></svg>
                                Add link
                            </button>
                        </div>
                        @error('description') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-100 flex items-center justify-end gap-3 flex-shrink-0">
                    <button wire:click="closePanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                    <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save,coverImage"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2 rounded-lg">
                        <span wire:loading.remove wire:target="save">{{ $editId ? 'Update Blog' : 'Publish Blog' }}</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════ DELETE CONFIRM ══════════════════ --}}
    @if ($pendingDelete !== null)
        <div class="fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-red-50 flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Delete Blog</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600">Are you sure you want to delete this blog post? This also removes its cover image and cannot be undone.</p>
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="deleteBlog"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">Yes, Delete</button>
                    <button wire:click="cancelDelete"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Insert a Markdown link around the selected text in the description box. --}}
    <script>
        window.blogInsertLink = function () {
            var ta = document.getElementById('blogDescription');
            if (!ta) return;

            var start    = ta.selectionStart;
            var end       = ta.selectionEnd;
            var selected  = ta.value.substring(start, end).trim();

            var label = selected || window.prompt('Text to show for the link:', '');
            if (!label) return;

            var url = window.prompt('Link URL (must start with https://):', 'https://');
            if (!url) return;
            if (!/^https?:\/\//i.test(url)) url = 'https://' + url.replace(/^\/+/, '');

            var markdown = '[' + label + '](' + url + ')';
            var before   = ta.value.substring(0, start);
            var after    = ta.value.substring(end);
            ta.value = before + markdown + after;

            // Put the caret after the inserted link and let Livewire capture the change.
            var pos = (before + markdown).length;
            ta.focus();
            ta.setSelectionRange(pos, pos);
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        };
    </script>
</div>
