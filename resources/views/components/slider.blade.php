@props([
    'show' => false,
    'title' => 'Slider',
    'position' => 'right',
    'width' => '96',
    'closeAction' => 'closeSlider',
])

<div x-data="{
    show: @entangle('show'),
    close() {
        @this.call('{{ $closeAction }}')
    }
}" x-show="show" x-cloak x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="fixed inset-0 z-[100]">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" x-show="show" @click="close()"></div>

    <!-- Slider Panel -->
    <div class="fixed {{ $position === 'right' ? 'right-0' : 'left-0' }} top-0 h-full w-{{ $width }} bg-white shadow-xl"
        x-show="show" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="{{ $position === 'right' ? 'translate-x-full' : '-translate-x-full' }}"
        x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="{{ $position === 'right' ? 'translate-x-full' : '-translate-x-full' }}">

        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-white">
            <h3 class="text-lg font-semibold text-gray-800">{{ $title }}</h3>
            <button @click="close()" class="text-gray-400 hover:text-gray-600 transition duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="h-full overflow-y-auto">
            {{ $slot }}
        </div>
    </div>
</div>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
