<div class="space-y-5">
    @php
        $shown   = $previewUrl ?: $savedUrl;
        $state   = !$qr ? 'none' : ($qr->is_active ? 'live' : 'off');
        $qrPath  = 'M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5zM6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z';
    @endphp

    {{-- ══════════════════════════════════════════════════
         STATUS (whether students see it, and what waits)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5 flex flex-col md:flex-row md:items-center gap-4">
        <div class="flex items-center gap-4 flex-1 min-w-0">
            <div class="relative w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qrPath }}" /></svg>
                @if ($state !== 'none')
                    <span class="absolute -right-1 -bottom-1 w-3.5 h-3.5 rounded-full ring-2 ring-white {{ $state === 'live' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                @endif
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-semibold text-gray-900">Fees on your school's UPI QR</h2>
                    @if ($state === 'live')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                            <span class="relative flex w-1.5 h-1.5">
                                <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span>
                                <span class="relative inline-flex w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            </span>
                            Live in the app
                        </span>
                    @elseif ($state === 'off')
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Switched off
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            Not set up
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-0.5">
                    @if ($state === 'live')
                        Students see it in the app under Fees → Pay on school QR.
                    @elseif ($state === 'off')
                        Hidden from students until you switch it on — nothing else is lost.
                    @else
                        Add the QR from your bank or UPI app, and students can pay fees on it from the app.
                    @endif
                    @if ($qr?->updated_at)
                        <span class="text-gray-400">· Updated {{ $qr->updated_at->diffForHumans() }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 md:justify-end">
            @if ($pendingCount > 0)
                <button type="button" wire:click="$parent.showTab('qr_payments')"
                    class="inline-flex items-center gap-2.5 rounded-lg border border-amber-200 bg-amber-50 pl-3 pr-2.5 py-2 text-left hover:bg-amber-100/70 transition-colors">
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="leading-tight">
                        <span class="block text-sm font-semibold text-amber-900">{{ $pendingCount }} {{ $pendingCount === 1 ? 'payment' : 'payments' }} to check</span>
                        <span class="block text-xs text-amber-700">₹{{ number_format($pendingAmount, 0) }} reported · QR Payments</span>
                    </span>
                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </button>
            @endif

            {{-- Show in the app: takes effect at once for a saved QR --}}
            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 pl-3.5 pr-3 py-2">
                <div class="leading-tight">
                    <p class="text-sm font-medium text-gray-800">Show in the app</p>
                    <p class="text-xs text-gray-500">
                        @if (!$qr)
                            {{ $isActive ? 'Live as soon as it is saved' : 'Stays hidden when saved' }}
                        @else
                            {{ $isActive ? 'Students can pay on it' : 'Students don\'t see it' }}
                        @endif
                    </p>
                </div>
                <button type="button" wire:click="toggleActive" wire:loading.attr="disabled" wire:target="toggleActive"
                    role="switch" aria-checked="{{ $isActive ? 'true' : 'false' }}" aria-label="Show in the app"
                    class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 {{ $isActive ? 'bg-emerald-500' : 'bg-gray-300' }}">
                    <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform {{ $isActive ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        {{-- ══════════════════════════════════════════════════
             FORM
        ══════════════════════════════════════════════════ --}}
        <form wire:submit="save" class="lg:col-span-7 bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">{{ $qr ? 'QR & payment details' : 'Set up your payment QR' }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">The QR from your bank or UPI app (Paytm, PhonePe, GPay Business, BHIM…)</p>
            </div>

            <div class="divide-y divide-gray-100">
                {{-- QR image --}}
                <div class="p-5">
                    <div class="flex items-baseline justify-between gap-3 mb-2">
                        <label for="qr-image-input" class="text-sm font-medium text-gray-700">
                            QR image @if (!$qr)<span class="text-rose-500">*</span>@endif
                        </label>
                        <span class="text-xs text-gray-400">JPG, PNG or WebP · up to 2 MB</span>
                    </div>

                    <div x-data="{ over: false, busy: false, progress: 0 }"
                        x-on:livewire-upload-start="busy = true; progress = 0"
                        x-on:livewire-upload-progress="progress = $event.detail.progress"
                        x-on:livewire-upload-finish="busy = false"
                        x-on:livewire-upload-cancel="busy = false"
                        x-on:livewire-upload-error="busy = false">
                        <div class="relative rounded-xl border-2 border-dashed transition-colors {{ $errors->has('qrImage') ? 'border-rose-300 bg-rose-50/40' : 'border-gray-200 bg-gray-50/60 hover:border-indigo-300 hover:bg-indigo-50/30' }}">

                            <div class="flex items-center gap-4 p-4">
                                @if ($shown)
                                    <div class="relative shrink-0">
                                        <img src="{{ $shown }}" alt="Payment QR" class="w-20 h-20 object-contain rounded-lg border border-gray-200 bg-white p-1">
                                        @if ($previewUrl)
                                            <span class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-amber-500 ring-2 ring-white flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <div class="w-20 h-20 rounded-lg bg-white border border-gray-200 flex items-center justify-center shrink-0">
                                        <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                    </div>
                                @endif

                                <div class="flex-1 min-w-0">
                                    @if ($previewUrl)
                                        <p class="text-sm font-semibold text-gray-900">New QR chosen</p>
                                        <p class="text-xs text-amber-700 mt-0.5">Not saved yet — save to show it to students.</p>
                                    @elseif ($savedUrl)
                                        <p class="text-sm font-semibold text-gray-900">Current QR</p>
                                        <p class="text-xs text-gray-500 mt-0.5">Drop a new image here, or browse, only to replace it.</p>
                                    @else
                                        <p class="text-sm font-semibold text-gray-900">Drop your QR here, or <span class="text-indigo-600">browse</span></p>
                                        <p class="text-xs text-gray-500 mt-0.5">A clear, straight picture of the QR, with nothing cut off.</p>
                                    @endif
                                </div>

                                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-xs font-semibold text-gray-700 shadow-sm shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" /></svg>
                                    {{ $shown ? 'Replace' : 'Browse' }}
                                </span>
                            </div>

                            {{-- A file dragged over the zone --}}
                            <div x-show="over" x-cloak class="pointer-events-none absolute -inset-0.5 rounded-xl border-2 border-dashed border-indigo-400 bg-indigo-50/90 flex items-center justify-center gap-2 text-sm font-semibold text-indigo-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                Drop it to upload
                            </div>

                            {{-- Upload progress --}}
                            <div x-show="busy" x-cloak class="pointer-events-none absolute inset-x-4 bottom-2 h-1 rounded-full bg-indigo-100 overflow-hidden">
                                <div class="h-full bg-indigo-500 transition-all" :style="`width: ${progress}%`"></div>
                            </div>

                            {{-- The input covers the zone, so a click browses and a dropped file lands in it. --}}
                            <input id="qr-image-input" type="file" wire:model="qrImage" accept="image/png,image/jpeg,image/webp"
                                x-on:click="$el.value = null"
                                x-on:dragenter="over = true" x-on:dragleave="over = false" x-on:drop="over = false"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                        </div>

                        <p x-show="busy" x-cloak class="text-xs text-indigo-600 mt-2">Uploading… <span x-text="progress + '%'"></span></p>
                        @error('qrImage') <p class="text-xs text-rose-500 mt-2">{{ $message }}</p> @enderror
                        @if ($qrImage)
                            <div class="flex justify-end mt-2">
                                <button type="button" wire:click="clearUpload" class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-800">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                                    {{ $qr ? 'Keep the current one' : 'Choose another' }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Payee --}}
                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="qr-upi-id" class="block text-sm font-medium text-gray-700 mb-1">UPI ID <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                            <div class="relative">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zm0 0c0 1.657 1.007 3 2.25 3S21 13.657 21 12a9 9 0 10-2.636 6.364M16.5 12V8.25" /></svg>
                                <input id="qr-upi-id" type="text" wire:model.live.debounce.400ms="upiId" placeholder="school@okaxis" autocomplete="off"
                                    class="w-full border rounded-lg pl-9 pr-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $errors->has('upiId') ? 'border-rose-300' : 'border-gray-200' }}">
                            </div>
                            @error('upiId')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @else
                                <p class="text-xs text-gray-400 mt-1">Lets students open their UPI app with the amount filled in, and copy it.</p>
                            @enderror
                        </div>
                        <div>
                            <label for="qr-payee" class="block text-sm font-medium text-gray-700 mb-1">Account name</label>
                            <div class="relative">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" /></svg>
                                <input id="qr-payee" type="text" wire:model.live.debounce.400ms="payeeName" placeholder="As on the bank account"
                                    class="w-full border rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $errors->has('payeeName') ? 'border-rose-300' : 'border-gray-200' }}">
                            </div>
                            @error('payeeName')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @else
                                <p class="text-xs text-gray-400 mt-1">So students know they are paying the right account.</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Note --}}
                <div class="p-5">
                    <div class="flex items-baseline justify-between gap-3 mb-1">
                        <label for="qr-note" class="text-sm font-medium text-gray-700">Note for students <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                        <span class="text-xs tabular-nums {{ mb_strlen($instructions) > 450 ? 'text-amber-600' : 'text-gray-400' }}">{{ mb_strlen($instructions) }}/500</span>
                    </div>
                    <textarea id="qr-note" wire:model.live.debounce.400ms="instructions" rows="3" maxlength="500"
                        placeholder="e.g. Write the student's name and class in the payment remark."
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $errors->has('instructions') ? 'border-rose-300' : 'border-gray-200' }}"></textarea>
                    @error('instructions')
                        <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                    @else
                        <p class="text-xs text-gray-400 mt-1">Shown under the QR in the app.</p>
                    @enderror
                </div>
            </div>

            <div class="px-5 py-3.5 border-t border-gray-100 bg-gray-50/60 flex items-center justify-between gap-3">
                @if ($qr)
                    <button type="button" wire:click="confirmRemove"
                        class="inline-flex items-center gap-1.5 px-3 py-2 -ml-3 rounded-lg text-sm font-medium text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                        Remove QR
                    </button>
                @else
                    <span></span>
                @endif
                <button type="submit" wire:loading.attr="disabled" wire:target="save,qrImage"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    {{ $qr ? 'Save changes' : 'Save QR' }}
                </button>
            </div>
        </form>

        {{-- ══════════════════════════════════════════════════
             WHAT STUDENTS SEE (the app's Pay on School QR, drawn small)
        ══════════════════════════════════════════════════ --}}
        <div class="lg:col-span-5 lg:sticky lg:top-24">
            <div class="flex items-end justify-between gap-3 mb-3 px-1">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">What students see</h3>
                    <p class="text-xs text-gray-500 mt-0.5">In the app, under Fees → Pay on school QR</p>
                </div>
                @if ($previewUrl)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700 border border-amber-200 whitespace-nowrap">New QR · not saved</span>
                @endif
            </div>

            <div class="mx-auto w-full max-w-[300px] rounded-[2.6rem] bg-gray-900 p-2 shadow-xl ring-1 ring-gray-800">
                <div class="relative rounded-[2.1rem] bg-gray-100 overflow-hidden">
                    {{-- Status bar --}}
                    <div class="relative bg-white px-6 pt-2.5 pb-1 flex items-center justify-between text-[10px] font-semibold text-gray-900">
                        <span>9:41</span>
                        <span class="absolute left-1/2 top-2 -translate-x-1/2 w-16 h-[18px] rounded-full bg-gray-900"></span>
                        <span class="flex items-center gap-1">
                            <span class="flex items-end gap-px h-2.5">
                                <span class="w-[3px] h-1 rounded-sm bg-gray-900"></span><span class="w-[3px] h-1.5 rounded-sm bg-gray-900"></span><span class="w-[3px] h-2 rounded-sm bg-gray-900"></span><span class="w-[3px] h-2.5 rounded-sm bg-gray-900"></span>
                            </span>
                            <span class="w-5 h-2.5 rounded-[3px] border border-gray-900 p-px"><span class="block h-full w-3/4 rounded-[1px] bg-gray-900"></span></span>
                        </span>
                    </div>
                    {{-- App header --}}
                    <div class="bg-white px-3 py-2.5 flex items-center gap-2 border-b border-gray-100">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        <span class="text-[13px] font-semibold text-gray-900">Pay on School QR</span>
                    </div>

                    {{-- Screen --}}
                    <div class="relative p-3 space-y-3">
                        <div class="bg-white rounded-xl p-3">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qrPath }}" /></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[12px] font-semibold text-gray-900 leading-tight">1 · Pay the school</p>
                                    <p class="text-[10.5px] text-gray-500 truncate">{{ $payeeName ?: 'On its UPI QR' }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col items-center pt-3">
                                @if ($shown)
                                    <img src="{{ $shown }}" alt="" class="w-40 h-40 object-contain">
                                    <p class="text-[10px] text-gray-400 mt-1.5">Tap to enlarge · scan it from another phone</p>
                                @else
                                    <div class="w-40 h-40 rounded-lg border-2 border-dashed border-gray-200 flex flex-col items-center justify-center gap-1.5 text-gray-300">
                                        <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $qrPath }}" /></svg>
                                        <span class="text-[10px] font-medium text-gray-400">Your QR shows here</span>
                                    </div>
                                @endif
                            </div>

                            @if ($upiId !== '')
                                <div class="mt-3 flex items-center gap-2 rounded-lg bg-gray-50 border border-gray-100 px-2.5 py-1.5">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[9.5px] text-gray-400 leading-tight">UPI ID</p>
                                        <p class="text-[11.5px] font-semibold text-gray-900 truncate">{{ $upiId }}</p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 text-[10.5px] font-semibold text-indigo-600">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v8.25A2.25 2.25 0 006 16.5h2.25m8.25-8.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-7.5A2.25 2.25 0 018.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 00-2.25 2.25v6" /></svg>
                                        Copy
                                    </span>
                                </div>
                            @endif

                            @if ($upiId !== '' || $shown)
                                <div class="mt-2.5 flex gap-2">
                                    @if ($upiId !== '')
                                        <span class="flex-1 inline-flex items-center justify-center gap-1 rounded-lg bg-indigo-600 py-1.5 text-[10.5px] font-semibold text-white">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                            Open UPI app
                                        </span>
                                    @endif
                                    @if ($shown)
                                        <span class="flex-1 inline-flex items-center justify-center gap-1 rounded-lg border border-indigo-600 py-1.5 text-[10.5px] font-semibold text-indigo-600">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                            Save QR
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[9.5px] text-gray-400 leading-snug mt-2">
                                    {{ $upiId !== ''
                                        ? 'On this phone? Open your UPI app from here, or save the QR and pick it with "scan from gallery".'
                                        : 'On this phone? Save the QR and pick it in your UPI app with "scan from gallery".' }}
                                </p>
                            @endif

                            @if ($instructions !== '')
                                <div class="mt-2.5 flex gap-1.5 rounded-lg bg-gray-50 px-2 py-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-500 shrink-0 mt-px" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                                    <p class="text-[10.5px] text-gray-600 leading-snug break-words min-w-0">{{ $instructions }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- The rest of the screen, as a hint --}}
                        <div class="bg-white rounded-t-xl p-3 h-20 -mb-3 overflow-hidden">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                </span>
                                <p class="text-[12px] font-semibold text-gray-900">2 · Tell the school</p>
                            </div>
                            <div class="mt-3 space-y-2">
                                <div class="h-7 rounded-lg bg-gray-100"></div>
                                <div class="h-7 rounded-lg bg-gray-100"></div>
                                <div class="h-7 w-2/3 rounded-lg bg-gray-100"></div>
                            </div>
                        </div>

                        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-14 bg-linear-to-t from-gray-100 to-transparent"></div>

                        @if ($qr && !$isActive && !$previewUrl)
                            <div class="absolute inset-0 bg-gray-100/80 backdrop-blur-[2px] flex items-center justify-center p-6">
                                <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-4 text-center">
                                    <span class="mx-auto w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                    </span>
                                    <p class="mt-2 text-[12px] font-semibold text-gray-900">Switched off</p>
                                    <p class="mt-0.5 text-[10.5px] text-gray-500 leading-snug">Students see “Not taking fees on QR” until you switch it on.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         HOW IT WORKS
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">How it works</h3>
        <ol class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            @foreach ([
                ['Student pays', 'They scan this QR in the app, or open their UPI app on it. The money comes straight to your account.', 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
                ['Student reports it', 'They send the UTR from their UPI app, a screenshot of the payment, or both.', 'M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5'],
                ['You check it', 'In QR Payments, match it with your bank statement and approve — the receipt is made then — or reject it with a reason.', 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
            ] as $i => [$title, $text, $icon])
                <li class="relative flex gap-3">
                    <div class="relative shrink-0">
                        <span class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                        </span>
                        <span class="absolute -top-1.5 -left-1.5 w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white">{{ $i + 1 }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ $title }}</p>
                        <p class="text-xs text-gray-500 leading-relaxed mt-0.5">{{ $text }}</p>
                    </div>
                    @if ($i < 2)
                        <svg class="hidden md:block absolute -right-5 top-2.5 w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</div>
