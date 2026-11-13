{{-- /web/demo/book/{token} (App\Http\Controllers\DemoBookingController): a website demo
     request picks its date and time, and gets its Meet link the moment it does.
     $demo, $slots [date => ['10:00', …]], $canBook, $settings, $platform, $logoUrl,
     $justBooked (booked | moved | null). Plain JS: the website pages load no Alpine. --}}
@php
    $status   = $demo->statusKey();
    $booked   = $status === 'booked' && $demo->meeting_at && $demo->meet_link;
    $upcoming = $booked && $demo->meeting_at->isFuture();
    $first    = trim((string) preg_split('/\s+/', trim((string) $demo->full_name))[0]) ?: 'there';
    $len      = (int) $settings['minutes'];
    $oldDate  = old('date');
    $pickDate = $oldDate && isset($slots[$oldDate]) ? $oldDate : array_key_first($slots);
    $showPicker = $canBook && (!$upcoming || $errors->any());
    $ics = $booked ? \App\Services\DemoMessages::ics($demo, $platform) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $booked ? 'Your demo is booked' : 'Pick a time for your demo' }} — {{ $platform }}</title>
    <link rel="icon" type="image/png" href="{{ asset('website-image/Group 11525.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'DM Sans', system-ui, sans-serif; }
        .brand-grad { background-image: linear-gradient(120deg, #DB57B2 0%, #6F56FE 100%); }
        .brand-text { background-image: linear-gradient(120deg, #DB57B2 0%, #6F56FE 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .day-btn[aria-pressed="true"] { border-color: #6F56FE; background: #F0EDFF; color: #2D1B4E; box-shadow: 0 0 0 1px #6F56FE inset; }
        .slot-btn[aria-pressed="true"] { border-color: #6F56FE; background: #6F56FE; color: #fff; }
        .days::-webkit-scrollbar { height: 6px; } .days::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 3px; }
    </style>
</head>
<body class="min-h-screen bg-[#FAFAFA] text-[#1A0F2E] antialiased">
    <header class="bg-white border-b border-gray-100">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ route('website.home') }}" class="flex items-center gap-2">
                <img src="{{ $logoUrl }}" alt="{{ $platform }}" class="h-9 w-auto">
            </a>
            <span class="text-xs sm:text-sm text-gray-500">Live demo · {{ $len }} minutes</span>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-12">

        {{-- ═══════════ BOOKED: the meeting and its link ═══════════ --}}
        @if ($booked)
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6" data-booked>
                <div class="brand-grad h-1.5"></div>
                <div class="p-6 sm:p-8 grid gap-6 md:grid-cols-[minmax(0,1fr)_16rem] md:items-center">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-11 h-11 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <div>
                                <h1 class="text-xl sm:text-2xl font-bold">
                                    @if (!$upcoming) Your demo was on @elseif ($justBooked === 'moved') Your demo has a new time @else You're booked, {{ $first }}! @endif
                                </h1>
                                <p class="text-sm text-gray-500">
                                    @if ($upcoming) The meeting link is below — we've sent it to {{ $demo->email }} and on WhatsApp too. @else Thank you for your time with us. @endif
                                </p>
                            </div>
                        </div>
                        <div class="rounded-xl bg-[#F7F4FF] border border-[#E4DEFF] px-4 py-3.5">
                            <p class="text-base sm:text-lg font-semibold text-[#2D1B4E]">{{ $demo->meeting_at->format('l, d F Y') }}</p>
                            <p class="text-sm text-[#6B5B8A]">{{ $demo->meeting_at->format('g:i A') }} – {{ $demo->meetingEnd()->format('g:i A') }} (IST)</p>
                            @if ($upcoming)
                                <p class="mt-2 text-sm break-all">
                                    <span class="text-gray-500">{{ $demo->meet_provider === 'google' ? 'Google Meet' : 'Meeting link' }}:</span>
                                    <a href="{{ $demo->meet_link }}" target="_blank" rel="noopener" class="text-[#6F56FE] font-medium hover:underline" data-meet-link>{{ $demo->meet_link }}</a>
                                </p>
                            @endif
                        </div>
                    </div>
                    @if ($upcoming)
                        <div class="flex flex-col gap-2">
                            <a href="{{ $demo->meet_link }}" target="_blank" rel="noopener"
                                class="brand-grad text-white text-sm font-semibold rounded-xl px-4 py-3 text-center shadow-sm hover:opacity-95">Join the meeting</a>
                            <button type="button" onclick="copyLink(this)" data-link="{{ $demo->meet_link }}"
                                class="text-sm font-semibold rounded-xl px-4 py-2.5 border border-gray-200 bg-white hover:bg-gray-50">Copy link</button>
                            <a href="{{ \App\Services\DemoMessages::googleCalendarUrl($demo) }}" target="_blank" rel="noopener"
                                class="text-sm font-semibold rounded-xl px-4 py-2.5 border border-gray-200 bg-white hover:bg-gray-50 text-center">Add to Google Calendar</a>
                            <a href="data:text/calendar;charset=utf-8,{{ rawurlencode($ics) }}" download="superlms-demo.ics"
                                class="text-sm font-medium text-gray-500 hover:text-gray-800 text-center py-1">Download invite (.ics)</a>
                            @if ($canBook)
                                <button type="button" onclick="document.getElementById('picker').classList.toggle('hidden'); document.getElementById('picker').scrollIntoView({behavior:'smooth'})"
                                    class="text-sm font-medium text-[#6F56FE] hover:underline py-1">Need another time?</button>
                            @endif
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- ═══════════ CLOSED ═══════════ --}}
        @if (!$booked && !$canBook)
            <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center max-w-xl mx-auto">
                <h1 class="text-xl font-bold mb-2">{{ $status === 'completed' ? 'Your demo is done' : 'This booking link is closed' }}</h1>
                <p class="text-sm text-gray-500 mb-6">{{ $status === 'completed' ? 'Thank you for your time with us. We will be in touch.' : 'This demo request was closed. You can request a new demo any time.' }}</p>
                <a href="{{ route('website.demo') }}" class="brand-grad inline-block text-white text-sm font-semibold rounded-xl px-5 py-3">Request a demo</a>
            </section>
        @endif

        {{-- ═══════════ PICK A TIME ═══════════ --}}
        @if ($canBook)
            <section id="picker" class="{{ $showPicker ? '' : 'hidden' }}">
                @unless ($booked)
                    <div class="mb-6 sm:mb-8">
                        <p class="text-sm font-semibold text-[#DB57B2] mb-1">Thank you for requesting a demo!</p>
                        <h1 class="text-2xl sm:text-3xl font-bold">Hi {{ $first }}, pick a time for your <span class="brand-text">live demo</span></h1>
                        <p class="mt-2 text-sm sm:text-base text-gray-500">A {{ $len }}-minute video call with our team{{ $demo->school_name ? ' for ' . $demo->school_name : '' }}. Your meeting link comes the moment you confirm.</p>
                    </div>
                @else
                    <h2 class="text-lg font-bold mb-4">Pick another time</h2>
                @endunless

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">{{ $errors->first() }}</div>
                @endif

                @if (empty($slots))
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                        <p class="font-semibold mb-1">No free times in the next {{ $settings['days_ahead'] }} days</p>
                        <p class="text-sm text-gray-500">Our team will call you on {{ $demo->phone }} to fix a time. Please check this link again later.</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('website.demo.book.store', $demo->booking_token) }}" id="book-form"
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden grid md:grid-cols-[minmax(0,1fr)_18rem]">
                        @csrf
                        <input type="hidden" name="date" id="f-date" value="{{ $pickDate }}">
                        <input type="hidden" name="time" id="f-time" value="{{ old('time') }}">

                        <div class="p-5 sm:p-6 md:border-r border-gray-100 min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">1 · Choose a day</p>
                            <div class="days flex md:grid md:grid-cols-4 lg:grid-cols-5 gap-2 overflow-x-auto md:overflow-visible pb-2 md:pb-0 -mx-1 px-1">
                                @foreach ($slots as $date => $times)
                                    @php $d = \Carbon\Carbon::parse($date); @endphp
                                    <button type="button" class="day-btn flex-shrink-0 w-[5.5rem] md:w-auto rounded-xl border border-gray-200 bg-white px-1 py-2.5 text-center hover:border-[#6F56FE] transition-colors"
                                        data-date="{{ $date }}" aria-pressed="{{ $date === $pickDate ? 'true' : 'false' }}" onclick="pickDay('{{ $date }}')">
                                        <span class="block text-[11px] font-semibold uppercase text-gray-500 whitespace-nowrap">{{ $d->isToday() ? 'Today' : ($d->isTomorrow() ? 'Tomorrow' : $d->format('D')) }}</span>
                                        <span class="block text-lg font-bold leading-6">{{ $d->format('j') }}</span>
                                        <span class="block text-[11px] text-gray-500">{{ $d->format('M') }} · {{ count($times) }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mt-6 mb-3">2 · Choose a time <span class="normal-case font-normal">(IST)</span></p>
                            @foreach ($slots as $date => $times)
                                <div class="slots grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-2 {{ $date === $pickDate ? '' : 'hidden' }}" data-slots="{{ $date }}">
                                    @foreach ($times as $t)
                                        <button type="button" class="slot-btn rounded-lg border border-gray-200 bg-white px-2 py-2 text-sm font-medium hover:border-[#6F56FE] transition-colors"
                                            data-time="{{ $t }}" aria-pressed="{{ $date === $pickDate && old('time') === $t ? 'true' : 'false' }}"
                                            onclick="pickTime('{{ $date }}', '{{ $t }}')">{{ \Carbon\Carbon::createFromFormat('H:i', $t)->format('g:i A') }}</button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        <div class="p-5 sm:p-6 bg-[#FAFAFE] flex flex-col">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Your demo</p>
                            <div class="flex-1 space-y-3 text-sm">
                                <div>
                                    <p class="text-gray-500 text-xs">When</p>
                                    <p class="font-semibold" id="summary">Pick a day and a time</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs">With</p>
                                    <p class="font-medium">{{ $platform }} team · video call</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs">For</p>
                                    <p class="font-medium break-words">{{ $demo->full_name }}@if ($demo->school_name), {{ $demo->school_name }}@endif</p>
                                </div>
                            </div>
                            <button type="submit" id="confirm" disabled
                                class="mt-5 w-full brand-grad text-white text-sm font-semibold rounded-xl px-4 py-3 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                {{ $booked ? 'Move my demo' : 'Confirm & get meeting link' }}
                            </button>
                            <p class="mt-2 text-[11px] text-gray-400 text-center">We'll send the link by email and WhatsApp.</p>
                        </div>
                    </form>
                @endif
            </section>
        @endif

        <p class="mt-10 text-center text-xs text-gray-400">© {{ now()->year }} {{ $platform }}</p>
    </main>

    <script>
        const fmt = (date, time) => {
            const d = new Date(date + 'T' + time + ':00');
            return d.toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long' }) + ' · ' +
                d.toLocaleTimeString('en-IN', { hour: 'numeric', minute: '2-digit', hour12: true }).toUpperCase();
        };
        function pickDay(date) {
            document.getElementById('f-date').value = date;
            document.getElementById('f-time').value = '';
            document.querySelectorAll('.day-btn').forEach(b => b.setAttribute('aria-pressed', b.dataset.date === date ? 'true' : 'false'));
            document.querySelectorAll('.slots').forEach(s => s.classList.toggle('hidden', s.dataset.slots !== date));
            document.querySelectorAll('.slot-btn').forEach(b => b.setAttribute('aria-pressed', 'false'));
            update();
        }
        function pickTime(date, time) {
            document.getElementById('f-date').value = date;
            document.getElementById('f-time').value = time;
            document.querySelectorAll('[data-slots="' + date + '"] .slot-btn').forEach(b => b.setAttribute('aria-pressed', b.dataset.time === time ? 'true' : 'false'));
            update();
        }
        function update() {
            const date = document.getElementById('f-date')?.value, time = document.getElementById('f-time')?.value;
            const btn = document.getElementById('confirm');
            if (!btn) return;
            btn.disabled = !(date && time);
            document.getElementById('summary').textContent = date && time ? fmt(date, time) : 'Pick a day and a time';
        }
        function copyLink(el) {
            navigator.clipboard?.writeText(el.dataset.link).then(() => {
                const t = el.textContent; el.textContent = 'Copied!'; setTimeout(() => el.textContent = t, 1500);
            });
        }
        document.getElementById('book-form')?.addEventListener('submit', e => {
            const btn = document.getElementById('confirm');
            btn.disabled = true; btn.textContent = 'Booking…';
        });
        update();
    </script>
</body>
</html>
