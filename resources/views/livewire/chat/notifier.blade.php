<div wire:poll.5s="poll"
    x-data="{
        url: '{{ $messagesUrl }}',
        toasts: [],
        onMessagesPage() { return window.location.pathname.replace(/\/+$/, '').endsWith('/messages'); },
        handle(detail) {
            if (!detail || this.onMessagesPage()) return;
            (detail.toasts || []).forEach(t => this.push(t));
        },
        push(t) {
            if (this.toasts.some(x => x.id === t.id)) return;
            this.toasts.push(t);
            this.beep();
            setTimeout(() => this.dismiss(t.id), 6000);
        },
        dismiss(id) { this.toasts = this.toasts.filter(x => x.id !== id); },
        open() { window.location.href = this.url; },
        initial(name) { return (name || '?').charAt(0).toUpperCase(); },
        beep() {
            // Prefer the shared notification sound (same as web-push); fall back
            // to a synthesized tone if the audio file can't play.
            if (window.lmsPlayNotifSound) { window.lmsPlayNotifSound(); return; }
            try {
                const C = window.AudioContext || window.webkitAudioContext;
                if (!C) return;
                const c = new C();
                const o = c.createOscillator();
                const g = c.createGain();
                o.connect(g); g.connect(c.destination);
                o.type = 'sine'; o.frequency.value = 880;
                g.gain.setValueAtTime(0.0001, c.currentTime);
                g.gain.exponentialRampToValueAtTime(0.12, c.currentTime + 0.01);
                g.gain.exponentialRampToValueAtTime(0.0001, c.currentTime + 0.25);
                o.start(); o.stop(c.currentTime + 0.26);
            } catch (e) {}
        },
    }"
    x-on:chat-sync.window="handle($event.detail)">

    <div class="fixed top-20 right-4 z-[9998] flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)] pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div @click="open()"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-8"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-8"
                class="pointer-events-auto cursor-pointer bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-shadow">
                <div class="flex items-start gap-3 p-3">
                    <div class="flex-shrink-0">
                        <template x-if="t.image">
                            <img :src="t.image" class="w-10 h-10 rounded-full object-cover border border-gray-200" alt="">
                        </template>
                        <template x-if="!t.image">
                            <span class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold" x-text="initial(t.name)"></span>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-900 truncate" x-text="t.name"></p>
                            <span class="text-[10px] text-gray-400 flex-shrink-0" x-text="t.time"></span>
                        </div>
                        <p class="text-[11px] text-blue-500 font-medium leading-none mb-0.5" x-text="t.role"></p>
                        <p class="text-xs text-gray-500 truncate" x-text="t.preview"></p>
                    </div>
                    <button type="button" @click.stop="dismiss(t.id)"
                        class="flex-shrink-0 -mt-1 -mr-1 p-1 rounded-md text-gray-300 hover:text-gray-500 hover:bg-gray-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="h-1 bg-blue-500"></div>
            </div>
        </template>
    </div>
</div>
