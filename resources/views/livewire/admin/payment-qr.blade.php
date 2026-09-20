<div>
    @php
        $shown = $previewUrl ?: $savedUrl;
    @endphp

    {{-- ══════════════════════════════════════════════════
         THE QR — the whole page, as a student sees it
    ══════════════════════════════════════════════════ --}}
    @if (!$qr)
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
            <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM13.5 14.25h2.25v2.25H13.5v-2.25zm4.5 0h2.25v2.25H18v-2.25zm-4.5 4.5h2.25V21H13.5v-2.25zm4.5 0h2.25V21H18v-2.25z" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-900">No payment QR yet</p>
            <p class="text-xs text-gray-500 mt-1">Add the QR from your bank or UPI app and students can pay fees on it.</p>
            <button wire:click="openPanel"
                class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                Add QR
            </button>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            {{-- Edit and remove sit on the sheet, nothing else --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <div class="flex items-center gap-2 text-xs font-medium">
                    <span class="w-1.5 h-1.5 rounded-full {{ $qr->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                    <span class="{{ $qr->is_active ? 'text-emerald-700' : 'text-gray-500' }}">
                        {{ $qr->is_active ? 'Live in the app' : 'Hidden from students' }}
                    </span>
                </div>
                <div class="flex items-center gap-1">
                    <button wire:click="openPanel" title="Edit"
                        class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </button>
                    <button wire:click="confirmRemove" title="Remove"
                        class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </div>

            <div class="px-6 py-10 flex flex-col items-center">
                @if ($savedUrl)
                    <img src="{{ $savedUrl }}" alt="Payment QR"
                        class="w-full max-w-sm aspect-square object-contain rounded-xl border border-gray-200 bg-white p-4">
                @endif

                <p class="mt-5 text-base font-semibold text-gray-900 text-center">{{ $qr->payee_name ?: (auth()->user()->organization->name ?? 'Your school') }}</p>

                @if ($qr->upi_id)
                    <div class="mt-1.5 flex items-center gap-2" x-data="{ copied: false }">
                        <span class="font-mono text-sm text-gray-600">{{ $qr->upi_id }}</span>
                        <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-800"
                            @click="navigator.clipboard.writeText('{{ $qr->upi_id }}'); copied = true; setTimeout(() => copied = false, 1500)"
                            x-text="copied ? 'Copied' : 'Copy'">Copy</button>
                    </div>
                @endif

                @if ($qr->instructions)
                    <p class="mt-4 max-w-sm text-center text-xs text-gray-500 leading-relaxed">{{ $qr->instructions }}</p>
                @endif

                <p class="mt-5 text-xs text-gray-400">Updated {{ $qr->updated_at?->diffForHumans() }}</p>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         SLIDE-IN PANEL — add or edit the QR
    ══════════════════════════════════════════════════ --}}
    @if ($showPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <form wire:submit="save" class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $qr ? 'Edit payment QR' : 'Add payment QR' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">From your bank or UPI app — Paytm, PhonePe, GPay Business, BHIM</p>
                    </div>
                    <button type="button" wire:click="closePanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                    {{-- QR image — previewed here the moment it uploads --}}
                    <div x-data="{ busy: false, progress: 0 }"
                        x-on:livewire-upload-start="busy = true; progress = 0"
                        x-on:livewire-upload-progress="progress = $event.detail.progress"
                        x-on:livewire-upload-finish="busy = false"
                        x-on:livewire-upload-cancel="busy = false"
                        x-on:livewire-upload-error="busy = false">
                        <div class="flex items-baseline justify-between gap-3 mb-1.5">
                            <label for="qr-image-input" class="text-sm font-medium text-gray-700">
                                QR image @if (!$qr)<span class="text-red-500">*</span>@endif
                            </label>
                            <span class="text-xs text-gray-400">JPG, PNG or WebP · up to 2 MB</span>
                        </div>

                        @if ($shown)
                            <div class="rounded-xl border border-gray-200 p-4 flex flex-col items-center">
                                <img src="{{ $shown }}" alt="QR preview" class="w-44 h-44 object-contain rounded-lg bg-white">
                                <p class="mt-2 text-xs {{ $previewUrl ? 'text-amber-600' : 'text-gray-400' }}">
                                    {{ $previewUrl ? 'New QR — not saved yet' : 'Current QR' }}
                                </p>
                                <div class="mt-2 flex items-center gap-3">
                                    <label for="qr-image-input" class="text-xs font-semibold text-blue-600 hover:text-blue-800 cursor-pointer">Choose another</label>
                                    @if ($qrImage)
                                        <button type="button" wire:click="clearUpload" class="text-xs font-medium text-gray-500 hover:text-gray-800">
                                            {{ $qr ? 'Keep the current one' : 'Remove' }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <label for="qr-image-input"
                                class="flex flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed px-4 py-10 cursor-pointer transition-colors {{ $errors->has('qrImage') ? 'border-red-300 bg-red-50/40' : 'border-gray-200 bg-gray-50 hover:border-blue-300 hover:bg-blue-50/30' }}">
                                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                <span class="text-sm font-medium text-gray-700">Upload the QR</span>
                                <span class="text-xs text-gray-400">A clear, straight picture with nothing cut off</span>
                            </label>
                        @endif

                        <input id="qr-image-input" type="file" wire:model="qrImage" accept="image/png,image/jpeg,image/webp" class="hidden" />

                        <div x-show="busy" x-cloak class="mt-2">
                            <div class="h-1 rounded-full bg-blue-100 overflow-hidden">
                                <div class="h-full bg-blue-500 transition-all" :style="`width: ${progress}%`"></div>
                            </div>
                            <p class="text-xs text-blue-600 mt-1">Uploading… <span x-text="progress + '%'"></span></p>
                        </div>
                        @error('qrImage') <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p> @enderror
                    </div>

                    {{-- UPI ID --}}
                    <div>
                        <label for="qr-upi-id" class="block text-sm font-medium text-gray-700 mb-1">UPI ID <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                        <input id="qr-upi-id" type="text" wire:model.blur="upiId" placeholder="school@okaxis" autocomplete="off"
                            class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $errors->has('upiId') ? 'border-red-300' : 'border-gray-200' }}">
                        @error('upiId')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @else
                            <p class="text-xs text-gray-400 mt-1">Students can open their UPI app with the amount filled in, and copy it.</p>
                        @enderror
                    </div>

                    {{-- Account name --}}
                    <div>
                        <label for="qr-payee" class="block text-sm font-medium text-gray-700 mb-1">Account name</label>
                        <input id="qr-payee" type="text" wire:model.blur="payeeName" placeholder="As on the bank account"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $errors->has('payeeName') ? 'border-red-300' : 'border-gray-200' }}">
                        @error('payeeName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Note --}}
                    <div>
                        <label for="qr-note" class="block text-sm font-medium text-gray-700 mb-1">Note for students <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                        <textarea id="qr-note" wire:model.blur="instructions" rows="3" maxlength="500"
                            placeholder="e.g. Write the student's name and class in the payment remark."
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $errors->has('instructions') ? 'border-red-300' : 'border-gray-200' }}"></textarea>
                        @error('instructions') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Show in the app --}}
                    <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3.5 py-2.5 cursor-pointer">
                        <span class="leading-tight">
                            <span class="block text-sm font-medium text-gray-800">Show in the app</span>
                            <span class="block text-xs text-gray-500">{{ $isActive ? 'Students can pay on it' : 'Students don\'t see it' }}</span>
                        </span>
                        <span class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors {{ $isActive ? 'bg-emerald-500' : 'bg-gray-300' }}">
                            <input type="checkbox" wire:model.live="isActive" class="sr-only">
                            <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform {{ $isActive ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </span>
                    </label>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3 flex-shrink-0">
                    <button type="button" wire:click="closePanel"
                        class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save,qrImage"
                        class="inline-flex items-center gap-1.5 px-5 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        {{ $qr ? 'Save changes' : 'Save QR' }}
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
