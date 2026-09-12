{{--
    The LMS assistant panel.

    It has no launcher of its own — the top bar owns that button (next to the
    notification bell) and flips this open with a `gemini-toggle` browser event,
    so opening it costs no server round-trip. The panel itself is the house
    slide-in shell — the same `fixed inset-x-0 bottom-0 top-16` frame the
    certificate and student panels use — so it sits BELOW the navbar, never
    over it.
--}}
<div>
    @if ($enabled)
        <div x-data="{
                listening: false,
                recog: null,
                supportsVoice: !!(window.SpeechRecognition || window.webkitSpeechRecognition),

                // ── Taking things out of the panel ──────────────────
                // Everything below reads the rendered bubble in the DOM rather
                // than the markdown behind it: what you see is what you copy.
                toast: '',

                flash(message) {
                    this.toast = message;
                    clearTimeout(this._toastTimer);
                    this._toastTimer = setTimeout(() => { this.toast = ''; }, 1800);
                },

                bubble(el) {
                    return el.closest('[data-gem-row]')?.querySelector('[data-gem-bubble]') || null;
                },

                async writeClipboard(text) {
                    if (! text) return false;

                    try {
                        await navigator.clipboard.writeText(text);
                        return true;
                    } catch (e) {
                        // navigator.clipboard needs a secure context; the old
                        // textarea trick still works where it is missing.
                        const box = document.createElement('textarea');
                        box.value = text;
                        box.setAttribute('readonly', '');
                        box.style.position = 'fixed';
                        box.style.top = '-1000px';
                        document.body.appendChild(box);
                        box.select();
                        let ok = false;
                        try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
                        box.remove();
                        return ok;
                    }
                },

                saveBlob(blob, name) {
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = name;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    setTimeout(() => URL.revokeObjectURL(url), 5000);
                },

                async saveUrl(url, name) {
                    try {
                        const blob = await (await fetch(url, { mode: 'cors' })).blob();
                        const ext = (blob.type.split('/')[1] || '').split('+')[0].replace('jpeg', 'jpg');
                        const named = /\.[a-z0-9]{2,5}$/i.test(name) || ! ext ? name : name + '.' + ext;
                        this.saveBlob(blob, named);
                        return true;
                    } catch (e) {
                        // A cross-origin file without CORS headers cannot be
                        // read here — hand it to the browser instead of failing.
                        window.open(url, '_blank', 'noopener');
                        return false;
                    }
                },

                async copyMessage(el) {
                    const bubble = this.bubble(el);
                    if (! bubble) return;
                    this.flash(await this.writeClipboard(bubble.innerText.trim()) ? 'Copied' : 'Copy failed');
                },

                async copyInput() {
                    const text = (this.$refs.input?.value || '').trim();
                    if (! text) { this.flash('Nothing to copy'); return; }
                    this.flash(await this.writeClipboard(text) ? 'Copied' : 'Copy failed');
                },

                // ── Tables ──
                readTables(el) {
                    return [...(this.bubble(el)?.querySelectorAll('table') || [])].map((table) => {
                        const rows = [...table.rows].map((row) =>
                            [...row.cells].map((cell) => cell.innerText.trim().replace(/\s+/g, ' ')));

                        return { headers: table.tHead ? (rows.shift() || []) : [], rows };
                    });
                },

                async copyTable(el) {
                    // Tab-separated, so it pastes into Excel or Sheets as cells
                    // rather than as one long line.
                    const text = this.readTables(el)
                        .map((t) => [t.headers, ...t.rows].filter((r) => r.length).map((r) => r.join('\t')).join('\n'))
                        .filter(Boolean).join('\n\n');

                    this.flash(await this.writeClipboard(text) ? 'Table copied' : 'Copy failed');
                },

                async tablePdf(el) {
                    const tables = this.readTables(el).filter((t) => t.headers.length || t.rows.length);
                    if (! tables.length) return;

                    this.flash('Making PDF…');

                    try {
                        const response = await fetch('{{ route('assistant.table-pdf') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/pdf',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({ title: 'LMS Assistant', tables }),
                        });

                        if (! response.ok) throw new Error(response.status);

                        this.saveBlob(await response.blob(), 'lms-assistant-table.pdf');
                        this.flash('PDF downloaded');
                    } catch (e) {
                        this.flash('PDF failed');
                    }
                },

                // ── Images ──
                readImages(el) {
                    return [...(this.bubble(el)?.querySelectorAll('img') || [])];
                },

                async copyImage(el) {
                    const image = this.readImages(el)[0];
                    if (! image) return;

                    try {
                        const blob = await (await fetch(image.src, { mode: 'cors' })).blob();
                        // The clipboard only takes PNG; anything else goes
                        // through a canvas first.
                        const png = blob.type === 'image/png' ? blob : await this.toPng(image);
                        await navigator.clipboard.write([new ClipboardItem({ 'image/png': png })]);
                        this.flash('Image copied');
                    } catch (e) {
                        this.flash('Copy blocked — use Save');
                    }
                },

                toPng(image) {
                    return new Promise((resolve, reject) => {
                        const canvas = document.createElement('canvas');
                        canvas.width = image.naturalWidth;
                        canvas.height = image.naturalHeight;
                        canvas.getContext('2d').drawImage(image, 0, 0);
                        canvas.toBlob((blob) => blob ? resolve(blob) : reject(new Error('no blob')), 'image/png');
                    });
                },

                async saveImages(el) {
                    const images = this.readImages(el);
                    if (! images.length) return;

                    for (const [index, image] of images.entries()) {
                        await this.saveUrl(image.src, 'assistant-image-' + (index + 1));
                    }
                    this.flash(images.length > 1 ? 'Images downloaded' : 'Image downloaded');
                },

                // ── Files and documents behind links ──
                readLinks(el) {
                    return [...(this.bubble(el)?.querySelectorAll('a[href]') || [])]
                        .filter((a) => /^https?:/i.test(a.href));
                },

                async copyLinks(el) {
                    const urls = this.readLinks(el).map((a) => a.href).join('\n');
                    this.flash(await this.writeClipboard(urls) ? 'Link copied' : 'Copy failed');
                },

                async saveFiles(el) {
                    const links = this.readLinks(el);
                    if (! links.length) return;

                    for (const link of links) {
                        const guess = (link.href.split('?')[0].split('/').pop() || 'file');
                        await this.saveUrl(link.href, guess);
                    }
                    this.flash('Download started');
                },

                scrollDown() {
                    this.$nextTick(() => {
                        const box = this.$refs.stream;
                        if (box) box.scrollTop = box.scrollHeight;
                    });
                },

                // Web Speech API — runs entirely in the browser, nothing is
                // uploaded until the user presses send.
                toggleVoice() {
                    if (this.listening) { this.stopVoice(); return; }
                    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!Recognition) return;

                    const recog = new Recognition();
                    recog.lang = document.documentElement.lang || 'en-IN';
                    recog.interimResults = true;
                    recog.continuous = false;

                    let finalText = '';
                    recog.onresult = (event) => {
                        let interim = '';
                        for (let i = event.resultIndex; i < event.results.length; i++) {
                            const chunk = event.results[i][0].transcript;
                            if (event.results[i].isFinal) { finalText += chunk; } else { interim += chunk; }
                        }
                        const box = this.$refs.input;
                        if (box) box.value = (finalText + interim).trim();
                    };
                    recog.onerror = () => this.stopVoice();
                    recog.onend = () => {
                        this.listening = false;
                        const box = this.$refs.input;
                        // Hand the transcript to Livewire only once, at the end.
                        if (box && box.value.trim()) { $wire.set('prompt', box.value.trim()); }
                    };

                    this.recog = recog;
                    this.listening = true;
                    try { recog.start(); } catch (e) { this.listening = false; }
                },

                stopVoice() {
                    this.listening = false;
                    try { this.recog && this.recog.stop(); } catch (e) {}
                },
            }"
            x-on:gemini-run.window="$wire.run()"
            x-on:gemini-scroll.window="scrollDown()"
            x-on:gemini-toggle.window="$wire.toggle()"
            x-on:keydown.escape.window="if ($wire.open) $wire.close()"
            wire:key="gemini-assistant">

            <style>
                /* Element-level styling for the markdown Gemini returns — these
                   tags come from the converter, not from Blade, so utility
                   classes cannot reach them. */
                .gem-md > *:first-child { margin-top: 0; }
                .gem-md > *:last-child  { margin-bottom: 0; }
                .gem-md p               { margin: 0 0 .5rem; }
                .gem-md ul, .gem-md ol  { margin: 0 0 .5rem; padding-left: 1.15rem; }
                .gem-md ul              { list-style: disc; }
                .gem-md ol              { list-style: decimal; }
                .gem-md li              { margin: .15rem 0; }
                .gem-md strong          { font-weight: 600; color: #111827; }
                .gem-md code            { background: #f3f4f6; padding: .05rem .3rem; border-radius: .25rem; font-size: .78rem; }
                .gem-md h1, .gem-md h2, .gem-md h3 { font-size: .82rem; font-weight: 700; margin: .6rem 0 .3rem; color: #111827; }
                .gem-md table           { width: 100%; border-collapse: collapse; margin: .4rem 0; font-size: .75rem; }
                .gem-md th, .gem-md td  { border: 1px solid #e5e7eb; padding: .25rem .4rem; text-align: left; }
                .gem-md th              { background: #f9fafb; font-weight: 600; }
                .gem-md a               { color: #2563eb; text-decoration: underline; }

                @keyframes gem-blink { 0%, 80%, 100% { opacity: .25; } 40% { opacity: 1; } }
                .gem-dot { animation: gem-blink 1.3s infinite; }
                .gem-dot:nth-child(2) { animation-delay: .18s; }
                .gem-dot:nth-child(3) { animation-delay: .36s; }

                @keyframes gem-pulse { 0% { box-shadow: 0 0 0 0 rgba(220,38,38,.45); } 70% { box-shadow: 0 0 0 10px rgba(220,38,38,0); } 100% { box-shadow: 0 0 0 0 rgba(220,38,38,0); } }
                .gem-listening { animation: gem-pulse 1.4s infinite; }

                /* Copy / download chips under a bubble. Always visible rather
                   than hover-only: half the panel's users are on a phone. */
                .gem-act { font-size: 10.5px; line-height: 1; padding: 3.5px 7px; border-radius: 6px;
                           color: #6b7280; background: #fff; border: 1px solid #e5e7eb; white-space: nowrap; }
                .gem-act:hover { color: #111827; background: #f9fafb; border-color: #d1d5db; }

            </style>

            {{-- ───────────── Chat panel ───────────── --}}
            @if ($open)
                {{-- The house slide-in shell: starts under the navbar, anchored
                     right, full height, behind it the same barely-there scrim
                     every other panel uses. --}}
                <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
                    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="close"></div>
                    <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col" wire:click.stop>

                    {{-- Header --}}
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <img src="{{ asset('website-image/Group 11525.png') }}" alt=""
                            width="36" height="36" class="w-9 h-9 object-contain flex-shrink-0">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-lg font-semibold text-gray-900 leading-tight">LMS Assistant</h2>
                            {{-- One line does double duty: what the panel can see, and the
                                 last copy/download result, so a chip never acts silently. --}}
                            <p class="text-xs mt-0.5 truncate">
                                <span x-show="! toast" class="text-gray-500">Gemini · {{ $scopeLabel }} data only</span>
                                <span x-show="toast" x-cloak x-text="toast" class="text-blue-600 font-medium"></span>
                            </p>
                        </div>
                        {{-- The day's shared allowance, at a glance. --}}
                        <span title="Questions left today for everyone in this account. Resets {{ $resetsAt }}."
                            class="text-xs font-semibold px-2 py-1 rounded-md flex-shrink-0
                                   {{ $remaining === 0 ? 'bg-red-50 text-red-600' : ($remaining <= 5 ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                            {{ $remaining }}/{{ $dailyLimit }}
                        </span>
                        @if (count($messages))
                            <button type="button" wire:click="clear" title="Clear chat"
                                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" /></svg>
                            </button>
                        @endif
                        <button type="button" wire:click="close" title="Close"
                            class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Stream --}}
                    <div x-ref="stream" x-init="scrollDown()" class="flex-1 overflow-y-auto px-6 py-5 space-y-3 bg-gray-50">
                        @if (empty($messages))
                            <div class="text-center pt-8 pb-4">
                                <p class="text-base font-semibold text-gray-700">Ask about your LMS data</p>
                                <p class="text-xs text-gray-400 mt-1 px-6">Students, fees, attendance, staff, certificates — type or speak, in English or Hindi.</p>
                            </div>
                            {{-- Two-up: a single column of short prompts looked
                                 stranded once the panel got this wide. --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach ($suggestions as $i => $suggestion)
                                    <button type="button" wire:click="useSuggestion({{ $i }})"
                                        class="w-full h-full text-left px-3.5 py-2.5 text-xs text-gray-700 bg-white border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50/50">
                                        {{ $suggestion }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @foreach ($messages as $i => $message)
                            @if ($message['role'] === 'user')
                                <div wire:key="gm-{{ $i }}" data-gem-row class="flex flex-col items-end">
                                    <div data-gem-bubble class="max-w-[85%] px-3 py-2 rounded-2xl rounded-br-sm bg-blue-600 text-white text-[13px] leading-relaxed whitespace-pre-wrap break-words">{{ $message['text'] }}</div>
                                    <div class="mt-1 flex items-center gap-1">
                                        <button type="button" class="gem-act" x-on:click="copyMessage($el)">Copy</button>
                                    </div>
                                </div>
                            @else
                                @php
                                    // What the bubble will actually contain decides which chips it
                                    // gets — no point offering "Download PDF" on a one-line answer.
                                    $rendered = $this->html($message['text']);
                                    $hasTable = str_contains($rendered, '<table');
                                    $hasImage = str_contains($rendered, '<img');
                                    $hasLink  = str_contains($rendered, '<a href');
                                @endphp
                                <div wire:key="gm-{{ $i }}" data-gem-row class="flex flex-col items-start">
                                    <div data-gem-bubble class="gem-md max-w-[90%] px-3 py-2 rounded-2xl rounded-bl-sm bg-white border border-gray-200 text-[13px] leading-relaxed text-gray-700 break-words overflow-x-auto">
                                        {!! $rendered !!}
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-1">
                                        <button type="button" class="gem-act" x-on:click="copyMessage($el)">Copy</button>
                                        @if ($hasTable)
                                            <button type="button" class="gem-act" x-on:click="copyTable($el)">Copy table</button>
                                            <button type="button" class="gem-act" x-on:click="tablePdf($el)">Table PDF</button>
                                        @endif
                                        @if ($hasImage)
                                            <button type="button" class="gem-act" x-on:click="copyImage($el)">Copy image</button>
                                            <button type="button" class="gem-act" x-on:click="saveImages($el)">Save image</button>
                                        @endif
                                        @if ($hasLink)
                                            <button type="button" class="gem-act" x-on:click="copyLinks($el)">Copy link</button>
                                            <button type="button" class="gem-act" x-on:click="saveFiles($el)">Save file</button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        @if ($pending)
                            <div class="flex justify-start">
                                <div class="px-3 py-2.5 rounded-2xl rounded-bl-sm bg-white border border-gray-200 flex items-center gap-1">
                                    <span class="gem-dot w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    <span class="gem-dot w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    <span class="gem-dot w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                </div>
                            </div>
                        @endif

                        {{-- Keep the newest bubble in view after every render. --}}
                        <div x-init="scrollDown()" wire:key="gm-end-{{ count($messages) }}-{{ (int) $pending }}"></div>
                    </div>

                    {{-- Composer --}}
                    <div class="border-t border-gray-200 px-6 py-3.5 flex-shrink-0 bg-white">
                        @if ($remaining === 0)
                            {{-- Out of questions: say so, and say exactly when it comes back. --}}
                            <div class="flex items-start gap-2 px-3 py-2.5 bg-red-50 border border-red-200 rounded-xl">
                                <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M12 21a9 9 0 110-18 9 9 0 010 18z" /></svg>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-red-800">Daily limit reached</p>
                                    <p class="text-[11px] text-red-700 mt-0.5 leading-snug">
                                        All {{ $dailyLimit }} questions for {{ $scopeLabel === 'This school' ? 'this school' : 'this panel' }} have been used today —
                                        the count is shared by everyone who logs in here.
                                        Resets <span class="font-semibold">{{ $resetsAt }}</span>.
                                    </p>
                                </div>
                            </div>
                        @else
                        <div class="flex items-end gap-2">
                            <textarea x-ref="input" wire:model="prompt" rows="1"
                                x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.submit(); }"
                                x-bind:disabled="$wire.pending"
                                placeholder="{{ 'Ask about students, fees, attendance…' }}"
                                class="flex-1 resize-none max-h-28 px-3 py-2 border border-gray-300 rounded-xl text-[13px] focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50"></textarea>

                            {{-- Copy the question you typed, before or after sending. --}}
                            <button type="button" x-on:click="copyInput()" title="Copy what you typed"
                                class="w-9 h-9 flex items-center justify-center rounded-xl flex-shrink-0 bg-gray-100 text-gray-500 hover:bg-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            </button>

                            <template x-if="supportsVoice">
                                <button type="button" x-on:click="toggleVoice()"
                                    x-bind:class="listening ? 'bg-red-600 text-white gem-listening' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                                    x-bind:title="listening ? 'Stop listening' : 'Speak your question'"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-14 0M12 18v3" /></svg>
                                </button>
                            </template>

                            <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit,run"
                                class="w-9 h-9 flex items-center justify-center rounded-xl text-white flex-shrink-0 bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
                                title="Send">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7" /></svg>
                            </button>
                        </div>
                        <p class="mt-1.5 text-[10px] text-gray-400 text-center" x-show="!listening">
                            {{ $remaining }} of {{ $dailyLimit }} questions left today · resets {{ $resetsAt }}
                        </p>
                        <p class="mt-1.5 text-[10px] text-red-500 text-center" x-show="listening" x-cloak>Listening… speak now</p>
                        @endif
                    </div>

                    </div>
                </div>
            @endif

        </div>
    @endif
</div>
