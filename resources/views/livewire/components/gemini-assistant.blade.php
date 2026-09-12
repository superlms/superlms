{{--
    Floating Gemini assistant — bottom-right on every panel page.

    The window is capped to the space *below* the top bar so it can never float
    over the navbar, and it is anchored bottom-right rather than being a
    full-height slide-in, so it does not fight the page underneath.
--}}
<div>
    @if ($enabled)
        <div x-data="{
                listening: false,
                recog: null,
                supportsVoice: !!(window.SpeechRecognition || window.webkitSpeechRecognition),

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
            </style>

            {{-- ───────────── Chat window ───────────── --}}
            @if ($open)
                <div class="fixed z-[70] bg-white shadow-2xl border border-gray-200 flex flex-col overflow-hidden
                            inset-x-3 bottom-24 rounded-2xl
                            sm:inset-x-auto sm:right-5 sm:w-[390px]"
                    style="max-height: calc(100vh - 10rem); height: 560px;">

                    {{-- Header --}}
                    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-gray-200 flex-shrink-0">
                        <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                            style="background: linear-gradient(135deg,#4285f4 0%,#9b72cb 50%,#d96570 100%)">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="#fff" aria-hidden="true">
                                <path d="M12 0c0 6.627-5.373 12-12 12 6.627 0 12 5.373 12 12 0-6.627 5.373-12 12-12-6.627 0-12-5.373-12-12z" />
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 leading-tight">LMS Assistant</p>
                            <p class="text-[11px] text-gray-400 leading-tight truncate">Gemini · {{ $scopeLabel }} data only</p>
                        </div>
                        @if (count($messages))
                            <button type="button" wire:click="clear" title="Clear chat"
                                class="w-7 h-7 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" /></svg>
                            </button>
                        @endif
                        <button type="button" wire:click="close" title="Close"
                            class="w-7 h-7 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Stream --}}
                    <div x-ref="stream" x-init="scrollDown()" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-gray-50">
                        @if (empty($messages))
                            <div class="text-center pt-6 pb-3">
                                <p class="text-sm font-semibold text-gray-700">Ask about your LMS data</p>
                                <p class="text-xs text-gray-400 mt-1 px-3">Students, fees, attendance, staff, certificates — type or speak, in English or Hindi.</p>
                            </div>
                            <div class="space-y-2">
                                @foreach ($suggestions as $i => $suggestion)
                                    <button type="button" wire:click="useSuggestion({{ $i }})"
                                        class="w-full text-left px-3 py-2 text-xs text-gray-700 bg-white border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50/50">
                                        {{ $suggestion }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @foreach ($messages as $i => $message)
                            @if ($message['role'] === 'user')
                                <div wire:key="gm-{{ $i }}" class="flex justify-end">
                                    <div class="max-w-[85%] px-3 py-2 rounded-2xl rounded-br-sm bg-blue-600 text-white text-[13px] leading-relaxed whitespace-pre-wrap break-words">{{ $message['text'] }}</div>
                                </div>
                            @else
                                <div wire:key="gm-{{ $i }}" class="flex justify-start">
                                    <div class="gem-md max-w-[90%] px-3 py-2 rounded-2xl rounded-bl-sm bg-white border border-gray-200 text-[13px] leading-relaxed text-gray-700 break-words overflow-x-auto">
                                        {!! $this->html($message['text']) !!}
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
                    <div class="border-t border-gray-200 p-3 flex-shrink-0 bg-white">
                        <div class="flex items-end gap-2">
                            <textarea x-ref="input" wire:model="prompt" rows="1"
                                x-on:keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $wire.submit(); }"
                                x-bind:disabled="$wire.pending"
                                placeholder="{{ 'Ask about students, fees, attendance…' }}"
                                class="flex-1 resize-none max-h-28 px-3 py-2 border border-gray-300 rounded-xl text-[13px] focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50"></textarea>

                            <template x-if="supportsVoice">
                                <button type="button" x-on:click="toggleVoice()"
                                    x-bind:class="listening ? 'bg-red-600 text-white gem-listening' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                                    x-bind:title="listening ? 'Stop listening' : 'Speak your question'"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-14 0M12 18v3" /></svg>
                                </button>
                            </template>

                            <button type="button" wire:click="submit" wire:loading.attr="disabled" wire:target="submit,run"
                                class="w-9 h-9 flex items-center justify-center rounded-xl text-white flex-shrink-0 disabled:opacity-50"
                                style="background: linear-gradient(135deg,#4285f4 0%,#9b72cb 100%)" title="Send">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7" /></svg>
                            </button>
                        </div>
                        <p class="mt-1.5 text-[10px] text-gray-400 text-center" x-show="!listening">
                            Reads your LMS data only · double-check anything critical
                        </p>
                        <p class="mt-1.5 text-[10px] text-red-500 text-center" x-show="listening" x-cloak>Listening… speak now</p>
                    </div>
                </div>
            @endif

            {{-- ───────────── Launcher ───────────── --}}
            <button type="button" wire:click="toggle"
                class="fixed bottom-5 right-5 z-[70] w-14 h-14 rounded-full shadow-lg flex items-center justify-center text-white transition hover:scale-105 active:scale-95"
                style="background: linear-gradient(135deg,#4285f4 0%,#9b72cb 50%,#d96570 100%)"
                title="{{ $open ? 'Close assistant' : 'Ask the LMS assistant' }}"
                aria-label="LMS assistant">
                @if ($open)
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                @else
                    <svg viewBox="0 0 24 24" class="w-7 h-7" fill="currentColor" aria-hidden="true">
                        <path d="M12 0c0 6.627-5.373 12-12 12 6.627 0 12 5.373 12 12 0-6.627 5.373-12 12-12-6.627 0-12-5.373-12-12z" />
                    </svg>
                @endif
            </button>
        </div>
    @endif
</div>
