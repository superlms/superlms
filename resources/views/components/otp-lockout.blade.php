@props(['until' => 0])

{{--
    Lockout notice shown after too many wrong OTP entries.

    `until` is an absolute unix timestamp, not a countdown, so the browser ticks
    it down on its own and a Livewire re-render can never restart it. The notice
    hides itself the moment it reaches zero; the server re-checks the lockout on
    every verify/resend regardless, so it stays the authority.
--}}
@if ((int) $until > time())
    <div x-data="{
            until: {{ (int) $until }},
            remaining: Math.max(0, {{ (int) $until }} - Math.floor(Date.now() / 1000)),
            interval: null,
            init() {
                this.tick();
                this.interval = setInterval(() => this.tick(), 1000);
            },
            destroy() {
                if (this.interval) clearInterval(this.interval);
            },
            tick() {
                this.remaining = Math.max(0, this.until - Math.floor(Date.now() / 1000));
                if (this.remaining === 0 && this.interval) {
                    clearInterval(this.interval);
                    this.interval = null;
                }
            },
            label() {
                const minutes = Math.floor(this.remaining / 60);
                const secs = this.remaining % 60;
                return `${minutes}:${secs.toString().padStart(2, '0')}`;
            }
        }"
        x-show="remaining > 0"
        {{ $attributes->merge(['class' => 'mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-center']) }}>
        <p class="text-sm font-semibold text-red-600">Too many incorrect attempts</p>
        <p class="text-sm text-red-500 mt-0.5">
            Try again after
            <span class="font-bold tabular-nums" x-text="label()"></span>
        </p>
    </div>
@endif
