{{-- A photo file input that lets the photo be fitted in its circle before it
     is uploaded. Use it in place of <input type="file" wire:model="…">:

         <x-admin.photo-cropper model="studentImage" class="flex-1 text-sm" />

     Attributes go on the input. The popup is moved to <body>, so it covers the
     whole window (top bar and sidebar too) wherever the input sits. Logic:
     window.lmsPhotoCropper (partials/photo-cropper-js). --}}
@props(['model'])

<span x-data="lmsPhotoCropper(@js($model))" class="contents">
    <input type="file" accept="image/*" x-ref="input" x-on:change="pick($event)" {{ $attributes }}>

    <template x-teleport="body">
        <div x-show="open" style="display: none"
            class="fixed inset-0 z-[10050] flex items-center justify-center p-4 bg-black/50 backdrop-blur-[1.5px]"
            x-on:keydown.escape.window="open && close()" x-on:click.self="close()">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-5">
                <h3 class="text-base font-semibold text-gray-900">Adjust photo</h3>
                <p class="text-xs text-gray-500 mt-0.5" x-show="src">Drag to move it, zoom to fit — the circle is how it will show.</p>

                <template x-if="src">
                    <div>
                        <div class="mx-auto mt-4 relative overflow-hidden rounded-full bg-gray-100 ring-1 ring-gray-200 touch-none select-none cursor-grab active:cursor-grabbing"
                            :style="`width:${size}px;height:${size}px`"
                            x-on:pointerdown.prevent="down($event)" x-on:pointermove="move($event)"
                            x-on:pointerup="up()" x-on:pointercancel="up()" x-on:wheel.prevent="wheel($event)">
                            <img x-ref="img" :src="src" alt="" draggable="false"
                                class="absolute max-w-none pointer-events-none" :style="imgStyle()">
                        </div>

                        <div class="flex items-center gap-3 mt-4">
                            <button type="button" x-on:click="setZoom(zoom - 0.25)" :disabled="busy" title="Zoom out"
                                class="w-8 h-8 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100 disabled:opacity-40">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
                            </button>
                            <input type="range" min="1" max="4" step="0.01" :value="zoom" :disabled="busy"
                                x-on:input="setZoom($event.target.value)" class="flex-1 accent-blue-600">
                            <button type="button" x-on:click="setZoom(zoom + 0.25)" :disabled="busy" title="Zoom in"
                                class="w-8 h-8 flex items-center justify-center rounded-full text-gray-600 hover:bg-gray-100 disabled:opacity-40">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            </button>
                        </div>
                    </div>
                </template>

                <p x-show="busy" class="mt-3 text-sm text-blue-600">Uploading…</p>
                <p x-show="error" x-text="error" class="mt-3 text-sm text-red-600"></p>

                <div class="flex items-center justify-end gap-2 mt-5">
                    <button type="button" x-on:click="close()" :disabled="busy"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md disabled:opacity-50">Cancel</button>
                    <button type="button" x-show="src" x-on:click="use()" :disabled="busy"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-60">Use photo</button>
                </div>
            </div>
        </div>
    </template>
</span>
