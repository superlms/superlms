{{--
    The school's map in Website Data: the map as the site will show it, then
    the ways to set the exact spot — paste a Google Maps link (or the
    coordinates a long-press shows), or the phone's own location at the
    school — and the address, which the map searches for while no spot is
    set. Uses $id, $input, $mapSrc.
--}}
@php
    $loc      = $data[$id];
    $hasPin   = $loc['lat'] !== '' && $loc['lng'] !== '';
    $hasEmbed = $loc['embed'] !== '';
    $btn      = 'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-60';
@endphp
<div class="space-y-4" x-data="{ locating: false, err: '' }">
    <div>
        <div class="h-56 sm:h-64 rounded-lg overflow-hidden border border-gray-200 bg-gray-100">
            @if ($mapSrc)
                <iframe wire:key="map-{{ md5($mapSrc) }}" src="{{ $mapSrc }}" title="Map preview"
                    referrerpolicy="no-referrer-when-downgrade" class="w-full h-full border-0"></iframe>
            @else
                <div class="h-full flex items-center justify-center px-6 text-center text-sm text-gray-400">No map yet — set the spot or the address below.</div>
            @endif
        </div>
        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
            @if ($hasEmbed)
                <span class="text-gray-600">Showing the place from your Google Maps link.</span>
            @elseif ($hasPin)
                <span class="text-gray-600">Pinned at <span class="tabular-nums">{{ $loc['lat'] }}, {{ $loc['lng'] }}</span></span>
                <a href="https://www.google.com/maps?q={{ $loc['lat'] }},{{ $loc['lng'] }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Check on Google Maps</a>
            @elseif (trim($loc['address']) !== '')
                <span class="text-gray-500">Found from the address. Not quite the school? Set the exact spot below.</span>
            @endif
            @if ($hasPin || $hasEmbed)
                <button type="button" wire:click="clearPin" class="text-gray-400 hover:text-red-600">Remove — use the address</button>
            @endif
        </div>
    </div>

    {{-- 1 · a Google Maps link --}}
    <div>
        <label for="map-link" class="block text-xs font-medium text-gray-700 mb-1">Set the exact spot: paste a Google Maps link</label>
        <div class="flex gap-2">
            <input id="map-link" type="text" wire:model="data.{{ $id }}.link" wire:keydown.enter.prevent="setMapLink"
                placeholder="https://maps.app.goo.gl/…" class="{{ $input }}">
            <button type="button" wire:click="setMapLink" wire:loading.attr="disabled" wire:target="setMapLink"
                class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-900 hover:bg-gray-800 text-white flex-shrink-0 disabled:opacity-60">
                <span wire:loading.remove wire:target="setMapLink">Set</span>
                <span wire:loading wire:target="setMapLink">…</span>
            </button>
        </div>
        @error("data.{$id}.link")<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        <details class="mt-1.5 text-xs text-gray-500">
            <summary class="cursor-pointer select-none text-blue-600">How do I get the link?</summary>
            <ol class="list-decimal ml-4 mt-1.5 space-y-0.5">
                <li>Open Google Maps on your phone or computer and search for your school.</li>
                <li>Tap the school's name, then <b>Share</b> &rarr; <b>Copy link</b>.</li>
                <li>Paste it here and press <b>Set</b> — the map above moves to your school.</li>
            </ol>
            <p class="mt-1.5">School not on Google Maps? Press and hold (on a computer, right-click) its spot on the map, copy the numbers that show — like 27.9512, 78.1234 — and paste them here.</p>
        </details>
    </div>

    {{-- 2 · this phone's location --}}
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <button type="button" x-bind:disabled="locating" class="{{ $btn }}"
            x-on:click="err = '';
                if (! navigator.geolocation) { err = 'This browser cannot share its location — paste a link instead.'; return; }
                locating = true;
                navigator.geolocation.getCurrentPosition(
                    (p) => { locating = false; $wire.setPin(p.coords.latitude, p.coords.longitude); },
                    (e) => { locating = false; err = e.code === 1
                        ? 'Location is blocked — allow it for this site in the browser, then try again.'
                        : 'Could not find your location. Try again, or paste a link instead.'; },
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.7 16.7L13.4 21a2 2 0 01-2.8 0l-4.3-4.3a8 8 0 1111.4 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span x-text="locating ? 'Finding you…' : 'Use my current location'">Use my current location</span>
        </button>
        <span class="text-xs text-gray-400">At the school? Press this on your phone.</span>
        <p x-show="err" x-text="err" x-cloak class="w-full text-xs text-red-500"></p>
    </div>

    {{-- 3 · the address --}}
    <div>
        <label for="map-address" class="block text-xs font-medium text-gray-700 mb-1">Address</label>
        <textarea id="map-address" wire:model.blur="data.{{ $id }}.address" rows="2" class="{{ $input }} resize-y"></textarea>
        <p class="text-xs text-gray-400 mt-1">Shown on the website. While no spot is set, the map searches for it.</p>
    </div>
</div>
