@props([
    'show' => false,
    'title' => '',
    'image' => null,
    'closeAction' => 'closeModal',
    'maxWidth' => 'max-w-2xl',
    'footer' => null,
])

@if ($show)
    <div class="fixed inset-0 z-[9999]">
        {{-- Backdrop: catches outside clicks --}}
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]"
             wire:click="{{ $closeAction }}"></div>

        {{-- Panel: sits above backdrop so its clicks never reach the backdrop --}}
        <div class="relative z-10 flex justify-end h-full pt-16 pb-4">
        <div class="relative w-full {{ $maxWidth }} mx-4 sm:mx-6 md:mx-8 my-8">
            <div class="relative bg-white/90 backdrop-blur-sm rounded-lg shadow-xl flex flex-col max-h-[80vh]">
                
                {{-- <!-- Image (if provided) -->
                @if ($image)
                    <div class="flex justify-center -mt-10">
                        <img src="{{ $image }}" alt="Modal Image" 
                             class="w-20 h-20 rounded-full border-4 border-white shadow-md object-cover" />
                    </div>
                @endif --}}

                <!-- Header -->
                <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate pr-4 mx-auto">{{ $title }}</h3>
                    <button type="button" wire:click="{{ $closeAction }}"
                        class="absolute right-4 top-4 text-gray-400 hover:text-gray-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-6 text-gray-800 text-sm space-y-3 text-center">
                    {{ $slot }}
                </div>

                <!-- Footer -->
                <div class="p-4 sm:px-6 flex justify-end border-t border-gray-200 bg-gray-50/50">
                    @if (isset($footer))
                        {{ $footer }}
                    @else
                        <x-button flat label="Close" wire:click="{{ $closeAction }}" class="px-4 py-2" />
                    @endif
                </div>
            </div>
        </div>
        </div>
    </div>
@endif
