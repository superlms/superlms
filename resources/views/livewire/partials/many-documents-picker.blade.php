{{-- Add Doc's files (App\Livewire\Concerns\PicksManyDocuments): pick or drop
     one file or several at once; they go up one after another — Livewire's
     S3 temporary uploads take one at a time — and each is listed with a
     title made from its name, to change before saving. A file over 5 MB is
     left out before it is sent. While files are going up the picker
     dispatches 'doc-uploading' with how many are left, for the Save button. --}}
@php
    $kind = function (string $name): array {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return match (true) {
            $ext === 'pdf'                                                   => ['PDF', 'bg-red-50 text-red-600'],
            in_array($ext, ['doc', 'docx', 'odt', 'rtf'], true)              => ['DOC', 'bg-blue-50 text-blue-600'],
            in_array($ext, ['xls', 'xlsx', 'csv', 'ods'], true)              => ['XLS', 'bg-emerald-50 text-emerald-600'],
            in_array($ext, ['ppt', 'pptx', 'odp'], true)                     => ['PPT', 'bg-orange-50 text-orange-600'],
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'svg', 'bmp'], true) => ['IMG', 'bg-purple-50 text-purple-600'],
            in_array($ext, ['zip', 'rar', '7z'], true)                       => ['ZIP', 'bg-amber-50 text-amber-600'],
            default => [$ext !== '' ? strtoupper(substr($ext, 0, 4)) : 'FILE', 'bg-gray-100 text-gray-600'],
        };
    };
    $maxDocs = $this->maxDocs();
@endphp
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1.5">Files <span class="text-red-500">*</span></label>

    <div x-data="{
            left: 0, total: 0, over: false, skipped: [],
            async take(list) {
                const files = [...list];
                if (!files.length || this.left) return;
                this.skipped = files.filter(f => f.size > 5 * 1024 * 1024).map(f => f.name);
                const ok = files.filter(f => f.size <= 5 * 1024 * 1024);
                this.total = ok.length;
                this.left = ok.length;
                this.$dispatch('doc-uploading', this.left);
                for (const file of ok) {
                    await new Promise((done) => this.$wire.upload('docPick', file, done, done));
                    this.left--;
                    this.$dispatch('doc-uploading', this.left);
                }
                this.total = 0;
            }
        }"
        x-on:dragover.prevent="over = true" x-on:dragleave.prevent="over = false"
        x-on:drop.prevent="over = false; take($event.dataTransfer.files)">
        <label class="flex flex-col items-center justify-center gap-1 px-4 py-5 border-2 border-dashed rounded-lg text-center cursor-pointer transition-colors"
            :class="over ? 'border-blue-400 bg-blue-50/60' : 'border-gray-300 hover:border-blue-300 hover:bg-gray-50'">
            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <span x-show="!left" class="text-sm font-medium text-gray-700">
                {{ count($docFiles) ? 'Add more files' : 'Choose files' }} <span class="font-normal text-gray-400">or drop them here</span>
            </span>
            <span x-show="left" x-cloak class="text-sm font-medium text-blue-700" x-text="'Uploading ' + (total - left + 1) + ' of ' + total + '…'"></span>
            <span class="text-xs text-gray-400">One or several at once — up to {{ $maxDocs }}, each up to 5 MB</span>
            <input type="file" multiple class="sr-only" x-bind:disabled="left > 0"
                x-on:change="take($event.target.files); $event.target.value = ''">
        </label>
        <p x-show="skipped.length" x-cloak class="mt-1 text-xs text-red-500" x-text="'Larger than 5 MB, left out: ' + skipped.join(', ')"></p>
    </div>
    @error('docPick')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
    @error('docFiles')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror

    @if (count($docFiles))
        <div class="mt-3 border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-3 py-2 bg-gray-50 border-b border-gray-200 text-xs text-gray-500">
                <strong class="text-gray-700">{{ count($docFiles) }} {{ count($docFiles) === 1 ? 'file' : 'files' }}</strong>
                — {{ count($docFiles) === 1 ? 'give it a title' : 'each becomes a document of its own; give each a title' }}
            </div>
            <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                @foreach ($docFiles as $i => $picked)
                    @php
                        $fileName = $picked->getClientOriginalName();
                        [$label, $tile] = $kind($fileName);
                    @endphp
                    <div class="flex items-start gap-3 px-3 py-2.5" wire:key="doc-pick-{{ $picked->getFilename() }}">
                        <span class="w-9 h-9 mt-0.5 rounded-lg {{ $tile }} flex items-center justify-center flex-shrink-0 text-[9px] font-bold">{{ $label }}</span>
                        <div class="flex-1 min-w-0">
                            <input type="text" wire:model="docTitles.{{ $i }}" maxlength="255" placeholder="Title" aria-label="Title for {{ $fileName }}"
                                class="w-full px-2.5 py-1.5 border {{ $errors->has('docTitles.' . $i) ? 'border-red-300' : 'border-gray-300' }} rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-[11px] text-gray-400 truncate mt-0.5" title="{{ $fileName }}">{{ $fileName }}</p>
                            @error('docTitles.' . $i)<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <button type="button" wire:click="removeDoc({{ $i }})" title="Remove {{ $fileName }}" aria-label="Remove {{ $fileName }}"
                            class="mt-1 w-7 h-7 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
