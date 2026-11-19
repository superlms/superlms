{{-- Quick Links' clock (Super Admin and school admin, 2026-11-19): the time,
     and the date under it in small type. A client clock that ticks every
     second; wire:ignore so Livewire re-renders leave it running. --}}
<span wire:ignore class="sk-clock" aria-live="off"
    x-data="{ date: '', time: '', tick() { const n = new Date(); this.time = n.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }); this.date = n.toLocaleDateString('en-IN', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }); } }"
    x-init="tick(); setInterval(() => tick(), 1000)">
    <b class="tabular-nums" x-text="time"></b>
    <small x-text="date"></small>
</span>
