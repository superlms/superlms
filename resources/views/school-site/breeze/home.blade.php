{{--
    Breeze's home page, from top to bottom: a split hero (the headline and
    buttons beside the slider in a rounded frame, with floating cards); "at
    a glance" — dashboard cards for the calendar month, the classes, how to
    apply, the latest board result as a gauge and the results by year as
    bars; the welcome beside a photo collage with the vision, mission,
    values and goals; the facility cards; messages from the leadership; a
    photo grid; the admission card; and the map with the contact cards.
    Every part reads Classic's content and shows only when the school has it.
--}}
@extends('school-site.breeze.layout')
@php
    use App\Models\SchoolWebsite;
    use App\Support\ModernSite;
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $stored     = $site->content ?? [];
    $sw         = fn ($i) => ModernSite::swatch('breeze', $i, $theme['primary']);
    $on         = fn ($slug) => $site->isPageEnabled($slug);
    $slides     = ModernSite::slides($c);
    $pool       = ModernSite::photos($c);
    $facilities = array_slice(ModernSite::facilities($c), 0, 6);
    $messages   = array_slice(ModernSite::messages($site, $c), 0, 3);
    $stats      = ModernSite::stats($c, $lms);
    $faces      = ModernSite::faces($c, 4);
    $range      = ModernSite::classRange($lms, $c);
    $events     = $lms->events();
    $month      = $events ? ModernSite::month($events) : null;
    $next       = ModernSite::upcoming($events, 2);
    $classes    = $lms->classes();
    $res        = ModernSite::results($c['results'] ?? []);
    $gallery    = array_slice(ModernSite::gallery($c), 0, 6);
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

    // How to apply: the school's steps, else its documents, else what its pages offer.
    if ($steps) {
        $checksTitle = 'How to apply';
        $checks = array_map(fn ($s) => [$s['title'], trim((string) ($s['desc'] ?? ''))], $steps);
    } elseif ($docs) {
        $checksTitle = 'Documents required';
        $checks = array_map(fn ($d) => [$d, ''], $docs);
    } else {
        $checksTitle = 'Getting started';
        $checks = array_values(array_filter([
            $tel !== '' ? ['Talk to the admission office', $c['phone']] : null,
            $on('admission') ? ['Fill in the online admission form', 'Choose the class and send it'] : null,
            $on('appointment') ? ['Book a campus visit', 'Meet the teachers and see the school'] : null,
            $on('fee-structure') ? ['Check the fee structure', 'Class by class, for this session'] : null,
            $on('rules') ? ['Read the rules & regulations', ''] : null,
        ]));
    }
    $checks = array_slice($checks, 0, 4);

    // The classes card: the Standard page's classes, else Website Data's class cards.
    $clsRows = [];
    if ($classes) {
        $most = max(1, ...array_map(fn ($k) => count($k['subjects']), $classes));
        foreach (array_slice($classes, 0, 5) as $k) {
            $clsRows[] = ['name' => $k['name'],
                'meta' => count($k['sections']) . ' ' . Str::plural('section', count($k['sections'])) . ' · ' . count($k['subjects']) . ' ' . Str::plural('subject', count($k['subjects'])),
                'fill' => max(8, (int) round(count($k['subjects']) / $most * 100)), 'num' => count($k['subjects'])];
        }
    } else {
        foreach (array_slice(array_values(array_filter($c['classes'] ?? [], fn ($k) => trim((string) ($k['title'] ?? '')) !== '')), 0, 5) as $i => $k) {
            $clsRows[] = ['name' => $k['title'], 'meta' => implode(' · ', array_filter([$k['age'] ?? '', $k['time'] ?? ''])) ?: 'Class',
                'fill' => 100 - $i * 14, 'num' => trim((string) ($k['capacity'] ?? '')) ?: null];
        }
    }

    // The board-result gauge: 21 ticks from red to green, lit up to the pass percentage.
    $gauge = '';
    if ($latest = $res['latest']) {
        for ($k = 0; $k <= 20; $k++) {
            $a  = M_PI - $k * M_PI / 20;
            $lit = $k / 20 <= $latest['pct'] / 100 + 0.0001;
            $col = $lit ? 'hsl(' . round($k / 20 * 128) . ', 78%, 50%)' : '#E8ECF3';
            $gauge .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="%s"/>',
                110 + 70 * cos($a), 112 - 70 * sin($a), 110 + 96 * cos($a), 112 - 96 * sin($a), $col);
        }
    }
    $yearBars = array_slice($res['rows'], -7);

    // The dashboard's cards, each a third (or more) of the row; a card that
    // does not fit where its row ends widens the one before it, so no row has a gap.
    $cards = array_values(array_filter([
        $month ? ['cal', 1] : null,
        $clsRows ? ['cls', 1] : null,
        $checks ? ['goals', 1] : null,
        $latest ? ['gauge', 1] : null,
        count($yearBars) >= 2 ? ['years', 2] : null,
    ]));
    $col = 0;
    foreach ($cards as $i => [$key, $span]) {
        if ($col + $span > 3) {
            $cards[$i - 1][1] += 3 - $col;
            $col = 0;
        }
        $col = ($col + $span) % 3;
    }
    $cards[] = ['hi', $col ? 3 - $col : 3];
@endphp

@push('styles')
<style>
    /* ══════════ HERO ══════════ */
    .z-hero { position: relative; overflow: hidden; isolation: isolate; padding: 56px 0 64px; background: linear-gradient(180deg, #fff 0%, var(--soft) 55%, var(--page) 100%); }
    .z-hero::before { content: ''; position: absolute; inset: 0; z-index: -1; background-image: radial-gradient(color-mix(in srgb, var(--primary) 22%, transparent) 1px, transparent 1.3px); background-size: 24px 24px; mask-image: radial-gradient(50% 60% at 85% 30%, #000, transparent 80%); -webkit-mask-image: radial-gradient(50% 60% at 85% 30%, #000, transparent 80%); }
    .z-hero-in { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 56px; align-items: center; }
    .z-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px 6px 8px; border-radius: 999px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); font-size: 12.5px; font-weight: 700; color: var(--text); animation: z-rise .8s cubic-bezier(.16,1,.3,1) both; }
    .z-badge i { width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; background: var(--soft); color: var(--primary); }
    .z-badge .ico { width: 13px; height: 13px; }
    .z-hero h1 { text-wrap: balance; margin: 20px 0 16px; font-size: clamp(34px, 4.8vw, 60px); line-height: 1.05; font-weight: 800; letter-spacing: -.04em; color: var(--ink); animation: z-rise .9s cubic-bezier(.16,1,.3,1) both .08s; }
    .z-hero h1 .hl { background: linear-gradient(transparent 64%, color-mix(in srgb, var(--primary) 16%, transparent) 64%); }
    .z-hero-text > p { max-width: 560px; font-size: clamp(15px, 1.4vw, 17.5px); color: var(--muted); animation: z-rise .9s cubic-bezier(.16,1,.3,1) both .14s; }
    .z-hero .btns { margin-top: 28px; animation: z-rise .9s cubic-bezier(.16,1,.3,1) both .2s; }
    .z-hero .btn { padding: 14px 26px; font-size: 14.5px; }
    .z-trust { display: flex; align-items: center; gap: 14px; margin-top: 30px; padding-top: 24px; border-top: 1px dashed var(--line); animation: z-rise .9s cubic-bezier(.16,1,.3,1) both .26s; }
    .z-faces { display: flex; }
    .z-faces .ph { width: 40px; height: 40px; border-radius: 50%; border: 3px solid #fff; margin-left: -12px; box-shadow: 0 2px 6px rgba(16,24,40,.12); }
    .z-faces .ph:first-child { margin-left: 0; }
    .z-trust > div > b { display: block; color: var(--ink); font-size: 15px; line-height: 1.25; }
    .z-trust > div > span { display: block; color: var(--muted); font-size: 13px; }
    .z-art { position: relative; }
    .z-slider { position: relative; overflow: hidden; aspect-ratio: 5 / 4; border-radius: 30px; background: var(--soft); box-shadow: 0 30px 60px -30px rgba(16,24,40,.35); touch-action: pan-y pinch-zoom; border: 6px solid #fff; }
    .z-slider .slide { position: absolute; inset: 0; opacity: 0; transition: opacity 1s ease; }
    .z-slider .slide.on { opacity: 1; }
    .z-slider .slide > img { width: 100%; height: 100%; object-fit: cover; }
    .z-slider .slide.on > img { animation: z-kb 8s ease-out forwards; }
    @keyframes z-kb { from { transform: scale(1); } to { transform: scale(1.06); } }
    .z-slider .wm { display: none; }
    .z-slider .slide.is-mark .wm { display: flex; }
    .z-cap { position: absolute; left: 16px; right: 16px; bottom: 16px; padding: 14px 18px; border-radius: 18px; background: rgba(255,255,255,.9); -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); }
    .z-cap h3 { font-size: 16px; line-height: 1.3; font-weight: 800; color: var(--ink); }
    .z-cap p { font-size: 13px; color: var(--muted); margin-top: 2px; }
    .z-slider .sl-dots { position: absolute; top: 16px; right: 16px; display: flex; gap: 5px; padding: 6px 8px; border-radius: 999px; background: rgba(255,255,255,.85); }
    .z-slider .sl-dots button { width: 7px; height: 7px; padding: 0; border: 0; border-radius: 99px; background: #CBD5E1; cursor: pointer; transition: width .3s, background .3s; }
    .z-slider .sl-dots button.on { width: 20px; background: var(--primary); }
    .z-float { position: absolute; display: flex; align-items: center; gap: 12px; padding: 12px 16px 12px 12px; border-radius: 18px; background: #fff; border: 1px solid var(--line); box-shadow: 0 20px 40px -20px rgba(16,24,40,.3); animation: z-bob 6s ease-in-out infinite; }
    .z-float .badge-ico { width: 42px; height: 42px; border-radius: 13px; }
    .z-float .badge-ico .ico { width: 20px; height: 20px; }
    .z-float b { display: block; font-size: 14px; line-height: 1.25; color: var(--ink); }
    .z-float > span:last-child > span { display: block; font-size: 12px; color: var(--muted); }
    .z-float.f1 { left: -34px; top: 40px; }
    .z-float.f2 { right: -24px; bottom: 74px; animation-delay: -3s; }
    @keyframes z-bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

    /* ══════════ SECTIONS ══════════ */
    .z-sec { padding: 64px 0; }
    .sec-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px 30px; flex-wrap: wrap; margin-bottom: 30px; }

    /* At a glance */
    .dash { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
    .dash .s2 { grid-column: span 2; } .dash .s3 { grid-column: 1 / -1; }
    .dcard { position: relative; display: flex; flex-direction: column; padding: 22px; border-radius: 24px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); min-width: 0; }
    .dhead { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
    .dhead .badge-ico { width: 36px; height: 36px; border-radius: 11px; }
    .dhead .badge-ico .ico { width: 18px; height: 18px; }
    .dhead h3 { flex: 1; min-width: 0; font-size: 15.5px; font-weight: 800; color: var(--ink); }
    .dpill { padding: 5px 12px; border-radius: 999px; border: 1px solid var(--line); font-size: 12px; font-weight: 700; color: var(--text); white-space: nowrap; }
    .d-arrow { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; border: 1px solid var(--line); color: var(--ink); transition: background .2s, color .2s, transform .2s; }
    .d-arrow:hover { background: var(--primary); border-color: var(--primary); color: #fff; transform: rotate(45deg); }
    .d-arrow .ico { width: 15px; height: 15px; }
    .cal-grid { width: 100%; border-collapse: separate; border-spacing: 0 6px; text-align: center; font-size: 12.5px; }
    .cal-grid th { font-weight: 700; color: var(--muted); padding-bottom: 2px; }
    .cal-grid th span { display: inline-grid; place-items: center; width: 30px; height: 30px; border-radius: 50%; border: 1px solid var(--line); }
    .cal-grid td { padding: 0; }
    .cal-grid .d { display: inline-grid; place-items: center; width: 30px; height: 30px; border-radius: 50%; font-weight: 700; color: var(--text); }
    .cal-grid .d.has { background: var(--soft); color: var(--primary); }
    .cal-grid .d.today { box-shadow: inset 0 0 0 1.5px var(--ink); color: var(--ink); }
    .cal-legend { display: flex; gap: 16px; margin-top: 6px; font-size: 11.5px; font-weight: 700; color: var(--muted); }
    .cal-legend i { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 6px; vertical-align: -1px; }
    .cal-next { list-style: none; display: grid; gap: 8px; margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--line); }
    .cal-next li { display: flex; align-items: center; gap: 10px; font-size: 13px; }
    .cal-next .chip { flex: none; min-width: 58px; justify-content: center; }
    .cal-next b { color: var(--ink); font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cls-list { list-style: none; display: grid; gap: 14px; }
    .cls-list li { display: flex; align-items: center; gap: 12px; }
    .cls-list .badge-ico { width: 38px; height: 38px; border-radius: 12px; font-size: 14px; font-weight: 800; }
    .cls-list div { flex: 1; min-width: 0; }
    .cls-list small { display: block; font-size: 11.5px; color: var(--muted); font-weight: 600; }
    .cls-list b { display: block; font-size: 14px; color: var(--ink); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .prog { display: block; height: 5px; margin-top: 6px; border-radius: 99px; background: #EEF1F6; overflow: hidden; }
    .prog i { display: block; height: 100%; border-radius: 99px; background: var(--primary); }
    .cls-list .num { font-size: 12px; font-weight: 800; color: var(--muted); }
    .goals { list-style: none; display: grid; gap: 12px; flex: 1; }
    .goals li { display: flex; gap: 12px; align-items: flex-start; }
    .goals .g { flex: none; width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; background: #F1F4F9; color: #94A3B8; }
    .goals li:first-child .g { background: var(--primary); color: #fff; }
    .goals .g .ico { width: 15px; height: 15px; }
    .goals b { display: block; font-size: 13.5px; line-height: 1.35; color: var(--ink); }
    .goals li:not(:first-child) b { font-weight: 600; color: var(--text); }
    .goals span { display: block; font-size: 12px; color: var(--muted); }
    .dcard .btn-sm { align-self: flex-start; margin-top: 16px; }
    .gauge-box { position: relative; max-width: 260px; margin: 0 auto; width: 100%; }
    .gauge-box svg { display: block; width: 100%; height: auto; }
    .gauge-box line { stroke-width: 9; stroke-linecap: round; }
    .g-val { position: absolute; left: 25%; right: 25%; bottom: 2px; text-align: center; }
    .g-val b { display: block; font-size: 34px; line-height: 1; font-weight: 800; color: var(--ink); letter-spacing: -.03em; }
    .g-cap { margin-top: 8px; text-align: center; font-size: 12.5px; color: var(--muted); font-weight: 700; }
    .g-mini { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
    .g-mini div { padding: 10px 12px; border-radius: 14px; border: 1px solid var(--line); }
    .g-mini span { display: block; font-size: 11.5px; color: var(--muted); font-weight: 700; }
    .g-mini b { font-size: 20px; color: var(--ink); font-weight: 800; }
    .ylegend { display: flex; gap: 16px; margin: -4px 0 12px; font-size: 11.5px; font-weight: 700; color: var(--muted); }
    .ylegend i { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 6px; vertical-align: -1px; }
    .ybars { flex: 1; display: flex; align-items: stretch; gap: 12px; min-height: 200px; }
    .yb { flex: 1; min-width: 0; display: flex; flex-direction: column; align-items: center; gap: 8px; }
    .yb-track { position: relative; flex: 1; width: 100%; max-width: 54px; border-radius: 16px; background: repeating-linear-gradient(-45deg, #EEF1F6 0 4px, #fff 4px 8px); border: 1px solid #EEF1F6; overflow: hidden; }
    .yb-track i { position: absolute; left: 0; right: 0; bottom: 0; border-radius: 16px; background: linear-gradient(180deg, color-mix(in srgb, var(--primary) 60%, #fff), var(--primary)); }
    .yb-track em { position: absolute; left: 0; right: 0; top: 8px; text-align: center; font-style: normal; font-size: 11px; font-weight: 800; color: var(--ink); }
    .yb span { font-size: 11.5px; font-weight: 700; color: var(--muted); }
    .dcard.hi { justify-content: flex-end; min-height: 200px; color: #fff; border: 0; overflow: hidden; isolation: isolate; background: linear-gradient(140deg, color-mix(in srgb, var(--primary) 78%, #fff) 0%, var(--primary) 100%); }
    .dcard.hi::before { content: ''; position: absolute; right: -50px; top: -60px; width: 220px; height: 220px; border-radius: 50%; background: rgba(255,255,255,.14); z-index: -1; }
    .hi-tag { align-self: flex-start; display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px 5px 6px; border-radius: 999px; background: rgba(255,255,255,.2); font-size: 12px; font-weight: 800; margin-bottom: auto; }
    .hi-tag i { width: 16px; height: 16px; border-radius: 50%; border: 2px solid #fff; }
    .dcard.hi h3 { margin-top: 22px; font-size: 21px; line-height: 1.25; font-weight: 800; }
    .dcard.hi p { margin-top: 6px; font-size: 13.5px; opacity: .9; max-width: 560px; }
    .hi-dots { display: flex; gap: 5px; margin-top: 16px; }
    .hi-dots i { height: 4px; width: 22px; border-radius: 99px; background: rgba(255,255,255,.35); }
    .hi-dots i:first-child { width: 34px; background: #fff; }
    .dcard.hi .btn { margin-top: 16px; align-self: flex-start; }

    /* Welcome */
    .z-about { display: grid; grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr); gap: 60px; align-items: center; }
    .z-collage { position: relative; padding: 0 40px 50px 0; }
    .z-collage .big { aspect-ratio: 4 / 4.4; border-radius: 30px; box-shadow: 0 30px 60px -30px rgba(16,24,40,.35); }
    .z-collage .small { position: absolute; right: 0; bottom: 0; width: 46%; aspect-ratio: 1; border-radius: 24px; border: 6px solid #fff; box-shadow: 0 20px 40px -20px rgba(16,24,40,.4); }
    .z-collage .z-stat { position: absolute; left: -18px; bottom: 70px; padding: 14px 18px; border-radius: 18px; background: #fff; border: 1px solid var(--line); box-shadow: 0 20px 40px -20px rgba(16,24,40,.3); }
    .z-stat b { display: block; font-size: 26px; line-height: 1; font-weight: 800; color: var(--primary); }
    .z-stat span { display: block; margin-top: 4px; font-size: 12px; font-weight: 700; color: var(--muted); }
    .z-about-text > p { color: var(--text); margin-top: 16px; }
    .mini-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin: 22px 0 24px; }
    .mini-tiles div { display: flex; gap: 12px; align-items: flex-start; padding: 14px; border-radius: 18px; background: #fff; border: 1px solid var(--line); }
    .mini-tiles .badge-ico { width: 38px; height: 38px; border-radius: 12px; }
    .mini-tiles .badge-ico .ico { width: 18px; height: 18px; }
    .mini-tiles b { display: block; font-size: 14px; color: var(--ink); }
    .mini-tiles div > span:last-child > span { display: block; font-size: 12.5px; line-height: 1.45; color: var(--muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    /* Figures */
    .z-stats { display: grid; grid-template-columns: repeat({{ max(count($stats), 1) }}, minmax(0, 1fr)); gap: 18px; }
    .z-stats .stat { padding: 22px; border-radius: 22px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); }
    .z-stats .stat b { display: block; font-size: clamp(26px, 3vw, 38px); line-height: 1.1; font-weight: 800; letter-spacing: -.03em; color: var(--ink); overflow-wrap: anywhere; }
    .z-stats .stat > span:not(.badge-ico) { display: block; margin-top: 4px; font-size: 13px; font-weight: 600; color: var(--muted); }
    .z-stats .badge-ico { width: 38px; height: 38px; border-radius: 12px; margin-bottom: 14px; }
    .z-stats .badge-ico .ico { width: 18px; height: 18px; }

    /* Facilities */
    .z-fac { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
    .z-fac article { display: flex; flex-direction: column; padding: 12px; border-radius: 26px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); transition: transform .3s, box-shadow .3s; }
    .z-fac article:hover { transform: translateY(-5px); box-shadow: 0 26px 50px -26px rgba(16,24,40,.3); }
    .z-fac article:hover .ph > img { transform: scale(1.05); }
    .z-fac .ph { aspect-ratio: 16 / 10; border-radius: 18px; }
    .z-fac .wm small { font-size: 40px; letter-spacing: 0; opacity: 1; }
    .z-fac-body { padding: 16px 10px 8px; display: flex; gap: 14px; align-items: flex-start; }
    .z-fac-body .badge-ico { width: 44px; height: 44px; border-radius: 14px; font-size: 21px; }
    .z-fac-body h3 { font-size: 17px; font-weight: 800; color: var(--ink); }
    .z-fac-body p { font-size: 13.5px; color: var(--text); margin-top: 2px; }

    /* Messages */
    .z-msgs { display: grid; grid-template-columns: repeat({{ max(1, min(count($messages), 3)) }}, minmax(0, 1fr)); gap: 20px; }
    .z-msgs figure { display: flex; flex-direction: column; padding: 28px; border-radius: 26px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); }
    .z-msgs blockquote { flex: 1; font-size: 15px; line-height: 1.7; color: var(--text); }
    .z-msgs figcaption { display: flex; align-items: center; gap: 12px; margin-top: 20px; padding-top: 18px; border-top: 1px dashed var(--line); }
    .z-msgs figcaption .ph { width: 48px; height: 48px; border-radius: 50%; flex: none; }
    .z-msgs figcaption b { display: block; color: var(--ink); font-size: 15px; }
    .z-msgs figcaption span { display: block; font-size: 12.5px; color: var(--muted); }
    .z-msgs .stars { display: flex; gap: 2px; margin-bottom: 14px; color: #F59E0B; }
    .z-msgs .stars .ico { width: 16px; height: 16px; fill: currentColor; }

    /* Photo grid */
    .z-photos { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); grid-auto-rows: 220px; gap: 16px; }
    .z-photos.n6 > :nth-child(1) { grid-row: span 2; }
    .z-photos.n6 > :nth-child(5) { grid-column: span 2; }
    .z-photos figure { position: relative; overflow: hidden; border-radius: 24px; }
    .z-photos .ph { width: 100%; height: 100%; cursor: zoom-in; }
    .z-photos figure:hover .ph > img { transform: scale(1.05); }
    .z-photos figcaption { position: absolute; left: 12px; bottom: 12px; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.92); font-size: 12px; font-weight: 700; color: var(--ink); pointer-events: none; }

    /* Admission card */
    .z-cta { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: 40px; align-items: center; padding: 46px; border-radius: 34px; position: relative; overflow: hidden; isolation: isolate;
        background: linear-gradient(135deg, var(--soft) 0%, #fff 55%, color-mix(in srgb, #EC4899 6%, #fff) 100%); border: 1px solid var(--line); box-shadow: var(--shadow); }
    .z-cta::before { content: ''; position: absolute; right: -80px; top: -80px; width: 300px; height: 300px; border-radius: 50%; background: color-mix(in srgb, var(--primary) 10%, transparent); z-index: -1; }
    .z-cta h2 { text-wrap: balance; margin: 14px 0 10px; font-size: clamp(28px, 3.4vw, 44px); line-height: 1.08; font-weight: 800; letter-spacing: -.035em; color: var(--ink); }
    .z-cta-text > p { color: var(--text); max-width: 620px; }
    .z-cta-text > p + p { margin-top: 10px; }
    .z-cta .btns { margin-top: 24px; }
    .z-steps { list-style: none; display: grid; gap: 12px; counter-reset: st; }
    .z-steps .step { display: flex; gap: 14px; align-items: flex-start; padding: 16px; border-radius: 18px; background: #fff; border: 1px solid var(--line); counter-increment: st; }
    .z-steps .step::before { content: counter(st, decimal-leading-zero); flex: none; width: 34px; height: 34px; border-radius: 11px; display: grid; place-items: center; background: var(--soft); color: var(--primary); font-size: 12.5px; font-weight: 800; }
    .z-steps b { display: block; font-size: 14.5px; color: var(--ink); }
    .z-steps span { display: block; font-size: 13px; color: var(--muted); }

    /* Location */
    .z-loc { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); gap: 20px; padding: 14px; border-radius: 30px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); }
    .z-loc.nomap { grid-template-columns: minmax(0, 1fr); }
    .z-loc .info-list { padding: 14px; }
    .z-loc.nomap .info-list { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
    .z-loc .info-card { box-shadow: none; background: var(--page); border-color: transparent; }
    .z-loc .map-card { border-radius: 22px; }

    @media (max-width: 1020px) {
        .z-hero-in, .z-about, .z-cta { grid-template-columns: minmax(0, 1fr); }
        .z-art { max-width: 620px; }
        .z-float.f1 { left: 10px; } .z-float.f2 { right: 10px; }
        .dash { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dash .s2, .dash .s3 { grid-column: 1 / -1; }
        .z-fac { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .z-msgs { grid-template-columns: minmax(0, 1fr); }
        .z-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .z-collage { max-width: 560px; }
        .z-loc { grid-template-columns: minmax(0, 1fr); }
    }
    @media (max-width: 720px) {
        .z-hero { padding: 30px 0 40px; }
        .z-hero-in { gap: 34px; }
        .z-hero .btns .btn { flex: 1; }
        .z-slider { border-radius: 24px; border-width: 4px; aspect-ratio: 4 / 3.6; }
        .z-float { padding: 9px 12px 9px 9px; }
        .z-float .badge-ico { width: 34px; height: 34px; }
        .z-float.f1 { top: -18px; left: 8px; }
        .z-float.f2 { display: none; }
        .z-sec { padding: 40px 0; }
        .dash { grid-template-columns: minmax(0, 1fr); gap: 14px; }
        .dash > * { grid-column: auto !important; }
        .cal-grid .d, .cal-grid th span { width: 32px; height: 32px; }
        .z-about { gap: 30px; }
        .z-collage { padding: 0 24px 34px 0; }
        .z-collage .z-stat { left: 8px; bottom: 50px; }
        .mini-tiles { grid-template-columns: minmax(0, 1fr); }
        .z-fac { grid-template-columns: minmax(0, 1fr); }
        .z-photos, .z-photos.n6 { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: 150px; gap: 10px; }
        .z-photos.n6 > :nth-child(1) { grid-row: auto; }
        .z-photos.n6 > :nth-child(5) { grid-column: auto; }
        .z-photos figure { border-radius: 18px; }
        .z-cta { padding: 26px 20px; border-radius: 26px; gap: 24px; }
        .z-cta .btns .btn { flex: 1; }
        .z-loc { padding: 8px; border-radius: 24px; }
        .z-loc .info-list { padding: 8px; }
    }
</style>
@endpush

@section('content')

    {{-- ══════════ HERO ══════════ --}}
    <section class="z-hero">
        <div class="wrap z-hero-in">
            <div class="z-hero-text">
                <span class="z-badge"><i>@include('school-site.modern.partials.icon', ['n' => 'sparkle'])</i>{{ $badge }}</span>
                <h1>{!! ModernSite::highlight($c['hero_title'], $c['school_name']) !!}</h1>
                @if (!empty($c['hero_subtitle']))<p>{{ $c['hero_subtitle'] }}</p>@endif
                <div class="btns">
                    @if ($apply)<a class="btn" href="{{ $apply }}">Apply for Admission @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                    @if ($on('about'))<a class="btn btn-ghost" href="{{ url('about') }}">Explore the School</a>
                    @elseif ($tel !== '')<a class="btn btn-ghost" href="tel:{{ $tel }}">Call Us</a>@endif
                </div>
                @if ($faces || $range !== '' || $board !== '')
                    <div class="z-trust">
                        @if ($faces)
                            <span class="z-faces">
                                @foreach ($faces as $f)@include('school-site.classic.partials.ph', ['src' => $f['photo'], 'alt' => $f['name']])@endforeach
                            </span>
                        @endif
                        <div>
                            <b>{{ $range !== '' ? $range : ($board !== '' ? 'Affiliated to ' . $board : $c['school_name']) }}</b>
                            <span>{{ $faces ? 'Guided by a caring, experienced team' : (trim((string) ($c['medium'] ?? '')) ?: $c['tagline']) }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <div class="z-art">
                <div class="z-slider" data-slider="5500" aria-roledescription="carousel" aria-label="{{ $c['school_name'] }}">
                    @foreach ($slides as $i => $s)
                        @php $slideUrl = SchoolWebsite::media($s['image'] ?: null); @endphp
                        <div class="slide {{ $i === 0 ? 'on' : '' }} {{ $slideUrl ? '' : 'is-mark' }}" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($slides) }}">
                            @if ($slideUrl)
                                <img src="{{ $slideUrl }}" alt="{{ $s['title'] }}" @if ($i) loading="lazy" @endif onerror="this.parentNode.classList.add('is-mark');this.remove();">
                            @endif
                            @include('school-site.classic.partials.watermark', ['label' => null])
                            @if (trim($s['title'] . $s['subtitle']) !== '' && $s['title'] !== $c['hero_title'])
                                <div class="z-cap">
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
                    @endif
                </div>
                <div class="z-float f1">
                    <span class="badge-ico" style="{{ $sw(3) }}">@include('school-site.modern.partials.icon', ['n' => 'check'])</span>
                    <span><b>{{ $c['cta_heading'] }}</b><span>{{ $session !== '' ? $session : ($range !== '' ? $range : $c['school_name']) }}</span></span>
                </div>
                @if ($latest || $board !== '')
                    <div class="z-float f2">
                        <span class="badge-ico" style="{{ $sw(1) }}">@include('school-site.modern.partials.icon', ['n' => 'award'])</span>
                        @if ($latest)
                            <span><b>{{ rtrim(rtrim(number_format($latest['pct'], 1), '0'), '.') }}% pass</b><span>Board result {{ $latest['year'] }}</span></span>
                        @else
                            <span><b>Affiliated to {{ $board }}</b><span>{{ !empty($c['affiliation_no']) ? 'Aff. No. ' . $c['affiliation_no'] : $c['school_name'] }}</span></span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ══════════ AT A GLANCE ══════════ --}}
    <section class="z-sec">
        <div class="wrap">
            <div class="sec-head center">
                <span class="eyebrow">At a glance</span>
                <h2 class="h2">Everything about {{ trim($c['school_name']) }}, <span class="hl">in one place</span></h2>
            </div>
            <div class="dash">
                @foreach ($cards as [$key, $span])
                    @if ($key === 'cal')
                        <article class="dcard s{{ $span }}">
                            <header class="dhead">
                                <span class="badge-ico" style="{{ $sw(0) }}">@include('school-site.modern.partials.icon', ['n' => 'calendar'])</span>
                                <h3>School calendar</h3>
                                <span class="dpill">{{ $month['label'] }}</span>
                            </header>
                            <table class="cal-grid">
                                <thead><tr>@foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dn)<th><span>{{ $dn }}</span></th>@endforeach</tr></thead>
                                <tbody>
                                    @foreach ($month['weeks'] as $week)
                                        <tr>
                                            @foreach ($week as $day)
                                                <td>@if ($day)<span class="d {{ isset($month['events'][$day]) ? 'has' : '' }} {{ $month['today'] === $day ? 'today' : '' }}" @if (isset($month['events'][$day])) title="{{ implode(', ', $month['events'][$day]) }}" @endif>{{ $day }}</span>@endif</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="cal-legend"><span><i style="background:var(--soft);box-shadow:inset 0 0 0 1px var(--primary);"></i>Event day</span><span><i style="box-shadow:inset 0 0 0 1.5px var(--ink);"></i>Today</span></div>
                            @if ($next)
                                <ul class="cal-next">
                                    @foreach ($next as $i => $e)
                                        <li><span class="chip" style="{{ $sw($i + 1) }}">{{ Carbon::parse($e['date'])->format('d M') }}</span><b>{{ $e['title'] }}</b></li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>
                    @elseif ($key === 'cls')
                        <article class="dcard s{{ $span }}">
                            <header class="dhead">
                                <span class="badge-ico" style="{{ $sw(1) }}">@include('school-site.modern.partials.icon', ['n' => 'book'])</span>
                                <h3>Our classes</h3>
                                @if ($on('classes'))<a class="d-arrow" href="{{ url('classes') }}" aria-label="All classes">@include('school-site.modern.partials.icon', ['n' => 'arrow-ur'])</a>@endif
                            </header>
                            <ul class="cls-list">
                                @foreach ($clsRows as $i => $k)
                                    <li>
                                        <span class="badge-ico" style="{{ $sw($i + 2) }}">{{ mb_strtoupper(mb_substr(preg_replace('/^(class|std\.?|grade)\s+/i', '', $k['name']), 0, 2)) }}</span>
                                        <div><small>{{ $k['meta'] }}</small><b>{{ $k['name'] }}</b><span class="prog"><i style="width: {{ $k['fill'] }}%"></i></span></div>
                                        @if ($k['num'])<span class="num">{{ $k['num'] }}</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @elseif ($key === 'goals')
                        <article class="dcard s{{ $span }}">
                            <header class="dhead">
                                <span class="badge-ico" style="{{ $sw(3) }}">@include('school-site.modern.partials.icon', ['n' => 'check'])</span>
                                <h3>{{ $checksTitle }}</h3>
                            </header>
                            <ul class="goals">
                                @foreach ($checks as [$checkTitle, $desc])
                                    <li><span class="g">@include('school-site.modern.partials.icon', ['n' => $loop->first ? 'star' : 'check'])</span><span><b>{{ $checkTitle }}</b>@if ($desc !== '')<span>{{ $desc }}</span>@endif</span></li>
                                @endforeach
                            </ul>
                            @if ($apply)<a class="btn btn-sm" href="{{ $apply }}">Apply Now @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                        </article>
                    @elseif ($key === 'gauge')
                        <article class="dcard s{{ $span }}">
                            <header class="dhead">
                                <span class="badge-ico" style="{{ $sw(2) }}">@include('school-site.modern.partials.icon', ['n' => 'award'])</span>
                                <h3>Board result</h3>
                                @if ($on('result'))<a class="d-arrow" href="{{ url('result') }}" aria-label="All results">@include('school-site.modern.partials.icon', ['n' => 'arrow-ur'])</a>@endif
                            </header>
                            <div class="gauge-box">
                                <svg viewBox="0 0 220 122" aria-hidden="true">{!! $gauge !!}</svg>
                                <div class="g-val"><b data-count>{{ rtrim(rtrim(number_format($latest['pct'], 1), '0'), '.') }}%</b></div>
                            </div>
                            <p class="g-cap">Pass percentage · {{ $latest['year'] }}</p>
                            <div class="g-mini">
                                <div><span>Appeared</span><b>{{ number_format($latest['appeared']) }}</b></div>
                                <div><span>Passed</span><b>{{ number_format($latest['passed']) }}</b></div>
                            </div>
                        </article>
                    @elseif ($key === 'years')
                        <article class="dcard s{{ $span }}">
                            <header class="dhead">
                                <span class="badge-ico" style="{{ $sw(4) }}">@include('school-site.modern.partials.icon', ['n' => 'chart'])</span>
                                <h3>Results over the years</h3>
                                <span class="dpill">{{ $yearBars[0]['year'] }} – {{ end($yearBars)['year'] }}</span>
                            </header>
                            <div class="ylegend"><span><i style="background:repeating-linear-gradient(-45deg,#E2E8F0 0 2px,#fff 2px 4px);box-shadow:inset 0 0 0 1px #E2E8F0;"></i>100%</span><span><i style="background:var(--primary);"></i>Pass percentage</span></div>
                            <div class="ybars" role="img" aria-label="Pass percentage by year">
                                @foreach ($yearBars as $r)
                                    <div class="yb"><div class="yb-track"><i style="height: {{ max(3, $r['pct']) }}%"></i><em>{{ rtrim(rtrim(number_format($r['pct'], 1), '0'), '.') }}%</em></div><span>{{ $r['year'] }}</span></div>
                                @endforeach
                            </div>
                        </article>
                    @else
                        <article class="dcard hi s{{ $span }}">
                            <span class="hi-tag"><i></i>{{ $session !== '' ? $session : 'Admissions' }}</span>
                            <h3>{{ $c['cta_heading'] }}</h3>
                            <p>{{ $c['cta_text'] }}</p>
                            <div class="hi-dots" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                            @if ($apply)<a class="btn btn-white btn-sm" href="{{ $apply }}">Enquire Now @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                        </article>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════ WELCOME ══════════ --}}
    @php
        $vm = array_filter([
            ['eye', 'Vision', $c['vision'] ?? ''], ['target', 'Mission', $c['mission'] ?? ''],
            ['heart', 'Values', $c['values'] ?? ''], ['flag', 'Goals', $c['goals'] ?? ''],
            ['sparkle', 'Philosophy', $c['philosophy'] ?? ''],
        ], fn ($t) => trim((string) $t[2]) !== '');
        $vm = array_slice(array_values($vm), 0, 4);
    @endphp
    <section class="z-sec" style="padding-top:20px;">
        <div class="wrap z-about">
            <div class="z-collage">
                @include('school-site.classic.partials.ph', ['src' => $pool[0] ?? null, 'alt' => $c['school_name'], 'class' => 'big'])
                @if (count($pool) > 1)
                    @include('school-site.classic.partials.ph', ['src' => $pool[1], 'alt' => $c['school_name'], 'class' => 'small'])
                @endif
                @if ($stats)
                    <div class="z-stat"><b data-count>{{ $stats[0]['value'] }}</b><span>{{ $stats[0]['label'] }}</span></div>
                @endif
            </div>
            <div class="z-about-text">
                <span class="eyebrow">Welcome</span>
                <h2 class="h2">{!! ModernSite::highlight($welcome, $c['school_name']) !!}</h2>
                @if ($lead !== '')<p><b style="color:var(--ink)">{{ $lead }}</b></p>@endif
                <p>{{ ModernSite::excerpt(trim($c['about_text'] . ' ' . ($c['about_text2'] ?? '')), 420) }}</p>
                @if ($vm)
                    <div class="mini-tiles">
                        @foreach ($vm as $i => [$icon, $vmTitle, $text])
                            <div><span class="badge-ico" style="{{ $sw($i) }}">@include('school-site.modern.partials.icon', ['n' => $icon])</span><span style="display:block;min-width:0;"><b>{{ $vmTitle }}</b><span>{{ $text }}</span></span></div>
                        @endforeach
                    </div>
                @endif
                <div class="btns" @if (! $vm) style="margin-top:24px;" @endif>
                    @if ($on('about'))<a class="btn" href="{{ url('about') }}">Read More @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                    @if ($on('appointment'))<a class="btn btn-ghost" href="{{ url('appointment') }}">Book a Visit</a>@endif
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════ FIGURES ══════════ --}}
    @if (count($stats) > 1)
        <section class="z-sec" style="padding-top:10px;padding-bottom:30px;">
            <div class="wrap">
                <div class="z-stats">
                    @foreach ($stats as $i => $s)
                        <div class="stat">
                            <span class="badge-ico" style="{{ $sw($i) }}">@include('school-site.modern.partials.icon', ['n' => ['school', 'award', 'star', 'users'][$i % 4]])</span>
                            <b data-count>{{ $s['value'] }}</b><span>{{ $s['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ FEATURES & FACILITIES ══════════ --}}
    <section class="z-sec">
        <div class="wrap">
            <div class="sec-row">
                <div>
                    <span class="eyebrow">Features &amp; Facilities</span>
                    <h2 class="h2">A campus made for <span class="hl">curious minds</span></h2>
                    <p class="lead-text" style="margin-top:10px;">At {{ $c['school_name'] }}, we are committed to the best facilities for the overall development of your child.</p>
                </div>
                @if ($on('facilities'))<a class="btn btn-ghost" href="{{ url('facilities') }}">All facilities @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
            </div>
            <div class="z-fac">
                @foreach ($facilities as $i => $f)
                    <article>
                        @include('school-site.classic.partials.ph', ['src' => $f['image'] ?? null, 'alt' => $f['title'] ?? '', 'label' => $f['icon'] ?? null])
                        <div class="z-fac-body">
                            <span class="badge-ico" style="{{ $sw($i) }}">{{ $f['icon'] ?? '✦' }}</span>
                            <div><h3>{{ $f['title'] ?? '' }}</h3><p>{{ $f['desc'] ?? '' }}</p></div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════ MESSAGES ══════════ --}}
    @if ($messages)
        <section class="z-sec">
            <div class="wrap">
                <div class="sec-head center">
                    <span class="eyebrow">Messages</span>
                    <h2 class="h2">Words from our <span class="hl">leadership</span></h2>
                </div>
                <div class="z-msgs">
                    @foreach ($messages as $m)
                        <figure class="quote-card">
                            <span class="quote-mark">@include('school-site.modern.partials.icon', ['n' => 'quote'])</span>
                            <blockquote>“{{ ModernSite::excerpt($m['message'], count($messages) === 1 ? 520 : 260) }}”</blockquote>
                            <figcaption>
                                @if ($m['photo'] !== '')@include('school-site.classic.partials.ph', ['src' => $m['photo'], 'alt' => $m['name']])@endif
                                <span style="flex:1;min-width:0;"><b>{{ $m['name'] ?: $m['role'] }}</b>@if ($m['name'] !== '' && $m['role'] !== '')<span>{{ $m['role'] }}</span>@endif</span>
                                @if ($m['url'])<a class="d-arrow" href="{{ url($m['url']) }}" aria-label="Read the full message">@include('school-site.modern.partials.icon', ['n' => 'arrow-ur'])</a>@endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ PHOTOS ══════════ --}}
    @if (count($gallery) >= 3)
        <section class="z-sec" style="padding-top:20px;">
            <div class="wrap">
                <div class="sec-row">
                    <div>
                        <span class="eyebrow">Photogallery</span>
                        <h2 class="h2">Moments from <span class="hl">our campus</span></h2>
                    </div>
                    @if ($on('gallery'))<a class="btn btn-ghost" href="{{ url('gallery') }}">View all photos @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                </div>
                <div class="z-photos mosaic n{{ count($gallery) }}">
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
    <section class="z-sec">
        <div class="wrap">
            <div class="z-cta">
                <div class="z-cta-text">
                    <span class="eyebrow">{{ $session !== '' ? 'Admission Schedule · ' . $session : 'Admissions' }}</span>
                    <h2>{!! ModernSite::highlight($c['cta_heading'], '') !!}</h2>
                    <p>{{ $c['cta_text'] }}</p>
                    @if (!empty($c['admission_intro']))<p>{{ $c['admission_intro'] }}</p>@endif
                    <div class="btns">
                        @if ($on('admission'))<a class="btn" href="{{ url('admission') }}">Apply Online @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                        @if ($tel !== '')<a class="btn btn-ghost" href="tel:{{ $tel }}">@include('school-site.modern.partials.icon', ['n' => 'phone']) Call {{ trim(explode(',', $c['phone'])[0]) }}</a>@endif
                    </div>
                </div>
                @if ($steps || $docs)
                    <ol class="z-steps">
                        @if ($steps)
                            @foreach (array_slice($steps, 0, 4) as $st)
                                <li class="step"><span><b>{{ $st['title'] }}</b>@if (!empty($st['desc']))<span>{{ $st['desc'] }}</span>@endif</span></li>
                            @endforeach
                        @else
                            <li class="step"><span><b>Documents required</b><span>{{ implode(' · ', array_slice($docs, 0, 8)) }}</span></span></li>
                        @endif
                    </ol>
                @else
                    <div class="info-list">
                        @if (!empty($c['phone']))<div class="info-card"><span class="badge-ico" style="{{ $sw(0) }}">@include('school-site.modern.partials.icon', ['n' => 'phone'])</span><div><span>Admission office</span><a href="tel:{{ $tel }}">{{ $c['phone'] }}</a></div></div>@endif
                        @if (!empty($c['email']))<div class="info-card"><span class="badge-ico" style="{{ $sw(2) }}">@include('school-site.modern.partials.icon', ['n' => 'mail'])</span><div><span>Write to us</span><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></div></div>@endif
                        @if ($range !== '')<div class="info-card"><span class="badge-ico" style="{{ $sw(4) }}">@include('school-site.modern.partials.icon', ['n' => 'school'])</span><div><span>Classes</span><b>{{ $range }}</b></div></div>@endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ══════════ LOCATION ══════════ --}}
    <section class="z-sec" style="padding-top:20px;">
        <div class="wrap">
            <div class="sec-head center">
                <span class="eyebrow">Visit Us</span>
                <h2 class="h2">Our <span class="hl">Location</span></h2>
                <p class="lead-text">Reach us at the school or just drop in an email{{ !empty($c['email']) ? ' — ' . $c['email'] : '' }}.</p>
            </div>
            <div class="z-loc {{ $mapSrc ? '' : 'nomap' }}">
                <div class="info-list">
                    @if (!empty($c['address']))<div class="info-card"><span class="badge-ico" style="{{ $sw(0) }}">@include('school-site.modern.partials.icon', ['n' => 'pin'])</span><div><span>Address</span><b>{{ $c['address'] }}</b></div></div>@endif
                    @if (!empty($c['phone']))<div class="info-card"><span class="badge-ico" style="{{ $sw(1) }}">@include('school-site.modern.partials.icon', ['n' => 'phone'])</span><div><span>Call us</span><a href="tel:{{ $tel }}">{{ $c['phone'] }}</a></div></div>@endif
                    @if (!empty($c['email']))<div class="info-card"><span class="badge-ico" style="{{ $sw(2) }}">@include('school-site.modern.partials.icon', ['n' => 'mail'])</span><div><span>Email</span><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></div></div>@endif
                    @if (!empty($c['affiliation_no']) || !empty($c['school_code']))
                        <div class="info-card"><span class="badge-ico" style="{{ $sw(3) }}">@include('school-site.modern.partials.icon', ['n' => 'award'])</span><div><span>{{ $board ?: 'CBSE' }} Affiliation</span><b>{{ trim(($c['affiliation_no'] ? 'Aff. No. ' . $c['affiliation_no'] : '') . ($c['affiliation_no'] && $c['school_code'] ? ' · ' : '') . ($c['school_code'] ? 'School Code ' . $c['school_code'] : '')) }}</b></div></div>
                    @endif
                </div>
                @if ($mapSrc)
                    <div class="map-card">@include('school-site.classic.partials.map', ['height' => 420])</div>
                @endif
            </div>
        </div>
    </section>

@endsection
