<div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

    {{-- ══════════════════════════════════════════════════
         WHAT STUDENTS SEE (left)
    ══════════════════════════════════════════════════ --}}
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">What students see</h3>
                    <p class="text-xs text-gray-500 mt-0.5">In the app, under Fees → Pay on school QR</p>
                </div>
                @if ($qr)
                    @if ($qr->is_active)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Live
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Switched off
                        </span>
                    @endif
                @endif
            </div>

            <div class="p-5 flex flex-col items-center text-center">
                @php $shown = $previewUrl ?: $savedUrl; @endphp
                @if ($shown)
                    <div class="relative">
                        <img src="{{ $shown }}" alt="Payment QR"
                            class="w-60 h-60 object-contain rounded-xl border border-gray-200 bg-white p-2 {{ $qr && !$qr->is_active && !$previewUrl ? 'opacity-40' : '' }}">
                        @if ($previewUrl)
                            <span class="absolute -top-2 left-1/2 -translate-x-1/2 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-700 border border-amber-200">Not saved yet</span>
                        @endif
                    </div>
                    <p class="mt-4 text-sm font-semibold text-gray-900">{{ $payeeName ?: 'Your school' }}</p>
                    @if ($upiId)
                        <p class="mt-0.5 text-xs text-gray-500 font-mono">{{ $upiId }}</p>
                    @endif
                    @if ($instructions)
                        <p class="mt-3 text-xs text-gray-500 leading-relaxed max-w-xs">{{ $instructions }}</p>
                    @endif
                @else
                    <div class="w-60 h-60 rounded-xl border-2 border-dashed border-gray-200 flex flex-col items-center justify-center gap-2 text-gray-400">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z" /></svg>
                        <p class="text-sm font-medium">No QR yet</p>
                        <p class="text-xs px-6">Upload your school's UPI QR to take fees on it from the app.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Waiting to be checked --}}
        @if ($pendingCount > 0)
            <button type="button" wire:click="$parent.showTab('qr_payments')"
                class="w-full text-left bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-center gap-4 hover:bg-amber-100/70 transition-colors">
                <div class="w-10 h-10 rounded-lg bg-white border border-amber-200 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-amber-900">{{ $pendingCount }} {{ $pendingCount === 1 ? 'payment' : 'payments' }} to check</p>
                    <p class="text-xs text-amber-700 mt-0.5">₹{{ number_format($pendingAmount, 0) }} reported by students · open QR Payments</p>
                </div>
                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>
        @endif

        {{-- How it works --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">How it works</h3>
            <ol class="space-y-3">
                @foreach ([
                    ['Student pays', 'They scan this QR in the app, or open their UPI app on it. The money comes straight to your account.'],
                    ['Student reports it', 'They send the UTR from their UPI app, a screenshot of the payment, or both.'],
                    ['You check it', 'In QR Payments, match it with your bank statement and approve — the receipt is made then — or reject it with a reason.'],
                ] as $i => [$title, $text])
                    <li class="flex gap-3">
                        <span class="w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $title }}</p>
                            <p class="text-xs text-gray-500 leading-relaxed mt-0.5">{{ $text }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         FORM (right)
    ══════════════════════════════════════════════════ --}}
    <div class="lg:col-span-3">
        <form wire:submit="save" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-900">{{ $qr ? 'Update payment QR' : 'Add payment QR' }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">The QR from your bank or UPI app (Paytm, PhonePe, GPay Business, BHIM…)</p>
            </div>

            <div class="p-5 space-y-5">
                {{-- QR image --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        QR image @if (!$qr)<span class="text-rose-500">*</span>@endif
                        <span class="text-xs text-gray-400 font-normal">(JPG, PNG or WebP, up to 2 MB)</span>
                    </label>
                    <div class="flex flex-wrap items-center gap-3">
                        <input type="file" wire:model="qrImage" accept="image/png,image/jpeg,image/webp"
                            class="block text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-xs file:font-medium hover:file:bg-indigo-100" />
                        @if ($qrImage)
                            <button type="button" wire:click="clearUpload" class="text-xs font-medium text-gray-500 hover:text-gray-800">Keep the current one</button>
                        @endif
                    </div>
                    <div wire:loading wire:target="qrImage" class="text-xs text-indigo-500 mt-1">Uploading…</div>
                    @if ($qr && !$qrImage)
                        <p class="text-xs text-gray-400 mt-1">Choose a file only to replace the QR shown on the left.</p>
                    @endif
                    @error('qrImage') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">UPI ID <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" wire:model.live.debounce.400ms="upiId" placeholder="school@okaxis" autocomplete="off"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Lets students open their UPI app with the amount filled in, and copy it.</p>
                        @error('upiId') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Account name</label>
                        <input type="text" wire:model.live.debounce.400ms="payeeName" placeholder="As on the bank account"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">So students know they are paying the right account.</p>
                        @error('payeeName') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note for students <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                    <textarea wire:model.live.debounce.400ms="instructions" rows="3" maxlength="500"
                        placeholder="e.g. Write the student's name and class in the payment remark."
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    @error('instructions') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Show in the app --}}
                <div class="flex items-start justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800">Show in the app</p>
                        <p class="text-xs text-gray-500 mt-0.5">Switch off to stop taking fees on this QR for a while, without removing it.</p>
                    </div>
                    <button type="button" wire:click="toggleActive" role="switch" aria-checked="{{ $isActive ? 'true' : 'false' }}"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full transition-colors {{ $isActive ? 'bg-emerald-500' : 'bg-gray-300' }}">
                        <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform {{ $isActive ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>

            <div class="px-5 py-3.5 border-t border-gray-100 bg-gray-50/60 flex items-center justify-between gap-3">
                @if ($qr)
                    <button type="button" wire:click="confirmRemove" class="text-sm font-medium text-rose-600 hover:text-rose-800">Remove QR</button>
                @else
                    <span></span>
                @endif
                <button type="submit" wire:loading.attr="disabled" wire:target="save,qrImage"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    {{ $qr ? 'Save changes' : 'Save QR' }}
                </button>
            </div>
        </form>
    </div>
</div>
