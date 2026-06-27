<div>
    <div x-data="{
        steps: [{
                title: 'Seamless School Integration',
                desc: 'We partner directly with your school to set up a personalized digital learning environment.',
                svg: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 12h14M12 5l7 7-7 7' />`,
                color: 'bg-green-400',
                rotate: 'transform -rotate-12'
            },
            {
                title: 'Get Your LMS Account',
                desc: 'Each School, Student and Teacher receives a secure LMS login to access SuperLMS.',
                svg: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v8 m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1' />`,
                color: 'bg-yellow-400',
                rotate: ''
            },
            {
                title: 'Start Your Learning Journey',
                desc: 'Explore lessons, take assessments, and track progress — all through one platform.',
                svg: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4 m6 2a9 9 0 11-18 0 9 9 0 0118 0z' />`,
                color: 'bg-purple-400',
                rotate: 'transform rotate-12'
            }
        ]
    }">
        <!-- How It Works Section -->
        <section class="py-24 bg-white relative overflow-hidden">
            <div class="max-w-6xl mx-auto px-4 relative z-10">
                <h2 class="text-3xl font-bold text-center mb-2">How It Works!</h2>
                <p class="text-center text-gray-600 mb-16">
                    See How Our LMS Bridges Technology with Effective Learning
                </p>

                <svg class="absolute top-[180px] left-0 right-0 z-0 w-full h-40" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0,80 C400,20 800,140 1200,80 S1800,120 2200,80" stroke="#64748b" stroke-width="3"
                        stroke-dasharray="6,8" fill="none" />
                </svg>

                <div class="flex flex-col md:flex-row justify-between items-center relative z-10 gap-16">
                    <template x-for="(step, i) in steps" :key="i">
                        <div class="w-full md:w-1/3 flex flex-col items-center text-center px-4">
                            <div class="relative mb-6">
                                <div class="bg-pink-200 rounded-full w-16 h-16 absolute -bottom-2 -right-2"></div>
                                <div :class="step.rotate"
                                    class="bg-black rounded-xl w-24 h-36 flex items-center justify-center relative z-10">
                                    <div :class="step.color"
                                        class="w-10 h-10 rounded flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" class="h-6 w-6 text-white" x-html="step.svg"></svg>
                                    </div>
                                </div>
                            </div>
                            <h3 class="text-xl font-bold mb-2" x-text="step.title"></h3>
                            <p class="text-gray-600" x-text="step.desc"></p>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- About Us & What We Do -->
        <section id="about-us" class="py-24 bg-gradient-to-b from-white via-purple-50 to-white">
            <div class="max-w-6xl mx-auto px-4 space-y-24">
                <!-- About Us -->
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-4xl font-bold text-gray-900 mb-2">About Us.</h2>
                        <h3 class="text-2xl font-semibold text-gray-800 mb-6 leading-snug">
                            Your All-In-One Solution For <br /> Learning And Management.
                        </h3>
                        <p class="text-gray-700 mb-6 leading-relaxed">
                            At SuperLMS, we offer a comprehensive suite of services designed to streamline school
                            management and enhance the learning experience for students, teachers, and administrators.
                            The platform opens with a personalized Home Dashboardthat centralizes updates and quick
                            access to features. Our Standard module organizes users by class levels, while
                            dedicated Student and Teacher portals provide tailored experiences for learning and
                            teaching. Important updates are shared through the Announcement system, and academic
                            planning is simplified with dynamic Time Tables and Arrangement features. Schools can manage
                            tuition efficiently using our integrated Fee Management module, assign and track tasks with
                            the Homework feature, and ensure accurate Attendance tracking.
                            The Syllabus and Calendar modules help users stay aligned with academic goals and schedules,
                            while Rules & Regulations provide easy access to school policies.
                            Our Content section enables seamless sharing of digital learning materials, and
                            detailed Performance and Analyticstools help monitor academic progress. Students can
                            participate in interactive assessments using the Quiz feature, while the Library module
                            manages book inventories digitally.
                        </p>
                        <button onclick="document.getElementById('contact-us').scrollIntoView({ behavior: 'smooth' })"
                            class="w-full sm:w-auto bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-2 rounded-full font-medium hover:opacity-90 transition">
                            Request Demo
                    </div>
                    <div>
                        <img src="{{ asset('website-image/Rectangle 318.png') }}" alt="About Us"
                            class="rounded-xl shadow-xl w-full h-auto object-cover" />
                    </div>
                </div>

                <!-- What We Do -->
                <div
                    class="bg-white shadow-xl rounded-xl p-6 md:p-10 grid md:grid-cols-3 gap-6 items-start border border-black">
                    <div class="bg-white rounded-lg p-6 shadow-md border min-h-[300px]">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-lg font-bold">Streamlines Academic Management:</h4>
                            <img src="{{ asset('website-image/Vector 215.png') }}" alt="Icon"
                                class="w-8 h-8 rounded-full shadow-lg ml-4" />
                        </div>
                        <p class="text-gray-600 text-sm">
                            SUPERLMS simplifies daily academic operations with attendance tracking, timetable
                            scheduling, fee management, and note sharing.
                        </p>
                    </div>

                    <div class="bg-white rounded-lg p-6 shadow-md border min-h-[300px]">
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <h4 class="text-lg font-bold flex-1">Enhances Learning Experience:</h4>
                            <img src="{{ asset('website-image/Vector 215.png') }}" alt="Icon"
                                class="w-8 h-8 object-contain shrink-0" />
                        </div>
                        <p class="text-gray-600 text-sm">
                            With real-time progress analysis, resource sharing, and interactive tools, SUPERLMS
                            empowers both Educatorsss and learners digitally.
                        </p>
                    </div>


                    <div class="flex flex-col items-center text-center">
                        <h2 class="text-4xl font-extrabold text-gray-800 leading-tight mb-4">
                            What<br />We Do.
                        </h2>
                        <img src="{{ asset('website-image/Rectangle 18.png') }}" alt="What We Do"
                            class="rounded-xl shadow-xl w-full h-auto object-cover" />
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
