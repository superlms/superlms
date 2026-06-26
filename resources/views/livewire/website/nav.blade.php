<!-- LMS Header Navigation -->
<div class="relative bg-gradient-to-b from-pink-100 to-purple-100 shadow-md">

    <div
        class="max-w-7xl mx-auto md:px-6  min-h-[100px] relative flex items-center md:justify-center justify-between">

        <div
            class="absolute left-4 bottom-[-36px] w-[64px] sm:w-[88px] h-[64px] sm:h-[88px] rounded-[20px] sm:rounded-[28px] border border-pink-300 bg-white shadow flex items-center justify-center z-30">
            <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo"
                class="w-[48px] sm:w-[70px] h-[48px] sm:h-[70px] object-contain">
        </div>

        <div class="hidden md:block text-center flex-grow">
            <div class="flex justify-center space-x-6 sm:space-x-8 font-bold text-blue-800 text-[15px] items-center">
                <a href="#"
                    class="relative after:content-[''] after:absolute after:h-[2px] after:w-6 after:bg-blue-800 after:left-1/2 after:-translate-x-1/2 after:-bottom-1">Home</a>
                <a href="#about-us" class="hover:underline">About Us</a>
                <a href="#features" class="hover:underline">Features</a>
                <a href="#pricing" class="hover:underline">Pricing</a>
                <a href="#contact-us" class="hover:underline">Contact Us</a>
                <a href="/web/terms-conditions" target="_blank" class="hover:underline">Terms & Conditions</a>
            </div>
        </div>

        <div class="md:hidden z-30 ml-auto">
            <button wire:click="$toggle('mobileMenuOpen')" class="text-purple-700 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <div class="w-full h-[4px] bg-gradient-to-r from-fuchsia-500 via-pink-500 to-purple-500 mt-[-4px] z-0"></div>

    @if ($mobileMenuOpen)
        <div class="bg-pink-100 py-4 px-6 md:hidden transition-all duration-200">
            <div class="flex flex-col space-y-4 font-bold text-blue-800 text-sm">
                <a href="#" class="hover:underline">Home</a>
                <a href="#about-us" class="hover:underline">About Us</a>
                <a href="#features" class="hover:underline">Features</a>
                <a href="#pricing" class="hover:underline">Pricing</a>
                <a href="#contact-us" class="hover:underline">Contact Us</a>
                <a href="/web/terms-conditions" target="_blank" class="hover:underline">Terms & Conditions</a>
            </div>
        </div>
    @endif

    <style>
        html {
            scroll-behavior: smooth;
        }
    </style>
</div>
