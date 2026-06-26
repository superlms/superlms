<div>
    <!-- Hero Section -->
    <section class="relative py-12 px-4 pb-32 md:pb-48 lg:pb-64"
        style="background: linear-gradient(135deg, #fddde6, #e6c3ff, #ffe0c3);">

        <div class="max-w-6xl mx-auto flex flex-col-reverse lg:flex-row items-center gap-10 relative z-10">
            <!-- Left Text -->
            <div class="w-full lg:w-1/2 text-center lg:text-left">
                <p class="text-purple-800 font-medium mb-2">
                    The Leader in School Management System
                </p>
                <h1 class="text-4xl sm:text-3xl md:text-4xl font-bold text-purple-900 mb-4 leading-snug">
                    Engaging, Accessible<br />
                    & Affordable Learning<br />
                    Management System for<br />
                    Schools & Institutions
                </h1>
                <p class="text-purple-800 mb-4 text-xl md:text-base">
                    Trusted by over {{ $schoolCount }}+ schools across India
                </p>

                <div
                    class="flex flex-col sm:flex-row sm:justify-center lg:justify-start items-center gap-4 sm:gap-8 mb-14">
                    <div class="flex flex-col">
                        <span class="text-2xl md:text-3xl font-bold text-purple-900">{{ $schoolCount }}+</span>
                        <span class="text-gray-600 text-sm">Schools</span>
                    </div>
                    <div class="flex flex-col">
                        <div class="flex items-center justify-center lg:justify-start">
                            <span class="text-2xl md:text-3xl font-bold text-purple-900">{{ $avgRating }}</span>
                            <div class="flex ml-2 text-orange-500 text-xl">★★★★★</div>
                        </div>
                        <span class="text-gray-600 text-sm">Rated</span>
                    </div>
                </div>

                <button onclick="document.getElementById('contact-us').scrollIntoView({ behavior: 'smooth' })"
                    class="w-full sm:w-auto bg-gradient-to-r from-pink-500 to-purple-600 text-white px-8 py-4 rounded-full font-medium hover:opacity-90 transition">
                    Request Demo
                </button>
            </div>

            <!-- Right Image -->
            <div class="w-full lg:w-1/2 relative flex justify-center">
                <img src="{{ asset('website-image/object 1.png') }}" alt="Student reading"
                    class="relative z-10 max-w-full h-auto" />
                <div class="absolute top-0 right-0 w-24 sm:w-40 h-24 sm:h-40 bg-red-300 rounded-full opacity-70 z-0">
                </div>
                <div
                    class="absolute bottom-0 left-1/4 w-10 sm:w-16 h-10 sm:h-16 bg-yellow-300 rotate-45 opacity-70 z-0">
                </div>
                <div
                    class="absolute bottom-1/3 right-1/4 w-16 sm:w-24 h-16 sm:h-24 bg-blue-300 rounded-full opacity-70 z-0">
                </div>
            </div>
        </div>

        <!-- Overlapping Cards -->
        <div
            class="relative md:absolute md:-bottom-24 md:left-1/2 md:transform md:-translate-x-1/2 w-full max-w-6xl px-4 z-20 mt-10 md:mt-0">

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <!-- Card 1 -->
                <div class="bg-white shadow-lg rounded-xl p-6">
                    <div class="flex justify-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold">{{ $schoolCount }}+</h3>
                    <p class="text-gray-600">Partnered Schools</p>
                </div>

                <!-- Card 2 -->
                <div class="bg-white shadow-lg rounded-xl p-6">
                    <div class="flex justify-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a2 2 0 00-2-2h-3m-2 4h-5a2 2 0 01-2-2v-2m7-10V5a2 2 0 00-2-2h-2a2 2 0 00-2 2v3m7 0h-7m7 4H5" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold">1000+</h3>
                    <p class="text-gray-600">Subject Experts</p>
                </div>

                <!-- Card 3 -->
                <div class="bg-white shadow-lg rounded-xl p-6">
                    <div class="flex justify-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-yellow-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold">5+ Yrs.</h3>
                    <p class="text-gray-600">of Experience</p>
                </div>

                <!-- Card 4 -->
                <div class="bg-white shadow-lg rounded-xl p-6">
                    <div class="flex justify-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 9.143 15.429 12l1.429 4.857L12 14.143 7.143 16.857 8.571 12 3 9.143l5.714-2.286L11 3z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold">{{ number_format($studentCount) }}+</h3>
                    <p class="text-gray-600">Online Students</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Spacer section below for white background -->
    <section class="pt-40 md:pt-32 pb-12 bg-white"></section>
</div>
