<div>
    <section id="contact-us" class="relative py-20 bg-white overflow-hidden">
        <div
            class="absolute -top-32 left-1/2 -translate-x-1/2 w-[1000px] h-[600px] bg-blue-100 opacity-30 blur-[140px] rounded-full z-0">
        </div>

        <div class="relative z-10 max-w-6xl mx-auto px-4">
            <div class="grid md:grid-cols-2 gap-6 mb-12">
                <div>
                    <h2 class="text-4xl font-extrabold text-black mb-2">Contact Us</h2>
                    <h3 class="text-2xl font-bold text-black">Your Doubt Our Priority !</h3>
                </div>
            </div>

            <!-- resources/views/livewire/website/contact-form.blade.php -->
            <div x-data="{ success: @entangle('showSuccess') }">
                <form wire:submit.prevent="submit" class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-1 font-medium">Full Name</label>
                        <input type="text" wire:model.defer="full_name" placeholder="Enter your full name"
                            class="w-full bg-pink-100 text-black px-5 py-3 rounded-md border border-black shadow-md focus:outline-none" />
                        @error('full_name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium">School Name</label>
                        <input type="text" wire:model.defer="school_name" placeholder="Enter your school name"
                            class="w-full bg-pink-100 text-black px-5 py-3 rounded-md border border-black shadow-md focus:outline-none" />
                        @error('school_name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium">Phone</label>
                        <input type="text" wire:model.defer="phone" placeholder="Enter your phone number"
                            class="w-full bg-pink-100 text-black px-5 py-3 rounded-md border border-black shadow-md focus:outline-none" />
                        @error('phone')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 font-medium">E-mail</label>
                        <input type="email" wire:model.defer="email" placeholder="Enter your e-mail address"
                            class="w-full bg-pink-100 text-black px-5 py-3 rounded-md border border-black shadow-md focus:outline-none" />
                        @error('email')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="md:col-span-2 mt-6">
                        <button
                            class="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-4 rounded-[2rem] shadow-lg hover:opacity-90 transition text-lg font-medium">
                            Contact Us
                        </button>
                    </div>

                </form>
                <h1 class="text-xl text-center mt-8"> OR Contact Us Through Our official Email or WhatsApp.</h1>

                <div class="grid md:grid-cols-2 gap-4 pt-6 border-t border-gray-100 justify-between">
                    <div class="flex justify between items-center space-x-3">
                        <div class="bg-blue-100 p-2 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <a href="mailto:support@edyonelms.in"
                            class="text-gray-700 hover:text-purple-600 hover:underline">support@edyonelms.in</a>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-100 p-2 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <a href="tel:+919084748563" class="text-gray-700 hover:text-purple-600 hover:underline">+91
                            9084748563</a>
                    </div>
                </div>

                <div x-show="success" x-transition x-init="$watch('success', val => { if (val) setTimeout(() => success = false, 5000) })"
                    class="mt-6 md:col-span-2 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded relative text-center">
                    ✅ Your message has been submitted successfully!
                </div>

            </div>

        </div>
    </section>
</div>
