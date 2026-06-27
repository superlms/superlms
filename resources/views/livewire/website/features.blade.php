<div x-data="carouselComponent()" x-init="init()" class="bg-white">

    <!-- Features Section -->
    <section id="features" class="py-16 px-4">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl font-bold text-center mb-4">Top Features</h2>
            <p class="text-center text-gray-600 mb-8">
                EDUONE LMS streamlines institutional management with easy attendance tracking, fee handling, and
                timetable scheduling.
            </p>

            <div class="relative">
                <!-- Left Arrow -->
                <button @click="scroll(-1)"
                    class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full shadow">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Right Arrow -->
                <button @click="scroll(1)"
                    class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full shadow">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Scrollable Cards -->
                <div x-ref="carousel" class="flex overflow-x-auto scroll-smooth space-x-6 no-scrollbar px-6 py-4">
                    <template x-for="(item, i) in features" :key="i">
                        <div class="min-w-[250px] bg-white p-6 rounded-lg shadow-md text-center border border-gray-200">
                            <div class="icon bg-blue-100 p-3 rounded-lg inline-block mb-4">
                                <span class="text-blue-600 text-2xl" x-text="item.icon"></span>
                            </div>
                            <h3 class="text-xl font-bold" x-text="item.title"></h3>
                            <p class="text-gray-600 mt-2" x-text="item.desc"></p>
                        </div>
                    </template>
                </div>

                <!-- Dots -->
                <div class="flex justify-center mt-6 space-x-2">
                    <template x-for="(dot, i) in features" :key="i">
                        <div class="dot" :class="{ 'active': i === currentIndex }"></div>
                    </template>
                </div>
            </div>
        </div>
    </section>

    @livewire('website.pricing')

    <!-- Get Our Apps Section -->
    <section class="pb-16 bg-white">
        <div class="max-w-6xl mx-auto px-4">
            <div class="bg-gray-100 border border-gray-300 rounded-xl p-6 shadow-sm">
                <h2 class="text-5xl font-bold mb-6 leading-tight">
                    Get Our<br><span class="text-black-600">Apps</span>
                </h2>

                <div class="grid md:grid-cols-3 gap-6 items-center">
                    <div class="flex justify-center">
                        <img src="{{ asset('website-image/Rectangle 338.png') }}"
                            class="rounded shadow-md w-full max-w-xs">
                    </div>

                    <!-- App 1 -->
                    <div
                        class="bg-white rounded-xl p-6 shadow-[0_0_0_2px_#f472b6] text-center border border-gray-200 font-bold">
                        <div class="mb-4">
                            <img src="{{ asset('website-image/Group 11525.png') }}" class="mx-auto w-16 h-16">
                        </div>
                        <h3 class="text-xl mb-4 w-fit mx-auto">SUPERLMS</h3>
                        <a href="https://play.google.com/store/apps/details?id=com.edyoneapp" target="_blank"> <button
                                class="bg-gradient-to-r from-pink-500 to-purple-600 text-white py-2 rounded-full font-semibold px-6">
                                Get Now
                            </button>
                        </a>
                    </div>

                    <!-- App 2 -->
                    <div
                        class="bg-white rounded-xl p-6 shadow-[0_0_0_2px_#f472b6] text-center border border-gray-200 font-bold">
                        <div class="mb-4">
                            <img src="{{ asset('website-image/Vector 7.png') }}" class="mx-auto w-16 h-16">
                        </div>
                        <h3 class="text-xl mb-4 w-fit mx-auto">SUPERLMS</h3>
                        <button
                            class="bg-gradient-to-r from-pink-500 to-purple-600 text-white py-2 rounded-full font-semibold px-6">
                            Coming Soon
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Alpine Component Script -->
    <script>
        function carouselComponent() {
            const iconMap = {
                'Home': '🏠',
                'Standard': '📘',
                'Student & Teacher Modules': '👩‍🏫',
                'Student': '🧑‍🎓',
                'Teacher': '👨‍🏫',
                'Announcement': '📢',
                'Time Table & Arrangement': '📅',
                'Time Table': '📅',
                'Arrangement': '🪑',
                'Fee Management': '💳',
                'Homework': '📝',
                'Attendance': '✅',
                'Syllabus & Calendar': '📚',
                'Syllabus': '📖',
                'Calendar': '🗓️',
                'Rules & Regulations': '📜',
                'Content Management': '📂',
                'Performance & Analytics': '📈',
                'Performance': '📊',
                'Analytics': '📉',
                'Quiz': '❓',
                'Library': '🏛️',
                'Support': '🛠️',
                'ID Card & Admit Card': '🆔',
                'ID Card': '🆔',
                'Admit Card': '🎫',
                'Seating Plan & Exam Copy': '🪑',
                'Report Card': '📄',
                'Contact Admin': '☎️',
                'About App': 'ℹ️',
                'Upgrade Standard': '⬆️',
                'Rate LMS': '⭐',
                'Enquiries': '✉️'
            };

            const features = [{
                    title: 'Home',
                    desc: 'Your personalized dashboard for instant access to key updates, metrics, and learning tools.'
                },
                {
                    title: 'Standard',
                    desc: 'Class-wise content and user structure for organized academic workflows.'
                },
                {
                    title: 'Student & Teacher Modules',
                    desc: 'Dedicated portals designed for easy navigation, resource access, and performance tracking for both students and Educatorsss.'
                },
                {
                    title: 'Announcement',
                    desc: 'Broadcast important school-wide updates, circulars, and urgent notifications in real-time.'
                },
                {
                    title: 'Time Table & Arrangement',
                    desc: 'Automated schedule management with real-time substitution and class arrangements.'
                },
                {
                    title: 'Fee Management',
                    desc: 'A secure and simplified system for managing fee records, payments, and reminders.'
                },
                {
                    title: 'Homework',
                    desc: 'Assign, submit, and track homework digitally—making everyday learning more transparent.'
                },
                {
                    title: 'Attendance',
                    desc: 'Daily attendance tracking with smart insights to monitor student presence.'
                },
                {
                    title: 'Syllabus & Calendar',
                    desc: 'Access yearly curriculum plans and stay ahead with our smart academic calendar.'
                },
                {
                    title: 'Rules & Regulations',
                    desc: 'Built-in school policies and conduct guidelines for easy reference.'
                },
                {
                    title: 'Content Management',
                    desc: 'Upload and share study materials, presentations, videos, and digital notes with ease.'
                },
                {
                    title: 'Performance & Analytics',
                    desc: 'Advanced reporting tools to evaluate academic performance and identify learning gaps.'
                },
                {
                    title: 'Quiz',
                    desc: 'Engage students with interactive quizzes and instant evaluation.'
                },
                {
                    title: 'Library',
                    desc: 'Digitally manage library inventory and student book issues/returns.'
                },
                {
                    title: 'Support',
                    desc: 'Get help from our technical team for any issues related to usage or system errors.'
                },
                {
                    title: 'ID Card & Admit Card',
                    desc: 'Instantly generate ID cards and exam admit cards with just a few clicks.'
                },
                {
                    title: 'Seating Plan & Exam Copy',
                    desc: 'Organize seating arrangements and securely manage scanned exam copies for digital evaluation.'
                },
                {
                    title: 'Report Card',
                    desc: 'Auto-generate academic reports with comprehensive grading and teacher remarks.'
                },
                {
                    title: 'Contact Admin',
                    desc: 'Reach out to school administration for permissions, queries, or escalations.'
                },
                {
                    title: 'About App',
                    desc: 'Learn about the features, updates, and value SuperLMS brings to your institution.'
                },
                {
                    title: 'Upgrade Standard',
                    desc: 'Easily promote students to higher classes while preserving academic records.'
                },
                {
                    title: 'Rate LMS',
                    desc: 'Provide feedback and rate your experience to help us improve.'
                },
                {
                    title: 'Enquiries',
                    desc: 'Allow parents or prospective users to connect with the school for admissions or general queries.'
                }
            ].map(item => ({
                ...item,
                icon: iconMap[item.title] || '🌐' // Default fallback
            }));

            return {
                features,
                currentIndex: 0,
                cardWidth: 270,
                interval: null,
                scroll(dir) {
                    const total = this.features.length;
                    this.currentIndex = (this.currentIndex + dir + total) % total;
                    this.$refs.carousel.scrollTo({
                        left: this.currentIndex * this.cardWidth,
                        behavior: 'smooth'
                    });
                },
                init() {
                    this.interval = setInterval(() => this.scroll(1), 4000);
                }
            };
        }
    </script>

</div>
