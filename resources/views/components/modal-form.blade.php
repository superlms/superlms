@props([
    'show' => false,
    'title' => '',
    'submitAction' => 'save',
    'submitButton' => 'Save',
    'maxWidth' => 'max-w-3xl',
    'maxHeight' => 'max-h-[calc(100vh-8rem)]',
    'closeAction' => 'closeModal',
])

@if ($show)
    <div
        class="fixed inset-0 flex justify-end bg-white/10 backdrop-blur-sm z-[9999] pt-16 pb-4 overflow-y-auto">
        <div class="relative w-full {{ $maxWidth }} {{ $maxHeight }} mx-4 sm:mx-6 md:mx-8 my-4">
            <div title="{{ $title }}"
                class="relative z-10 bg-white/90 backdrop-blur-sm rounded-lg shadow-xl h-full flex flex-col min-h-[300px] max-h-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 truncate pr-4">{{ $title }}</h3>
                    <button type="button" wire:click="{{ $closeAction }}"
                        class="text-gray-400 hover:text-gray-600 transition flex-shrink-0 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Completely Dynamic Form -->
                <form wire:submit.prevent="{{ $submitAction }}" class="flex-1 flex flex-col overflow-hidden">
                    <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 sm:space-y-6">
                        {{ $slot }}
                    </div>

                    <!-- Footer -->
                    <div
                        class="border-t border-gray-200 flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 p-4 sm:px-6 sm:py-4 flex-shrink-0 bg-gray-50/50">
                        <x-button flat label="Cancel" wire:click="{{ $closeAction }}"
                            class="w-full sm:w-auto px-4 py-2 order-2 sm:order-1" />
                        <x-button primary label="{{ $submitButton }}" type="submit" wire:loading.attr="disabled"
                            class="w-full sm:w-auto px-4 py-2 bg-gradient-3 hover:bg-gradient-3-hover order-1 sm:order-2" />
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
