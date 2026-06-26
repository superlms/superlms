   <div>
       {{-- Navigation Bar --}}
       @livewire('website.nav')

       {{-- Hero Section --}}
       @livewire('website.hero-section')

       @livewire('website.school-slider')

       {{-- About Us --}}
       @livewire('website.about-us')

       {{-- Feautures Section --}}
       @livewire('website.features')

       <!-- Social Media Sidebar -->
       <div class="fixed left-0 top-1/3 z-50">
           <div
               class="flex flex-col space-y-3 bg-white p-4 rounded-r-lg shadow-md border-l-4 border-pink-500 shadow-pink-glow">

               <a href="https://www.instagram.com/edyonelms?utm_source=ig_web_button_share_sheet&igsh=ZzlkamE5ZTR5MDB6"
                   target="_blank" rel="noopener noreferrer">
                   <img src="https://img.icons8.com/fluency/48/instagram-new.png" alt="Instagram" class="w-8 h-8" />
               </a>

               <a href="https://whatsapp.com/channel/0029Vb6myRCKGGGKSCgVFP0M" target="_blank" rel="noopener noreferrer">
                   <img src="https://img.icons8.com/color/48/whatsapp--v1.png" alt="WhatsApp" class="w-8 h-8" />
               </a>

               <a href="https://youtube.com/@edyonelms?si=SqStdSrTtJ95j8tP" target="_blank" rel="noopener noreferrer">
                   <img src="https://img.icons8.com/color/48/youtube-play.png" alt="YouTube" class="w-8 h-8" />
               </a>

           </div>
       </div>

       {{-- Terms and Conditon --}}
       @livewire('website.terms-condition')
       <!-- Contact Us section -->
       @livewire('website.contact-us')

       <section x-data="testimonialSlider()" x-init="init()"
           class="relative py-20 bg-gradient-to-br from-white to-purple-50 overflow-hidden">
           <div class="max-w-6xl mx-auto px-4 grid md:grid-cols-2 gap-12 items-center relative z-10">
               <!-- Left Content -->
               <div>
                   <p class="text-sm font-semibold text-indigo-600 mb-2">Testimonials</p>
                   <h2 class="text-4xl font-extrabold text-gray-900 mb-4 leading-snug">
                       Why Our Learners<br />
                       <span class="text-indigo-600">‘Appreciate’</span> Us
                   </h2>
                   <p class="text-gray-600 text-base leading-relaxed">
                       With features like easy course access, interactive content, real-time progress tracking, and
                       anytime-anywhere availability, learners feel supported and motivated throughout their educational
                       journey.
                   </p>
               </div>

               <!-- Right: Vertical Scroll Container -->
               <div class="relative h-[280px] overflow-y-auto scrollbar-hide" x-ref="container">
                   <div class="transition-all duration-700 ease-in-out space-y-6" x-ref="track">
                       <template x-for="(item, index) in visibleTestimonials" :key="index">
                           <div class="testimonial bg-white rounded-xl p-6 shadow-md border border-gray-200">
                               <p class="text-gray-600 mb-4" x-text="item.feedback"></p>
                               <div class="flex items-center gap-4">
                                   <!-- School Logo: show image if available, else show initials avatar -->
                                   <template x-if="item.logo">
                                       <img :src="item.logo" :alt="item.school_name"
                                           class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0" />
                                   </template>
                                   <template x-if="!item.logo">
                                       <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                           <span class="text-indigo-600 font-bold text-sm" x-text="item.initials"></span>
                                       </div>
                                   </template>
                                   <div>
                                       <p class="font-semibold text-black" x-text="item.school_name"></p>
                                       <div class="text-yellow-400 text-sm mt-1" x-html="getStars(item.rating)"></div>
                                   </div>
                               </div>
                           </div>
                       </template>
                   </div>
               </div>
           </div>

           <!-- Decorative Glow -->
           <div
               class="absolute -top-40 -right-40 w-[600px] h-[600px] bg-purple-100 rounded-full blur-[120px] opacity-50 z-0">
           </div>
       </section>

       <!-- CSS: Hide Scrollbar -->
       <style>
           .scrollbar-hide {
               scrollbar-width: none;
               -ms-overflow-style: none;
           }

           .scrollbar-hide::-webkit-scrollbar {
               display: none;
           }
       </style>

       <!-- Alpine.js Logic -->
       <script>
           function testimonialSlider() {
               return {
                   testimonials: [],
                   visibleTestimonials: [],
                   scrollIndex: 0,
                   scrollEvery: 5000,
                   container: null,
                   track: null,

                   async init() {
                       this.container = this.$refs.container;
                       this.track = this.$refs.track;

                       try {
                           const res = await fetch(‘/api/website/testimonials’);
                           const json = await res.json();
                           if (json.success && json.data.length) {
                               this.testimonials = json.data;
                           }
                       } catch (e) {
                           console.error(‘Failed to load testimonials’, e);
                       }

                       if (this.testimonials.length) {
                           // clone for infinite scroll loop
                           this.visibleTestimonials = [...this.testimonials, ...this.testimonials];
                           setInterval(() => this.autoScroll(), this.scrollEvery);
                       }
                   },

                   autoScroll() {
                       const children = this.track.children;
                       if (this.scrollIndex + 1 >= children.length) return;

                       const next = children[this.scrollIndex + 1];
                       this.container.scrollTo({
                           top: next.offsetTop,
                           behavior: ‘smooth’
                       });

                       this.scrollIndex++;

                       if (this.scrollIndex >= this.testimonials.length) {
                           setTimeout(() => {
                               this.container.scrollTo({ top: 0 });
                               this.scrollIndex = 0;
                           }, 700);
                       }
                   },

                   getStars(rating) {
                       const full = ‘★’.repeat(rating);
                       const empty = ‘☆’.repeat(5 - rating);
                       return `<span>${full}${empty}</span>`;
                   }
               }
           }
       </script>


       <!-- FAQ Section -->
       <section class="relative bg-white py-20 px-4" x-data="faqSection()">

           <div
               class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] h-[400px] bg-pink-100 rounded-full blur-3xl opacity-50 z-0">
           </div>

           <!-- FAQ Card Content -->
           <div class="max-w-5xl mx-auto relative z-10">
               <div class="bg-white rounded-2xl border border-gray-200 shadow-lg p-8">

                   <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8">Frequently Asked Questions</h2>

                   <!-- FAQ List -->
                   <template x-for="(faq, index) in faqs" :key="index">
                       <div class="mb-4 border-b border-gray-200 pb-4">
                           <div class="flex justify-between items-center cursor-pointer"
                               @click="open === index ? open = null : open = index">
                               <p class="font-semibold text-gray-800 text-base md:text-lg" x-text="faq.question"></p>
                               <button
                                   class="w-6 h-6 flex items-center justify-center rounded-full bg-blue-500 text-white transition-transform duration-300"
                                   :class="{ 'rotate-180': open === index }">
                                   <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"
                                       viewBox="0 0 24 24">
                                       <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                   </svg>
                               </button>
                           </div>
                           <div x-show="open === index" x-collapse
                               class="mt-3 text-gray-600 text-sm md:text-base leading-relaxed" x-text="faq.answer">
                           </div>
                       </div>
                   </template>

               </div>
           </div>
       </section>

       <script>
           function faqSection() {
               return {
                   open: null,
                   faqs: [{
                           question: "1. What is EDYONE LMS, and who is it designed for?",
                           answer: "Edyone LMS is a comprehensive Learning Management System built specifically for schools and educational institutions. It is designed to serve students, teachers, and administrators by offering tools for digital learning, academic management, communication, and performance tracking."
                       },
                       {
                           question: "2. How easy is it to get started with EDYONE LMS?",
                           answer: "Getting started with Edyone LMS is quick and seamless. Once your school is onboarded, each user receives login credentials and guided support to begin using the platform. The user-friendly interface ensures that even first-time users can navigate it effortlessly."
                       },
                       {
                           question: "3. Can EDYONE LMS be accessed on mobile devices?",
                           answer: "Yes, Edyone LMS is fully mobile-compatible. Students, teachers, and parents can access all features via smartphones and tablets, making learning and school management accessible anytime, anywhere."
                       },
                       {
                           question: "4. Does EDYONE LMS support attendance and fee management?",
                           answer: "Absolutely. Edyone LMS includes powerful modules for real-time attendance tracking and a secure, transparent fee management system that simplifies payments, receipts, and record-keeping."
                       },
                       {
                           question: "5. Is EDYONE LMS customizable to suit specific institutional needs?",
                           answer: "Yes, Edyone LMS is highly customizable. Schools can tailor modules, permissions, design layouts, and workflows according to their academic structure and operational requirements."
                       },
                       {
                           question: "6. What security measures does EDYONE LMS have in place?",
                           answer: "Edyone LMS uses encrypted user authentication, secure cloud hosting, and role-based access control to ensure data privacy and system security. Regular backups and compliance with data protection standards further strengthen platform safety."
                       },
                       {
                           question: "7. Can teachers easily share study materials and notes through EDYONE LMS?",
                           answer: "Yes, teachers can upload and share documents, presentations, videos, and notes with students directly through the platform. These materials can be accessed by students anytime for continued learning and revision."
                       }
                   ]
               };
           }
       </script>

       <style>
           .shadow-pink-glow {
               box-shadow: -4px 0 15px rgba(236, 72, 153, 0.5);
           }
       </style>


       {{-- Footer --}}
       @livewire('website.footer')
   </div>
