{{--
    Vibrant's home page, from top to bottom: a centred headline over soft
    colour with a call-back pill, and bento cards (about, the team in orbit,
    a page finder over a dotted wave, the admission card); the welcome with
    the slider framed and the school's figures; the facility cards to swipe;
    what the school stands for, numbered; messages from the leadership in a
    ring of faces; upcoming events; a photo mosaic; the admission card with
    its checklist; and the map. Every part reads Classic's content and shows
    only when the school has it.
--}}
@extends('school-site.vibrant.layout')
@php
    use App\Models\SchoolWebsite;
    use App\Support\ModernSite;
    use Illuminate\Support\Str;

    $stored     = $site->content ?? [];
    $pal        = array_merge([$theme['primary']], ModernSite::PALETTE);
    $colour     = fn ($i) => $pal[$i % count($pal)];
    $on         = fn ($slug) => $site->isPageEnabled($slug);
    $slides     = ModernSite::slides($c);
    $pool       = ModernSite::photos($c);
    $facilities = ModernSite::facilities($c);
    $messages   = ModernSite::messages($site, $c);
    $stats      = ModernSite::stats($c, $lms);
    $points     = ModernSite::points($site, $c);
    $faces      = ModernSite::faces($c, 3);
    $range      = ModernSite::classRange($lms, $c);
    $events     = ModernSite::upcoming($lms->events(), 3);
    $gallery    = array_slice(ModernSite::gallery($c), 0, 5);
    $tel        = ModernSite::tel($c);
    $mapSrc     = SchoolWebsite::mapSrc($c);
    $welcome    = !empty($stored['about_heading']) ? $c['about_heading'] : 'Welcome to ' . $c['school_name'];
    $lead       = trim((string) ($stored['tagline'] ?? ''));
    $board      = trim((string) ($c['board'] ?? ''));
    $session    = trim((string) ($c['admission_session'] ?? ''));
    $badge      = $session !== '' ? 'Admissions open · ' . $session
                : ($board !== '' ? $board . ' affiliated school' : (trim((string) ($c['tagline'] ?? '')) ?: 'Quality education'));
    $apply      = $on('admission') ? url('admission') : ($on('contact') ? url('contact') : null);
    $docs       = array_values(array_filter(array_map(fn ($d) => trim((string) ($d['text'] ?? '')), $c['documents_required'] ?? [])));
    $steps      = array_values(array_filter($c['admission_steps'] ?? [], fn ($s) => trim((string) ($s['title'] ?? '')) !== ''));

    // The team card's orbit: the team's (else the leaders') faces, else the facilities' icons.
    $orbit = [];
    foreach (array_merge($c['team'] ?? [], $c['leadership'] ?? []) as $m) {
        if (count($orbit) >= 6) { break; }
        if (trim((string) ($m['name'] ?? '')) !== '') {
            $orbit[] = ['photo' => trim((string) ($m['photo'] ?? '')), 'label' => trim($m['name']), 'emoji' => null];
        }
    }
    $orbitPeople = (bool) $orbit;
    if (! $orbit) {
        foreach (array_slice($facilities, 0, 6) as $f) {
            $orbit[] = ['photo' => '', 'label' => $f['title'] ?? '', 'emoji' => $f['icon'] ?? '✦'];
        }
    }
    $spots = [[14, 64], [24, 24], [50, 10], [76, 24], [86, 64], [50, 90]];
    $initials = fn ($name) => mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/u', trim($name)), 0, 2))));

    // The finder: every page in the menu, by its name.
    $finder = [];
    foreach ($nav as $items) {
        foreach ($items as $slug => $pageName) {
            $finder[$pageName] = $slug === 'home' ? url('/') : url($slug);
        }
    }

    // A wave of dots for the finder card, drawn once here.
    $wave = '';
    for ($j = 0; $j < 12; $j++) {
        for ($i = 0; $i < 30; $i++) {
            $u = $i / 29; $v = $j / 11;
            $y = 34 + $v * 104 + sin($u * 7.2 + $v * 2.4) * (8 + 16 * $v) - cos($u * 3.1 + $v * 1.3) * 7;
            $wave .= sprintf('<circle cx="%.1f" cy="%.1f" r="%.2f"/>', 4 + $u * 292, $y, 0.55 + $v * 1.55);
        }
    }

    // The ring of faces around the leadership's words.
    $ringFaces = [];
    foreach (array_merge($messages, $c['team'] ?? []) as $m) {
        $p = trim((string) ($m['photo'] ?? ''));
        if ($p !== '' && ! isset($ringFaces[$p])) { $ringFaces[$p] = ['photo' => $p, 'role' => trim((string) ($m['role'] ?? ''))]; }
    }
    $ringFaces = array_slice(array_values($ringFaces), 0, 6);
    $ringSpots = [[8, 34], [20, 82], [88, 26], [94, 70], [36, 4], [70, 96]];
@endphp

@push('styles')
<style>
    /* ══════════ HERO ══════════ */
    .v-hero { position: relative; padding: 52px 0 34px; isolation: isolate; overflow: hidden; }
    .v-hero::before { content: ''; position: absolute; inset: -10% -5% 30%; z-index: -1;
        background:
            radial-gradient(40% 50% at 12% 20%, color-mix(in srgb, var(--primary) 20%, transparent), transparent 70%),
            radial-gradient(34% 46% at 88% 14%, rgba(236,72,153,.16), transparent 70%),
            radial-gradient(30% 40% at 70% 70%, rgba(245,158,11,.14), transparent 70%),
            radial-gradient(30% 40% at 25% 80%, rgba(6,182,212,.12), transparent 70%); }
    .v-hero::after { content: ''; position: absolute; inset: 0; z-index: -1; background-image: radial-gradient(rgba(15,23,42,.07) 1px, transparent 1.2px); background-size: 26px 26px; mask-image: radial-gradient(60% 60% at 50% 30%, #000, transparent); -webkit-mask-image: radial-gradient(60% 60% at 50% 30%, #000, transparent); }
    .v-hero-text { max-width: 1040px; margin: 0 auto; text-align: center; display: flex; flex-direction: column; align-items: center; }
    .v-pill { display: inline-flex; align-items: center; gap: 10px; padding: 5px 16px 5px 5px; border-radius: 999px; background: #fff; border: 1px solid var(--line); box-shadow: 0 8px 24px -14px rgba(15,23,42,.3); font-size: 12.5px; font-weight: 700; color: var(--text); animation: v-rise .8s cubic-bezier(.16,1,.3,1) both; }
    .v-faces { display: flex; }
    .v-faces .ph { width: 30px; height: 30px; border-radius: 50%; border: 2px solid #fff; margin-left: -9px; }
    .v-faces .ph:first-child { margin-left: 0; }
    .v-spark { width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; background: var(--grad); color: #fff; }
    .v-spark .ico { width: 15px; height: 15px; }
    .v-hero h1 { margin: 22px 0 16px; font-size: clamp(34px, 5.4vw, 66px); line-height: 1.05; text-wrap: balance; font-weight: 800; letter-spacing: -.045em; color: var(--ink); animation: v-rise .9s cubic-bezier(.16,1,.3,1) both .08s; }
    .v-sub { max-width: 660px; font-size: clamp(15px, 1.5vw, 18px); color: var(--muted); animation: v-rise .9s cubic-bezier(.16,1,.3,1) both .16s; }
    .sform.v-quick { width: 100%; max-width: 600px; margin-top: 28px; animation: v-rise .9s cubic-bezier(.16,1,.3,1) both .24s; }
    .v-quick-row { display: flex; align-items: center; gap: 4px; padding: 6px; border-radius: 999px; background: #fff; border: 1px solid var(--line); box-shadow: 0 22px 44px -24px rgba(15,23,42,.35); }
    .sform.v-quick input { flex: 1; min-width: 0; padding: 10px 16px; border: 0; border-radius: 999px; background: transparent; box-shadow: none; font-size: 14px; font-weight: 600; }
    .sform.v-quick input:focus { box-shadow: none; background: var(--page); }
    .v-quick-row i { width: 1px; height: 24px; background: var(--line); flex: none; }
    .v-quick .btn { flex: none; background: var(--grad); }
    .v-quick .sform-msg { margin-top: 10px; text-align: center; min-height: 1em; }

    /* ══════════ BENTO ══════════ */
    .v-bento { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); grid-template-areas: "about team find" "cta cta find"; gap: 18px; margin-top: 46px; }
    .b-card { position: relative; overflow: hidden; display: flex; flex-direction: column; padding: 22px; border-radius: 28px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); }
    .b-card h3 { font-size: 21px; line-height: 1.2; font-weight: 800; color: var(--ink); letter-spacing: -.02em; }
    .b-card > p { margin-top: 6px; font-size: 13.5px; line-height: 1.55; color: var(--muted); }
    .b-tag { align-self: flex-start; display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 999px; font-size: 11px; font-weight: 800; letter-spacing: .04em; background: color-mix(in srgb, var(--primary) 9%, #fff); color: var(--primary); margin-bottom: 10px; }
    .b-tag .ico { width: 12px; height: 12px; }
    .b-about { grid-area: about; }
    .b-about .ph { margin-top: 16px; flex: 1; min-height: 160px; border-radius: 20px; }
    .b-chips { position: absolute; left: 32px; right: 32px; bottom: 32px; display: flex; gap: 6px; overflow: hidden; }
    .b-chips span { padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.82); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); font-size: 11.5px; font-weight: 700; color: var(--ink); white-space: nowrap; }
    .b-chips span:nth-child(2) { background: #fff; box-shadow: 0 6px 16px rgba(0,0,0,.14); }
    .b-team { grid-area: team; }
    .orbit { position: relative; flex: 1; min-height: 200px; margin-top: 8px; }
    .orbit .lines { position: absolute; inset: 0; width: 100%; height: 100%; }
    .orbit .lines line { stroke: color-mix(in srgb, var(--primary) 30%, #E2E8F0); stroke-width: .5; stroke-dasharray: 1.4 1.6; }
    .orbit .o { position: absolute; width: 46px; height: 46px; margin: -23px 0 0 -23px; border-radius: 50%; border: 3px solid #fff; overflow: hidden; display: grid; place-items: center; background: var(--c); color: #fff; font-size: 14px; font-weight: 800; box-shadow: 0 8px 18px -6px rgba(15,23,42,.3); animation: v-float 6s ease-in-out infinite; animation-delay: calc(var(--i) * -.9s); }
    .orbit .o.emoji { font-size: 21px; background: color-mix(in srgb, var(--c) 16%, #fff); }
    .orbit .o .ph { width: 100%; height: 100%; }
    .orbit .o-center { position: absolute; left: 50%; top: 52%; width: 78px; height: 78px; margin: -39px 0 0 -39px; border-radius: 50%; padding: 10px; background: #fff; box-shadow: 0 0 0 8px color-mix(in srgb, var(--primary) 8%, transparent), 0 14px 30px -10px rgba(15,23,42,.3); display: grid; place-items: center; }
    .orbit .o-center img, .orbit .o-center svg { max-width: 56px; max-height: 56px; width: auto; height: auto; }
    .orbit .o-center svg { width: 56px; height: 56px; }
    @keyframes v-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    .b-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 10px; font-size: 13px; font-weight: 800; color: var(--primary); }
    .b-link .ico { width: 15px; height: 15px; transition: transform .2s; }
    .b-link:hover .ico { transform: translateX(3px); }
    .b-find { grid-area: find; text-align: center; padding-bottom: 0; background: linear-gradient(180deg, #fff 45%, color-mix(in srgb, var(--violet) 7%, #fff)); }
    .b-find .b-tag { align-self: center; margin-top: 4px; }
    .b-find > p { max-width: 280px; margin-left: auto; margin-right: auto; }
    .finder { position: relative; margin-top: 18px; z-index: 4; }
    .finder-box { display: flex; align-items: center; gap: 6px; padding: 5px 5px 5px 16px; border-radius: 999px; background: #fff; border: 1px solid var(--line); box-shadow: 0 10px 24px -16px rgba(15,23,42,.4); }
    .finder-box input { flex: 1; min-width: 0; border: 0; outline: none; background: transparent; font-size: 13.5px; font-weight: 600; padding: 7px 0; }
    .finder-box button { width: 36px; height: 36px; border: 0; border-radius: 50%; display: grid; place-items: center; background: var(--primary); color: #fff; cursor: pointer; flex: none; }
    .finder-box button .ico { width: 16px; height: 16px; }
    .finder-out { position: absolute; left: 0; right: 0; top: calc(100% + 8px); display: none; padding: 6px; border-radius: 18px; background: #fff; border: 1px solid var(--line); box-shadow: 0 24px 50px -20px rgba(15,23,42,.35); text-align: left; }
    .has-hits .finder-out { display: block; }
    .finder-out a { display: block; padding: 9px 12px; border-radius: 12px; font-size: 13.5px; font-weight: 700; color: var(--ink); }
    .finder-out a:hover { background: color-mix(in srgb, var(--primary) 7%, #fff); color: var(--primary); }
    .finder-out span { display: block; padding: 9px 12px; font-size: 12.5px; color: var(--muted); }
    .finder-list { display: none; }
    .b-wave { margin: auto -22px 0; padding-top: 10px; }
    .b-wave svg { display: block; width: 100%; height: auto; }
    .b-cta { grid-area: cta; min-height: 180px; justify-content: flex-end; padding: 26px 28px; color: #fff; border: 0; background: var(--grad); isolation: isolate; }
    .b-cta::before { content: ''; position: absolute; right: -60px; top: -80px; width: 280px; height: 280px; border-radius: 50%; background: rgba(255,255,255,.12); z-index: -1; }
    .b-cta::after { content: ''; position: absolute; right: 120px; bottom: -120px; width: 220px; height: 220px; border-radius: 50%; background: rgba(255,255,255,.08); z-index: -1; }
    .b-cta .b-tag { background: rgba(255,255,255,.2); color: #fff; }
    .b-cta h3 { color: #fff; font-size: clamp(21px, 2.3vw, 28px); }
    .b-cta > p { color: rgba(255,255,255,.86); max-width: 620px; }
    .b-arrow { position: absolute; top: 22px; right: 22px; width: 46px; height: 46px; border-radius: 50%; display: grid; place-items: center; border: 1.5px solid rgba(255,255,255,.55); transition: transform .35s, background .35s; }
    .b-cta:hover .b-arrow { transform: rotate(45deg); background: rgba(255,255,255,.2); }

    /* ══════════ SECTIONS ══════════ */
    .v-sec { padding: 64px 0; }
    .sec-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px 30px; flex-wrap: wrap; margin-bottom: 30px; }
    .sec-row .lead-text { max-width: 420px; font-size: 15px; }
    .sec-side { display: flex; align-items: flex-end; gap: 24px; }
    .rail-btns { display: flex; gap: 8px; }
    .rail-btns button { width: 46px; height: 46px; border-radius: 50%; border: 1.5px solid var(--line); background: #fff; color: var(--ink); display: grid; place-items: center; cursor: pointer; transition: background .2s, color .2s, border-color .2s; }
    .rail-btns button:hover { background: var(--ink); border-color: var(--ink); color: #fff; }
    .rail-btns .ico { width: 18px; height: 18px; }

    /* Welcome, slider, figures */
    .v-story { padding: 40px; border-radius: 36px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); }
    .v-story-head { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: 20px 40px; align-items: end; margin-bottom: 28px; }
    .v-story-head .h2 { margin-top: 12px; }
    .v-story-head p { color: var(--muted); margin-bottom: 16px; }
    .v-slider { position: relative; overflow: hidden; border-radius: 28px; aspect-ratio: 16 / 7; background: var(--dark); touch-action: pan-y pinch-zoom; }
    .v-slider .slide { position: absolute; inset: 0; opacity: 0; transition: opacity 1s ease; }
    .v-slider .slide.on { opacity: 1; }
    .v-slider .slide > img { width: 100%; height: 100%; object-fit: cover; }
    .v-slider .slide.on > img { animation: v-kb 8s ease-out forwards; }
    @keyframes v-kb { from { transform: scale(1); } to { transform: scale(1.07); } }
    .v-slider .slide::after { content: ''; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 45%, rgba(8,12,28,.55)); pointer-events: none; }
    .v-slider .wm { display: none; background: var(--grad); color: rgba(255,255,255,.7); }
    .v-slider .slide.is-mark .wm { display: flex; }
    .v-slider .wm img, .v-slider .wm svg { opacity: .22; max-height: 70%; }
    .slide-cap { position: absolute; z-index: 2; left: 22px; bottom: 22px; max-width: min(560px, calc(100% - 150px)); padding: 16px 20px; border-radius: 20px; color: #fff; background: rgba(255,255,255,.14); -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,.24); }
    .slide-cap h3 { font-size: clamp(16px, 2vw, 23px); font-weight: 800; line-height: 1.22; letter-spacing: -.01em; }
    .slide-cap p { margin-top: 4px; font-size: 13.5px; opacity: .9; }
    .sl-nav { position: absolute; z-index: 3; right: 22px; bottom: 22px; display: flex; gap: 8px; }
    .sl-nav button { width: 46px; height: 46px; border: 0; border-radius: 50%; display: grid; place-items: center; background: #fff; color: var(--ink); cursor: pointer; box-shadow: 0 8px 20px rgba(0,0,0,.2); transition: transform .2s; }
    .sl-nav button:hover { transform: scale(1.06); }
    .sl-nav .ico { width: 18px; height: 18px; }
    .sl-dots { position: absolute; z-index: 3; top: 18px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; }
    .sl-dots button { width: 8px; height: 8px; padding: 0; border: 0; border-radius: 999px; background: rgba(255,255,255,.55); cursor: pointer; transition: width .3s, background .3s; }
    .sl-dots button.on { width: 26px; background: #fff; }
    .v-stats { display: grid; grid-template-columns: repeat({{ max(count($stats), 1) }}, minmax(0, 1fr)); gap: 18px; margin-top: 30px; }
    .stat { padding-left: 18px; border-left: 3px solid var(--c); }
    .stat b { display: block; font-size: clamp(28px, 3.4vw, 44px); line-height: 1.08; font-weight: 800; letter-spacing: -.035em; color: var(--ink); overflow-wrap: anywhere; }
    .stat span { display: block; margin-top: 4px; font-size: 13.5px; font-weight: 600; color: var(--muted); }

    /* Facilities to swipe */
    .v-rail { display: flex; gap: 18px; overflow-x: auto; overscroll-behavior-x: contain; scroll-snap-type: x mandatory; scrollbar-width: none; padding: 4px 2px 10px; -webkit-overflow-scrolling: touch; }
    .v-rail::-webkit-scrollbar { display: none; }
    .v-path { position: relative; flex: 0 0 calc((100% - 54px) / 4); scroll-snap-align: start; display: flex; flex-direction: column; min-height: 400px; border-radius: 28px; overflow: hidden; color: #fff; isolation: isolate;
        background: linear-gradient(160deg, var(--c), color-mix(in srgb, var(--c) 45%, #0B1324)); }
    .v-path > .ph { position: absolute; inset: 0; z-index: -2; }
    .v-path:hover > .ph > img { transform: scale(1.06); }
    .v-path::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(180deg, rgba(8,12,28,.05) 25%, rgba(8,12,28,.85) 100%); }
    .v-path > .ph + .big-emoji { display: none; }
    .v-path .big-emoji { position: absolute; left: 50%; top: 38%; transform: translate(-50%, -50%); font-size: 86px; line-height: 1; filter: drop-shadow(0 12px 20px rgba(0,0,0,.25)); z-index: -1; }
    .v-path-tag { align-self: flex-start; display: inline-flex; align-items: center; gap: 8px; margin: 16px; padding: 5px 13px 5px 5px; border-radius: 999px; background: rgba(255,255,255,.94); color: var(--ink); font-size: 12.5px; font-weight: 800; }
    .v-path-tag span { width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; background: color-mix(in srgb, var(--c) 16%, #fff); font-size: 15px; }
    .v-path-body { margin-top: auto; padding: 20px; }
    .v-path-body h3 { font-size: 21px; font-weight: 800; letter-spacing: -.015em; }
    .v-path-body p { margin: 6px 0 14px; font-size: 13.5px; line-height: 1.55; opacity: .9; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .pill-link { display: inline-flex; align-items: center; gap: 10px; padding: 5px 5px 5px 15px; border-radius: 999px; background: rgba(255,255,255,.18); -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); font-size: 12.5px; font-weight: 800; }
    .pill-link span { width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; background: #fff; color: var(--ink); }
    .pill-link .ico { width: 14px; height: 14px; }

    /* What we stand for */
    .v-points { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, .9fr); gap: 56px; align-items: center; }
    .v-point { border-bottom: 1px solid var(--line); }
    .v-point:first-of-type { border-top: 1px solid var(--line); margin-top: 26px; }
    .v-point summary { list-style: none; display: flex; align-items: center; gap: 16px; padding: 19px 0; cursor: pointer; }
    .v-point summary::-webkit-details-marker { display: none; }
    .v-point .num { min-width: 28px; font-size: 13px; font-weight: 800; color: var(--c); }
    .v-point .t { flex: 1; font-size: clamp(17px, 1.8vw, 22px); font-weight: 800; color: var(--ink); letter-spacing: -.015em; }
    .v-point .plus { position: relative; flex: none; width: 36px; height: 36px; border-radius: 50%; background: color-mix(in srgb, var(--c) 12%, #fff); transition: background .3s; }
    .v-point .plus::before, .v-point .plus::after { content: ''; position: absolute; left: 50%; top: 50%; width: 13px; height: 2px; border-radius: 2px; background: var(--c); transform: translate(-50%, -50%); transition: transform .3s, background .3s; }
    .v-point .plus::after { transform: translate(-50%, -50%) rotate(90deg); }
    .v-point[open] .plus { background: var(--c); }
    .v-point[open] .plus::before, .v-point[open] .plus::after { background: #fff; }
    .v-point[open] .plus::after { transform: translate(-50%, -50%) rotate(0); }
    .v-point-body { padding: 0 0 22px 44px; color: var(--text); }
    .v-point-body .btn { margin-top: 12px; }
    .v-art { position: relative; isolation: isolate; margin-right: 18px; }
    .v-art::before { content: ''; position: absolute; inset: 22px -18px -18px 22px; z-index: -1; border-radius: 34px; background: var(--grad); }
    .v-art .ph { aspect-ratio: 4 / 4.6; border-radius: 34px; }
    .v-art-card { position: absolute; left: -28px; bottom: 36px; display: flex; align-items: center; gap: 12px; max-width: 300px; padding: 14px 18px 14px 14px; border-radius: 22px; background: #fff; box-shadow: 0 24px 50px -20px rgba(15,23,42,.4); }
    .v-art-card .mb-logo { width: 46px; height: 46px; }
    .v-art-card .mb-logo img, .v-art-card .mb-logo svg { max-width: 46px; max-height: 46px; }
    .v-art-card b { display: block; font-size: 14px; line-height: 1.3; color: var(--ink); }
    .v-art-card > span:last-child > span { display: block; font-size: 12px; color: var(--muted); }

    /* Leadership's words in a ring of faces */
    .v-ring { position: relative; display: grid; place-items: center; min-height: 430px; text-align: center; }
    .v-ring .ring { position: absolute; left: 50%; top: 50%; border-radius: 50%; border: 1px dashed color-mix(in srgb, var(--primary) 25%, var(--line)); transform: translate(-50%, -50%); pointer-events: none; }
    .v-ring .r1 { width: 380px; height: 380px; } .v-ring .r2 { width: 620px; height: 620px; } .v-ring .r3 { width: 880px; height: 880px; opacity: .6; }
    .v-ring .face { position: absolute; width: 58px; height: 58px; margin: -29px 0 0 -29px; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 12px 26px -10px rgba(15,23,42,.35); animation: v-float 7s ease-in-out infinite; animation-delay: calc(var(--i) * -1.1s); }
    .v-ring .face .ph { width: 100%; height: 100%; border-radius: 50%; }
    .v-ring .bubble { position: absolute; left: 44px; top: -20px; padding: 6px 12px; border-radius: 14px 14px 14px 4px; background: #fff; font-size: 11.5px; font-weight: 800; color: var(--ink); white-space: nowrap; box-shadow: 0 10px 24px -12px rgba(15,23,42,.35); }
    .v-ring-center { position: relative; max-width: 520px; padding: 0 16px; }
    .v-ring-center .lead-text { margin: 12px auto 22px; }
    .v-quotes { display: grid; max-width: 820px; margin: 10px auto 0; }
    .v-quotes .slide { grid-area: 1 / 1; opacity: 0; visibility: hidden; transition: opacity .6s, visibility .6s; }
    .v-quotes .slide.on { opacity: 1; visibility: visible; }
    .quote-card { padding: 34px 36px; border-radius: 30px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); text-align: center; }
    .quote-card blockquote { font-size: clamp(16px, 1.7vw, 19px); line-height: 1.65; color: var(--ink); font-weight: 600; }
    .quote-card figcaption { display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 22px; text-align: left; }
    .quote-card figcaption .ph { width: 50px; height: 50px; border-radius: 50%; flex: none; }
    .quote-card figcaption b { display: block; color: var(--ink); font-size: 15px; }
    .quote-card figcaption span { display: block; color: var(--muted); font-size: 13px; }
    .quote-card .quote-mark { margin: 0 auto 16px; }
    .v-quotes .sl-dots { position: static; transform: none; justify-content: center; margin-top: 18px; grid-area: 2 / 1; }
    .v-quotes .sl-dots button { background: var(--line); }
    .v-quotes .sl-dots button.on { background: var(--grad); }

    /* Upcoming events */
    .v-events { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
    .ev-card { display: flex; gap: 18px; padding: 20px; border-radius: 24px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); }
    .ev-date { flex: none; width: 74px; padding: 12px 6px; border-radius: 18px; text-align: center; color: #fff; background: linear-gradient(160deg, var(--c), color-mix(in srgb, var(--c) 60%, #0B1324)); }
    .ev-date b { display: block; font-size: 28px; line-height: 1; font-weight: 800; }
    .ev-date span { display: block; margin-top: 4px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; opacity: .9; }
    .ev-card h3 { margin: 8px 0 4px; font-size: 16.5px; line-height: 1.35; font-weight: 800; color: var(--ink); }
    .ev-card p { font-size: 13px; color: var(--muted); }

    /* Photo mosaic */
    .mosaic { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); grid-auto-rows: 200px; gap: 14px; }
    .mosaic.n5 > :first-child { grid-column: span 2; grid-row: span 2; }
    .mosaic:not(.n5) { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); grid-auto-rows: 240px; }
    .mosaic figure { position: relative; overflow: hidden; border-radius: 24px; }
    .mosaic .ph { width: 100%; height: 100%; cursor: zoom-in; }
    .mosaic figure:hover .ph > img { transform: scale(1.06); }
    .mosaic figcaption { position: absolute; left: 12px; bottom: 12px; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.9); font-size: 12px; font-weight: 700; color: var(--ink); pointer-events: none; }

    /* Admission card */
    .v-cta { position: relative; overflow: hidden; display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, .8fr); gap: 40px; align-items: center; padding: 48px; border-radius: 36px; color: #fff; background: var(--grad); isolation: isolate; }
    .v-cta::before { content: ''; position: absolute; inset: 0; z-index: -1; background-image: radial-gradient(rgba(255,255,255,.18) 1.2px, transparent 1.4px); background-size: 24px 24px; mask-image: linear-gradient(90deg, transparent, #000); -webkit-mask-image: linear-gradient(90deg, transparent, #000); }
    .v-cta .eyebrow { background: rgba(255,255,255,.18); color: #fff; }
    .v-cta .eyebrow::before { background: #fff; }
    .v-cta h2 { text-wrap: balance; margin: 14px 0 10px; font-size: clamp(28px, 3.6vw, 46px); line-height: 1.08; font-weight: 800; letter-spacing: -.035em; }
    .v-cta-text > p { color: rgba(255,255,255,.88); max-width: 620px; }
    .v-cta-text > p + p { margin-top: 10px; }
    .v-steps { list-style: none; display: grid; gap: 10px; margin: 22px 0 4px; counter-reset: st; }
    .step { display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px; border-radius: 16px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); counter-increment: st; }
    .step::before { content: counter(st, decimal-leading-zero); flex: none; width: 30px; height: 30px; border-radius: 10px; display: grid; place-items: center; background: #fff; color: var(--violet); font-size: 12px; font-weight: 800; }
    .step b { display: block; font-size: 14.5px; }
    .step span { display: block; font-size: 13px; opacity: .85; }
    .v-cta .btns { margin-top: 24px; }
    .v-cta .btn-white { color: var(--violet); }
    .v-cta-art { position: relative; min-height: 330px; }
    .paper { position: absolute; inset: 0; margin: auto; width: min(100%, 330px); height: 320px; border-radius: 22px; background: #fff; }
    .paper.p3 { transform: rotate(9deg) translate(26px, 6px); opacity: .35; }
    .paper.p2 { transform: rotate(4deg) translate(12px, 2px); opacity: .6; }
    .paper.p1 { padding: 22px; color: var(--ink); box-shadow: 0 30px 60px -20px rgba(0,0,0,.4); display: flex; flex-direction: column; }
    .paper-head { display: flex; align-items: center; gap: 10px; padding-bottom: 14px; border-bottom: 1px dashed var(--line); }
    .paper-head .mb-logo { width: 38px; height: 38px; }
    .paper-head b { display: block; font-size: 14.5px; line-height: 1.25; }
    .paper-head > span:last-child > span { display: block; font-size: 11.5px; color: var(--muted); }
    .paper ul { list-style: none; display: grid; gap: 9px; margin-top: 14px; overflow: hidden; }
    .paper li { display: flex; gap: 9px; align-items: flex-start; font-size: 13px; font-weight: 600; color: var(--text); line-height: 1.4; }
    .paper li .ico { width: 16px; height: 16px; margin-top: 1px; padding: 2px; border-radius: 50%; background: color-mix(in srgb, var(--green) 15%, #fff); color: var(--green); }
    .paper-foot { margin-top: auto; padding-top: 12px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: var(--muted); }
    .paper-foot i { width: 92px; height: 2px; background: linear-gradient(90deg, var(--violet), var(--pink)); border-radius: 2px; }

    /* Location */
    .v-loc .info-list { grid-template-columns: minmax(0, 1fr); }
    .v-loc .contact-grid.nomap { grid-template-columns: minmax(0, 1fr); }
    .v-loc .contact-grid.nomap .info-list { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }

    @media (max-width: 1020px) {
        .v-bento { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-template-areas: "about team" "find find" "cta cta"; }
        .b-find .b-wave { max-width: 520px; margin-left: auto; margin-right: auto; }
        .v-story-head, .v-points, .v-cta { grid-template-columns: minmax(0, 1fr); }
        .v-path { flex-basis: calc((100% - 18px) / 2); }
        .v-events { grid-template-columns: minmax(0, 1fr); }
        .v-art { max-width: 520px; }
        .v-cta-art { display: none; }
        .v-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .v-ring .r3 { display: none; }
    }
    @media (max-width: 720px) {
        .v-hero { padding: 30px 0 20px; }
        .v-hero h1 { margin-top: 18px; }
        .v-quick-row { flex-wrap: wrap; border-radius: 24px; padding: 8px; }
        .v-quick-row i { display: none; }
        .sform.v-quick input { flex: 1 1 100%; background: var(--page); }
        .v-quick .btn { flex: 1 1 100%; }
        .v-bento { grid-template-columns: minmax(0, 1fr); grid-template-areas: "about" "team" "find" "cta"; margin-top: 30px; gap: 14px; }
        .b-card { border-radius: 24px; }
        .v-sec { padding: 40px 0; }
        .v-story { padding: 20px; border-radius: 26px; }
        .v-slider { aspect-ratio: 4 / 4.2; border-radius: 22px; }
        .slide-cap { left: 12px; right: 12px; bottom: 70px; max-width: none; padding: 12px 14px; }
        .sl-nav { right: 12px; bottom: 12px; }
        .sl-nav button { width: 42px; height: 42px; }
        .v-path { flex-basis: 80%; min-height: 360px; }
        .v-point-body { padding-left: 0; }
        .v-art { margin-right: 14px; }
        .v-art-card { left: 12px; right: 12px; bottom: 14px; max-width: none; }
        .v-ring { min-height: 0; padding: 40px 0 10px; }
        .v-ring .face, .v-ring .r2, .v-ring .r3 { display: none; }
        .v-ring .r1 { width: 300px; height: 300px; }
        .quote-card { padding: 24px 20px; border-radius: 24px; }
        .mosaic, .mosaic:not(.n5) { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: 150px; gap: 10px; }
        .mosaic.n5 > :first-child { grid-column: span 2; grid-row: span 1; }
        .mosaic figure { border-radius: 18px; }
        .v-cta { padding: 28px 20px; border-radius: 28px; }
        .v-cta .btns .btn { flex: 1; }
    }
</style>
@endpush

@section('content')

    {{-- ══════════ HERO + BENTO ══════════ --}}
    <section class="v-hero">
        <div class="wrap">
            <div class="v-hero-text">
                <span class="v-pill">
                    @if ($faces)
                        <span class="v-faces">
                            @foreach ($faces as $f)
                                @include('school-site.classic.partials.ph', ['src' => $f['photo'], 'alt' => $f['name']])
                            @endforeach
                        </span>
                    @else
                        <span class="v-spark">@include('school-site.modern.partials.icon', ['n' => 'sparkle'])</span>
                    @endif
                    {{ $badge }}
                </span>
                <h1>{!! ModernSite::highlight($c['hero_title'], $c['school_name']) !!}</h1>
                @if (!empty($c['hero_subtitle']))<p class="v-sub">{{ $c['hero_subtitle'] }}</p>@endif

                <form class="sform v-quick" data-org="{{ (int) $site->organization_id }}" data-kind="Call back request" data-type="query" novalidate>
                    <div class="hp" aria-hidden="true"><input type="text" name="sf_check" tabindex="-1" autocomplete="off"></div>
                    <div class="v-quick-row">
                        <input type="text" name="name" data-label="Name" placeholder="Parent's name" maxlength="200" required aria-label="Parent's name">
                        <i aria-hidden="true"></i>
                        <input type="tel" name="phone" data-label="Phone" placeholder="Mobile number" maxlength="15" required aria-label="Mobile number">
                        <button class="btn" type="submit">Get a call back @include('school-site.modern.partials.icon', ['n' => 'arrow'])</button>
                    </div>
                    <p class="sform-msg" role="status"></p>
                </form>
            </div>

            <div class="v-bento">
                <article class="b-card b-about">
                    <h3>{{ $on('about') ? 'About Us' : 'Our School' }}</h3>
                    <p>{{ ModernSite::excerpt($c['about_text'], 130) }}</p>
                    @include('school-site.classic.partials.ph', ['src' => $pool[0] ?? null, 'alt' => $c['school_name']])
                    @php $chips = array_values(array_filter([$board, $range, trim((string) ($c['medium'] ?? '')) !== '' ? Str::limit($c['medium'], 26) : ''])); @endphp
                    @if ($chips)
                        <div class="b-chips">@foreach ($chips as $chip)<span>{{ $chip }}</span>@endforeach</div>
                    @endif
                </article>

                <article class="b-card b-team">
                    <h3>{{ $orbitPeople ? 'Our Team' : 'Our Campus' }}</h3>
                    <p>{{ $orbitPeople ? 'Experienced teachers and staff who guide every child, every day.' : 'Everything a child needs to learn, play and grow — in one place.' }}</p>
                    <div class="orbit">
                        <svg class="lines" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                            @foreach ($orbit as $i => $o)<line x1="50" y1="52" x2="{{ $spots[$i][0] }}" y2="{{ $spots[$i][1] }}"/>@endforeach
                        </svg>
                        <span class="o-center">@include('school-site.classic.partials.logo', ['sealId' => 'orbit'])</span>
                        @foreach ($orbit as $i => $o)
                            <span class="o {{ $o['emoji'] ? 'emoji' : '' }}" title="{{ $o['label'] }}"
                                  style="left: {{ $spots[$i][0] }}%; top: {{ $spots[$i][1] }}%; --c: {{ $colour($i) }}; --i: {{ $i }};">
                                @if ($o['emoji'])
                                    {{ $o['emoji'] }}
                                @elseif ($o['photo'] !== '')
                                    @include('school-site.classic.partials.ph', ['src' => $o['photo'], 'alt' => $o['label']])
                                @else
                                    {{ $initials($o['label']) }}
                                @endif
                            </span>
                        @endforeach
                    </div>
                    @if ($orbitPeople && $on('team'))
                        <a class="b-link" href="{{ url('team') }}">Meet our team @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>
                    @elseif (! $orbitPeople && $on('facilities'))
                        <a class="b-link" href="{{ url('facilities') }}">See our facilities @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>
                    @endif
                </article>

                <article class="b-card b-find">
                    <span class="b-tag">@include('school-site.modern.partials.icon', ['n' => 'sparkle']) {{ $c['school_name'] }}</span>
                    <h3>Find what you need</h3>
                    <p>Search for a page — admission, fees, results, transport and more.</p>
                    <form class="finder" data-find role="search">
                        <div class="finder-box">
                            <input type="search" placeholder="Search the school website" aria-label="Search the school website" autocomplete="off">
                            <button type="submit" aria-label="Search">@include('school-site.modern.partials.icon', ['n' => 'search'])</button>
                        </div>
                        <div class="finder-out" data-find-out></div>
                        <div class="finder-list" data-find-list>
                            @foreach ($finder as $pageName => $href)<a href="{{ $href }}">{{ $pageName }}</a>@endforeach
                        </div>
                    </form>
                    <div class="b-wave" aria-hidden="true">
                        <svg viewBox="0 0 300 170">
                            <defs>
                                <linearGradient id="vWave" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="300" y2="0">
                                    <stop offset="0" stop-color="{{ $theme['primary'] }}"/><stop offset=".45" stop-color="#7C3AED"/>
                                    <stop offset=".75" stop-color="#EC4899"/><stop offset="1" stop-color="#06B6D4"/>
                                </linearGradient>
                            </defs>
                            <g fill="url(#vWave)">{!! $wave !!}</g>
                        </svg>
                    </div>
                </article>

                <a class="b-card b-cta" href="{{ $apply ?? url('/') }}">
                    <span class="b-tag">@include('school-site.modern.partials.icon', ['n' => 'school']) {{ $session !== '' ? $session : ($board !== '' ? $board : 'Admissions') }}</span>
                    <h3>{{ $c['cta_heading'] }}</h3>
                    <p>{{ $c['cta_text'] }}</p>
                    <span class="b-arrow" aria-hidden="true">@include('school-site.modern.partials.icon', ['n' => 'arrow-ur'])</span>
                </a>
            </div>
        </div>
    </section>

    {{-- ══════════ WELCOME · SLIDER · FIGURES ══════════ --}}
    <section class="v-sec">
        <div class="wrap">
            <div class="v-story">
                <div class="v-story-head">
                    <div>
                        <span class="eyebrow">Welcome</span>
                        <h2 class="h2">{!! ModernSite::highlight($welcome, $c['school_name']) !!}</h2>
                    </div>
                    <div>
                        @if ($lead !== '')<p><b style="color:var(--ink)">{{ $lead }}</b></p>@endif
                        <p>{{ ModernSite::excerpt(trim($c['about_text'] . ' ' . ($c['about_text2'] ?? '')), 330) }}</p>
                        <div class="btns">
                            @if ($on('about'))<a class="btn btn-grad" href="{{ url('about') }}">Read More @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                            @if ($on('appointment'))<a class="btn btn-ghost" href="{{ url('appointment') }}">Book a Visit</a>
                            @elseif ($on('contact'))<a class="btn btn-ghost" href="{{ url('contact') }}">Contact Us</a>@endif
                        </div>
                    </div>
                </div>

                <div class="v-slider" id="slider" data-slider="5500" aria-roledescription="carousel" aria-label="{{ $c['school_name'] }}">
                    @foreach ($slides as $i => $s)
                        @php $slideUrl = SchoolWebsite::media($s['image'] ?: null); @endphp
                        <div class="slide {{ $i === 0 ? 'on' : '' }} {{ $slideUrl ? '' : 'is-mark' }}" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($slides) }}">
                            @if ($slideUrl)
                                <img src="{{ $slideUrl }}" alt="{{ $s['title'] }}" @if ($i) loading="lazy" @endif onerror="this.parentNode.classList.add('is-mark');this.remove();">
                            @endif
                            @include('school-site.classic.partials.watermark', ['label' => null])
                            @if (trim($s['title'] . $s['subtitle']) !== '')
                                <div class="slide-cap">
                                    @if ($s['title'] !== '')<h3>{{ $s['title'] }}</h3>@endif
                                    @if ($s['subtitle'] !== '')<p>{{ $s['subtitle'] }}</p>@endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                    @if (count($slides) > 1)
                        <div class="sl-dots">
                            @foreach ($slides as $i => $s)<button type="button" class="{{ $i === 0 ? 'on' : '' }}" aria-label="Slide {{ $i + 1 }}"></button>@endforeach
                        </div>
                        <div class="sl-nav">
                            <button class="sl-prev" type="button" aria-label="Previous slide">@include('school-site.modern.partials.icon', ['n' => 'left'])</button>
                            <button class="sl-next" type="button" aria-label="Next slide">@include('school-site.modern.partials.icon', ['n' => 'right'])</button>
                        </div>
                    @endif
                </div>

                @if ($stats)
                    <div class="v-stats">
                        @foreach ($stats as $i => $s)
                            <div class="stat" style="--c: {{ $colour($i) }}"><b data-count>{{ $s['value'] }}</b><span>{{ $s['label'] }}</span></div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ══════════ FEATURES & FACILITIES ══════════ --}}
    <section class="v-sec" style="padding-top:20px;">
        <div class="wrap">
            <div class="sec-row">
                <div>
                    <span class="eyebrow">Features &amp; Facilities</span>
                    <h2 class="h2">Everything your child needs <span class="hl">to grow</span></h2>
                </div>
                <div class="sec-side">
                    <p class="lead-text">At {{ $c['school_name'] }}, we are committed to the best facilities for the overall development of your child.</p>
                    @if (count($facilities) > 1)
                        <div class="rail-btns">
                            <button type="button" data-rail-prev="facRail" aria-label="Previous">@include('school-site.modern.partials.icon', ['n' => 'left'])</button>
                            <button type="button" data-rail-next="facRail" aria-label="Next">@include('school-site.modern.partials.icon', ['n' => 'right'])</button>
                        </div>
                    @endif
                </div>
            </div>
            <div class="v-rail" id="facRail">
                @foreach ($facilities as $i => $f)
                    @php $facUrl = SchoolWebsite::media($f['image'] ?? null); @endphp
                    <article class="v-path" style="--c: {{ $colour($i) }}">
                        @if ($facUrl)
                            <div class="ph"><img src="{{ $facUrl }}" alt="{{ $f['title'] ?? '' }}" loading="lazy" onerror="this.parentNode.remove();"></div>
                        @endif
                        <span class="big-emoji" aria-hidden="true">{{ $f['icon'] ?? '' }}</span>
                        <span class="v-path-tag"><span aria-hidden="true">{{ $f['icon'] ?? '✦' }}</span>{{ Str::limit($f['title'] ?? '', 28) }}</span>
                        <div class="v-path-body">
                            <h3>{{ $f['title'] ?? '' }}</h3>
                            <p>{{ $f['desc'] ?? '' }}</p>
                            @if ($on('facilities'))
                                <a class="pill-link" href="{{ url('facilities') }}">Read more <span>@include('school-site.modern.partials.icon', ['n' => 'arrow-ur'])</span></a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════ WHAT WE STAND FOR ══════════ --}}
    @if ($points)
        <section class="v-sec">
            <div class="wrap v-points">
                <div>
                    <span class="eyebrow">Why {{ $c['school_name'] }}</span>
                    <h2 class="h2">What we <span class="hl">stand for</span></h2>
                    @foreach ($points as $i => $p)
                        <details class="v-point" style="--c: {{ $colour($i) }}" @if ($i === 0) open @endif>
                            <summary><span class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span><span class="t">{{ $p['title'] }}</span><span class="plus" aria-hidden="true"></span></summary>
                            <div class="v-point-body">
                                <p>{{ ModernSite::excerpt($p['text'], 380) }}</p>
                                @if ($p['url'])<a class="btn btn-soft btn-sm" href="{{ url($p['url']) }}">View details @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                            </div>
                        </details>
                    @endforeach
                </div>
                <div class="v-art">
                    @include('school-site.classic.partials.ph', ['src' => $pool[1] ?? ($pool[0] ?? null), 'alt' => $c['school_name']])
                    <div class="v-art-card">
                        <span class="mb-logo">@include('school-site.classic.partials.logo', ['sealId' => 'art'])</span>
                        <span><b>{{ $c['school_name'] }}</b><span>{{ trim((string) ($c['motto'] ?? ''), " \"“”") ?: ($board !== '' ? 'Affiliated to ' . $board : $c['tagline']) }}</span></span>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ MESSAGES FROM THE LEADERSHIP ══════════ --}}
    @if ($messages)
        <section class="v-sec">
            <div class="wrap">
                <div class="v-ring">
                    <span class="ring r1"></span><span class="ring r2"></span><span class="ring r3"></span>
                    @foreach ($ringFaces as $i => $f)
                        <span class="face" style="left: {{ $ringSpots[$i][0] }}%; top: {{ $ringSpots[$i][1] }}%; --i: {{ $i }};">
                            @include('school-site.classic.partials.ph', ['src' => $f['photo'], 'alt' => $f['role']])
                            @if ($f['role'] !== '' && $i % 2 === 0)<span class="bubble">{{ Str::limit($f['role'], 24) }}</span>@endif
                        </span>
                    @endforeach
                    <div class="v-ring-center">
                        <span class="eyebrow">Messages</span>
                        <h2 class="h2">Words from our <span class="hl">leadership</span></h2>
                        <p class="lead-text">How the people who lead {{ $c['school_name'] }} see its children, its teachers and its future.</p>
                        @if ($messages[0]['url'])<a class="btn btn-grad" href="{{ url($messages[0]['url']) }}">Read the messages @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                    </div>
                </div>
                <div class="v-quotes" data-slider="8000">
                    @foreach (array_slice($messages, 0, 6) as $i => $m)
                        <figure class="slide quote-card {{ $i === 0 ? 'on' : '' }}">
                            <span class="quote-mark">@include('school-site.modern.partials.icon', ['n' => 'quote'])</span>
                            <blockquote>“{{ ModernSite::excerpt($m['message'], 340) }}”</blockquote>
                            <figcaption>
                                @if ($m['photo'] !== '')@include('school-site.classic.partials.ph', ['src' => $m['photo'], 'alt' => $m['name']])@endif
                                <span><b>{{ $m['name'] ?: $m['role'] }}</b>@if ($m['name'] !== '' && $m['role'] !== '')<span>{{ $m['role'] }}</span>@endif</span>
                            </figcaption>
                        </figure>
                    @endforeach
                    @if (count($messages) > 1)
                        <div class="sl-dots">
                            @foreach (array_slice($messages, 0, 6) as $i => $m)<button type="button" class="{{ $i === 0 ? 'on' : '' }}" aria-label="Message {{ $i + 1 }}"></button>@endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ UPCOMING EVENTS ══════════ --}}
    @if ($events)
        <section class="v-sec" style="padding-top:20px;">
            <div class="wrap">
                <div class="sec-row">
                    <div>
                        <span class="eyebrow">Calendar</span>
                        <h2 class="h2">Coming up at <span class="hl">school</span></h2>
                    </div>
                    @if ($on('academic-calendar'))<a class="btn btn-ghost" href="{{ url('academic-calendar') }}">Full calendar @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                </div>
                <div class="v-events">
                    @foreach ($events as $i => $e)
                        @php $d = \Carbon\Carbon::parse($e['date']); @endphp
                        <article class="ev-card" style="--c: {{ $colour($i + 1) }}">
                            <div class="ev-date"><b>{{ $d->format('d') }}</b><span>{{ $d->format('M') }}</span></div>
                            <div>
                                <span class="chip">{{ $e['type'] }}</span>
                                <h3>{{ $e['title'] }}</h3>
                                <p>{{ $d->format('l') }} · {{ $e['time'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ PHOTO MOSAIC ══════════ --}}
    @if (count($gallery) >= 2)
        <section class="v-sec" style="padding-top:20px;">
            <div class="wrap">
                <div class="sec-row">
                    <div>
                        <span class="eyebrow">Photogallery</span>
                        <h2 class="h2">Life at <span class="hl">{{ $c['school_name'] }}</span></h2>
                    </div>
                    @if ($on('gallery'))<a class="btn btn-ghost" href="{{ url('gallery') }}">View all photos @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                </div>
                <div class="mosaic n{{ count($gallery) }}">
                    @foreach ($gallery as $g)
                        <figure>
                            @include('school-site.classic.partials.ph', ['src' => $g['image'], 'alt' => $g['caption'], 'lb' => true])
                            @if ($g['caption'] !== '')<figcaption>{{ $g['caption'] }}</figcaption>@endif
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ ADMISSIONS ══════════ --}}
    <section class="v-sec">
        <div class="wrap">
            <div class="v-cta">
                <div class="v-cta-text">
                    <span class="eyebrow">{{ $session !== '' ? 'Admission Schedule · ' . $session : 'Admissions' }}</span>
                    <h2>{{ $c['cta_heading'] }}</h2>
                    <p>{{ $c['cta_text'] }}</p>
                    @if (!empty($c['admission_intro']))<p>{{ $c['admission_intro'] }}</p>@endif
                    @if ($steps)
                        <ol class="v-steps">
                            @foreach (array_slice($steps, 0, 4) as $st)
                                <li class="step"><span><b>{{ $st['title'] }}</b>@if (!empty($st['desc']))<span>{{ $st['desc'] }}</span>@endif</span></li>
                            @endforeach
                        </ol>
                    @endif
                    <div class="btns">
                        @if ($on('admission'))<a class="btn btn-white" href="{{ url('admission') }}">Apply Online @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                        @if ($tel !== '')<a class="btn btn-line" href="tel:{{ $tel }}">@include('school-site.modern.partials.icon', ['n' => 'phone']) Call {{ trim(explode(',', $c['phone'])[0]) }}</a>@endif
                    </div>
                </div>
                <div class="v-cta-art" aria-hidden="true">
                    <div class="paper p3"></div><div class="paper p2"></div>
                    <div class="paper p1">
                        <div class="paper-head">
                            <span class="mb-logo">@include('school-site.classic.partials.logo', ['sealId' => 'paper'])</span>
                            <span><b>{{ $docs ? 'Documents Required' : 'Admission Office' }}</b><span>{{ $c['school_name'] }}</span></span>
                        </div>
                        <ul>
                            @if ($docs)
                                @foreach (array_slice($docs, 0, 7) as $d)<li>@include('school-site.modern.partials.icon', ['n' => 'check']){{ $d }}</li>@endforeach
                            @else
                                @if (!empty($c['phone']))<li>@include('school-site.modern.partials.icon', ['n' => 'check']){{ $c['phone'] }}</li>@endif
                                @if (!empty($c['email']))<li>@include('school-site.modern.partials.icon', ['n' => 'check']){{ $c['email'] }}</li>@endif
                                @if (!empty($c['address']))<li>@include('school-site.modern.partials.icon', ['n' => 'check']){{ Str::limit($c['address'], 90) }}</li>@endif
                                @if ($range !== '')<li>@include('school-site.modern.partials.icon', ['n' => 'check']){{ $range }}</li>@endif
                            @endif
                        </ul>
                        <div class="paper-foot"><span>{{ $session ?: date('Y') }}</span><i></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════ LOCATION ══════════ --}}
    <section class="v-sec v-loc" style="padding-top:20px;">
        <div class="wrap">
            <div class="sec-head center">
                <span class="eyebrow">Visit Us</span>
                <h2 class="h2">Our <span class="hl">Location</span></h2>
                <p class="lead-text">Reach us at the school or just drop in an email{{ !empty($c['email']) ? ' — ' . $c['email'] : '' }}.</p>
            </div>
            <div class="contact-grid {{ $mapSrc ? '' : 'nomap' }}">
                <div class="info-list">
                    @if (!empty($c['address']))
                        <div class="info-card"><span class="badge-ico" style="--c: {{ $colour(0) }}">@include('school-site.modern.partials.icon', ['n' => 'pin'])</span><div><span>Address</span><b>{{ $c['address'] }}</b></div></div>
                    @endif
                    @if (!empty($c['phone']))
                        <div class="info-card"><span class="badge-ico" style="--c: {{ $colour(2) }}">@include('school-site.modern.partials.icon', ['n' => 'phone'])</span><div><span>Call us</span><a href="tel:{{ $tel }}">{{ $c['phone'] }}</a></div></div>
                    @endif
                    @if (!empty($c['email']))
                        <div class="info-card"><span class="badge-ico" style="--c: {{ $colour(4) }}">@include('school-site.modern.partials.icon', ['n' => 'mail'])</span><div><span>Email</span><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></div></div>
                    @endif
                    @if (!empty($c['affiliation_no']) || !empty($c['school_code']))
                        <div class="info-card"><span class="badge-ico" style="--c: {{ $colour(1) }}">@include('school-site.modern.partials.icon', ['n' => 'award'])</span><div><span>{{ $board ?: 'CBSE' }} Affiliation</span><b>{{ trim(($c['affiliation_no'] ? 'Aff. No. ' . $c['affiliation_no'] : '') . ($c['affiliation_no'] && $c['school_code'] ? ' · ' : '') . ($c['school_code'] ? 'School Code ' . $c['school_code'] : '')) }}</b></div></div>
                    @endif
                </div>
                @if ($mapSrc)
                    <div class="map-card">@include('school-site.classic.partials.map', ['height' => 420])</div>
                @endif
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
// What we stand for: one point open at a time.
document.querySelectorAll('.v-point').forEach(function (d) {
    d.addEventListener('toggle', function () {
        if (d.open) document.querySelectorAll('.v-point[open]').forEach(function (o) { if (o !== d) o.open = false; });
    });
});
</script>
@endpush
