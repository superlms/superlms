@props([
    'show' => false,
    'title' => '',
    'closeAction' => 'closeModal',
    'maxWidth' => '2xl',
    'scrollable' => true,
    'maxHeight' => '70vh', // Customizable max height
])

<div x-data="{
    open: @entangle($show),
    close() {
        this.open = false;
        $wire.{{ $closeAction }}();
    }
}" x-show="open" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" x-cloak class="fixed inset-0 z-50 overflow-hidden" style="display: none;">

    <!-- Backdrop -->
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0  bg-opacity-75 backdrop-blur-sm transition-opacity" x-on:click="close()"></div>

    <!-- Modal Container -->
    <div class="fixed inset-0 flex justify-center bg-white/10 backdrop-blur-sm z-[9999] pt-16 pb-4 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="open" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-{{ $maxWidth }}"
                x-on:keydown.escape.window="close()">

                <!-- Header -->
                <div class="bg-white px-6 py-4 border-b border-gray-200 sticky top-0 z-10">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900" id="modal-headline">
                            {{ $title }}
                        </h3>
                        <button type="button"
                            class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            x-on:click="close()">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Scrollable Content Area -->
                @if ($scrollable)
                    <div class="overflow-y-auto" style="max-height: {{ $maxHeight }};">
                        <div class="px-6 py-4">
                            {{ $slot }}
                        </div>
                    </div>
                @else
                    <!-- Non-scrollable content -->
                    <div class="px-6 py-4">
                        {{ $slot }}
                    </div>
                @endif

                <!-- Footer (if provided) -->
                @if (isset($footer))
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
