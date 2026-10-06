{{-- A photo file input whose photo is cropped before it is uploaded: from any
     side (its edges and corners), the part kept moved by dragging inside it,
     and the circle the lists show drawn on it and shown beside it. Use it in
     place of <input type="file" wire:model="…">:

         <x-admin.photo-editor model="studentImage" class="flex-1 text-sm" />
         <x-admin.photo-editor model="viewPhotoUpload" class="hidden" save="Save" />

     `save` names the button that sends the cropped photo to `model` — "Use
     photo" in a form (saved with it), "Save" where the page saves it at once.
     The photo already saved opens the same way on the window event
     `lms-crop-photo` { model, url } — url being this site's copy of it, as a
     picture from another host can't be cut in a canvas. Attributes go on the
     input; the popup is moved to <body>. Logic: window.lmsPhotoEditor
     (partials/photo-editor-js). The circle cropper (x-admin.photo-cropper)
     stays as it was where it is still used. --}}
@props(['model', 'save' => 'Use photo'])

<span x-data="lmsPhotoEditor(@js($model))" class="contents"
    x-on:lms-crop-photo.window="$event.detail && $event.detail.model === model && openUrl($event.detail.url)">
    <input type="file" accept="image/*" x-ref="input" x-on:change="pick($event)" {{ $attributes }}>

    <template x-teleport="body">
        <div x-show="open" style="display: none"
            class="fixed inset-0 z-[10050] flex items-center justify-center p-4 bg-black/60 backdrop-blur-[1.5px]"
            x-on:keydown.escape.window="open && close()">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-full overflow-y-auto p-5">
                <h3 class="text-base font-semibold text-gray-900">Crop photo</h3>
                <p class="text-xs text-gray-500 mt-0.5" x-show="src">Drag an edge or a corner to crop from that side; drag inside to move it. The dotted circle is what the lists show.</p>

                <template x-if="src">
                    <div class="mt-4 flex flex-col md:flex-row gap-5 items-center md:items-start">
                        {{-- The photo, and the part kept (the rest dimmed) --}}
                        <div class="p-2.5 bg-gray-900 rounded-lg overflow-hidden select-none touch-none flex-shrink-0"
                            x-on:pointerdown="down($event)" x-on:pointermove="move($event)"
                            x-on:pointerup="up()" x-on:pointercancel="up()">
                            <div x-ref="stage" class="relative" :style="`width:${dispW}px;height:${dispH}px`">
                                <img :src="src" alt="" draggable="false"
                                    class="absolute inset-0 w-full h-full max-w-none pointer-events-none">
                                <div data-h="move" class="absolute cursor-move" :style="boxStyle()">
                                    <div class="absolute rounded-full border-2 border-dashed border-white/90 pointer-events-none" :style="circleStyle()"></div>
                                    <template x-for="h in handles" :key="h.k">
                                        <span :data-h="h.k" class="absolute w-4 h-4 bg-white border border-gray-500 rounded-sm shadow"
                                            :style="h.style + ';cursor:' + h.cursor"></span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- How it will show --}}
                        <div class="flex flex-col items-center md:pt-1 md:w-32">
                            <div class="rounded-full ring-1 ring-gray-200 bg-gray-100" :style="previewStyle(96)"></div>
                            <p class="mt-2 text-xs text-gray-500 text-center">In the list</p>
                            <div class="mt-3 rounded-full ring-1 ring-gray-200 bg-gray-100" :style="previewStyle(40)"></div>
                            <button type="button" x-on:click="reset()" :disabled="busy"
                                class="mt-4 text-xs font-medium text-blue-600 hover:text-blue-800 disabled:opacity-40">Whole photo</button>
                        </div>
                    </div>
                </template>

                <p x-show="loading" class="mt-3 text-sm text-gray-500">Opening the photo…</p>
                <p x-show="busy" class="mt-3 text-sm text-blue-600">Saving…</p>
                <p x-show="error" x-text="error" class="mt-3 text-sm text-red-600"></p>

                <div class="flex items-center justify-end gap-2 mt-5">
                    <button type="button" x-on:click="close()" :disabled="busy"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md disabled:opacity-50">Cancel</button>
                    <button type="button" x-show="src" x-on:click="use()" :disabled="busy"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-60">{{ $save }}</button>
                </div>
            </div>
        </div>
    </template>
</span>
