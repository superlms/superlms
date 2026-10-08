{{-- Profile on a photo shown large: the round circle the lists show of it,
     set by dragging the photo under it and zooming in or out. The photo itself
     is not cut (Crop does that, x-admin.photo-editor :circle="false").

         <x-admin.photo-circle method="saveListPhotoCircle" />

     It opens on the window event `lms-circle-photo` { method, url, circle } —
     circle being the one set before ({x, y, w, h}) or null — and Save calls
     the Livewire `method` with x, y, w, h (fractions of the photo). The popup
     is moved to <body>. Logic: window.lmsPhotoCircle (partials/photo-circle-js). --}}
@props(['method', 'save' => 'Save'])

<span x-data="lmsPhotoCircle(@js($method))" class="contents"
    x-on:lms-circle-photo.window="$event.detail && $event.detail.method === method && openUrl($event.detail.url, $event.detail.circle)">
    <template x-teleport="body">
        <div x-show="open" style="display: none"
            class="fixed inset-0 z-[10050] flex items-center justify-center p-4 bg-black/60 backdrop-blur-[1.5px]"
            x-on:keydown.escape.window="open && close()">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-xl max-h-full overflow-y-auto p-5">
                <h3 class="text-base font-semibold text-gray-900">Profile photo</h3>
                <p class="text-xs text-gray-500 mt-0.5" x-show="src">Drag the photo to set the face in the circle; zoom in or out to show less or more of it. The circle is what the lists show — the photo itself stays as it is.</p>

                <template x-if="src">
                    <div class="mt-4 flex flex-col md:flex-row gap-5 items-center md:items-start">
                        <div class="flex-shrink-0" :style="`width:${stage}px`">
                            {{-- The photo under the circle (the rest dimmed) --}}
                            <div class="relative overflow-hidden rounded-lg bg-gray-900 select-none touch-none cursor-move"
                                :style="`width:${stage}px;height:${stage}px`"
                                x-on:pointerdown="down($event)" x-on:pointermove="move($event)"
                                x-on:pointerup="up()" x-on:pointercancel="up()"
                                x-on:wheel.prevent="wheel($event)">
                                <img :src="src" alt="" draggable="false"
                                    class="absolute max-w-none pointer-events-none" :style="imgStyle()">
                                <div class="absolute rounded-full pointer-events-none" :style="ringStyle()"></div>
                            </div>

                            {{-- Zoom --}}
                            <div class="mt-3 flex items-center gap-2">
                                <button type="button" title="Zoom out" x-on:click="zoomBy(1 / 1.25)" :disabled="busy || zoom <= 1.001"
                                    class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100 disabled:opacity-40">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14" /></svg>
                                </button>
                                <input type="range" min="1" max="5" step="0.01" aria-label="Zoom"
                                    :value="zoom" x-on:input="setZoom(+$event.target.value)" :disabled="busy"
                                    class="flex-1 min-w-0" style="accent-color: #2563eb">
                                <button type="button" title="Zoom in" x-on:click="zoomBy(1.25)" :disabled="busy || zoom >= 4.999"
                                    class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100 disabled:opacity-40">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                                </button>
                            </div>
                        </div>

                        {{-- How it will show --}}
                        <div class="flex flex-col items-center md:pt-1 md:w-32">
                            <div class="rounded-full ring-1 ring-gray-200 bg-gray-100" :style="previewStyle(96)"></div>
                            <p class="mt-2 text-xs text-gray-500 text-center">In the list</p>
                            <div class="mt-3 rounded-full ring-1 ring-gray-200 bg-gray-100" :style="previewStyle(40)"></div>
                        </div>
                    </div>
                </template>

                <p x-show="loading" class="mt-3 text-sm text-gray-500">Opening the photo…</p>
                <p x-show="busy" class="mt-3 text-sm text-blue-600">Saving…</p>
                <p x-show="error" x-text="error" class="mt-3 text-sm text-red-600"></p>

                <div class="flex items-center justify-end gap-2 mt-5">
                    <button type="button" x-on:click="close()" :disabled="busy"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md disabled:opacity-50">Cancel</button>
                    <button type="button" x-show="src" x-on:click="save()" :disabled="busy"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-60">{{ $save }}</button>
                </div>
            </div>
        </div>
    </template>
</span>
