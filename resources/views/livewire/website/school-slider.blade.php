<!-- School/Partner Slider -->
<section class="py-16 px-4 bg-white">
    <div class="max-w-6xl mx-auto relative">

        <!-- Heading -->
        <h2 class="text-3xl font-bold text-center mb-10">Trusted By many Partners</h2>

        <!-- Carousel Wrapper -->
        <div x-data="{
            scroll: null,
            interval: null,
            cardWidth: 160,
            total: {{ $organizations->count() }},
            currentIndex: 0,
            startAutoScroll() {
                this.interval = setInterval(() => {
                    this.scrollRight();
                }, 5000);
            },
            scrollRight() {
                if (this.scroll) {
                    this.currentIndex = (this.currentIndex + 1) % this.total;
                    const scrollAmount = this.cardWidth;
                    const maxScroll = this.scroll.scrollWidth - this.scroll.clientWidth;
                    const newScrollLeft = Math.min(this.scroll.scrollLeft + scrollAmount, maxScroll);
                    this.scroll.scrollTo({ left: newScrollLeft, behavior: 'smooth' });
                }
            },
            scrollLeft() {
                if (this.scroll) {
                    this.currentIndex = (this.currentIndex - 1 + this.total) % this.total;
                    const scrollAmount = this.cardWidth;
                    const newScrollLeft = Math.max(this.scroll.scrollLeft - scrollAmount, 0);
                    this.scroll.scrollTo({ left: newScrollLeft, behavior: 'smooth' });
                }
            },
            setDot(index) {
                const dots = document.querySelectorAll('.dot');
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === index);
                });
            },
            centerItems() {
                if (this.scroll) {
                    const containerWidth = this.scroll.clientWidth;
                    const contentWidth = this.total * this.cardWidth;
                    if (contentWidth < containerWidth) {
                        // Center all items if they fit in container
                        this.scroll.scrollLeft = (contentWidth - containerWidth) / 2;
                    } else {
                        // Center current item
                        const itemCenter = (this.currentIndex * this.cardWidth) + (this.cardWidth / 2);
                        this.scroll.scrollLeft = itemCenter - (containerWidth / 2);
                    }
                }
            },
            initCarousel() {
                this.scroll = $refs.scroll;
                this.setDot(0);
                this.startAutoScroll();
        
                // Center items on initialization
                this.$nextTick(() => {
                    this.centerItems();
                });
        
                // Re-center on window resize
                window.addEventListener('resize', () => {
                    this.centerItems();
                });
            }
        }" x-init="initCarousel()" class="relative">

            <!-- Left Arrow -->
            <button @click="scrollLeft(); setDot(currentIndex); centerItems();"
                class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full shadow hidden md:block">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Right Arrow -->
            <button @click="scrollRight(); setDot(currentIndex); centerItems();"
                class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full shadow hidden md:block">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Scrollable Logos -->
            <div x-ref="scroll" id="carousel"
                class="flex overflow-x-auto scroll-smooth space-x-8 no-scrollbar px-2 py-4 touch-pan-x cursor-grab select-none"
                @scroll.debounce.500ms="currentIndex = Math.round(scroll.scrollLeft / cardWidth); setDot(currentIndex);">
                @foreach ($organizations as $org)
                    <div class="min-w-[140px] flex justify-center items-center px-2">
                        <div
                            class="w-32 h-32 rounded-full bg-white flex items-center justify-center shadow-md hover:shadow-lg transition-shadow">
                            <img class="w-24 h-24 object-contain p-2"
                                src="{{ $org->logo ? asset($org->logo) : asset('website-image/Vector 215.png') }}"
                                alt="{{ $org->name }}"
                                onerror="this.onerror=null; this.src='{{ asset('website-image/Vector 215.png') }}';" />
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Dots -->
            @if ($organizations->count() > 1)
                <div class="flex justify-center mt-6 space-x-2">
                    @foreach ($organizations as $index => $org)
                        <div
                            class="dot w-3 h-3 rounded-full bg-gray-300 transition-all duration-300 @if ($index === 0) active bg-blue-500 w-6 @endif">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
