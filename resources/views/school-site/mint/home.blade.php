{{--
    Mint's home page, laid out as the superlms.in home page is, with the
    school's own content: the hero (a badge pill, the headline with the
    school's name in the gradient, pill buttons, a proof row, and an app
    window holding the photo slider and the school's figures, with floating
    chips); a band of the school's facts; the welcome beside a photo on a
    pastel stage; what the school stands for as pastel cards; the figures as
    big counters; the facility cards on coloured panels; "at a glance" cards
    with small screens of the calendar, classes, board results and how to
    apply; messages from the leadership; a photo grid; admissions as an FAQ;
    the mint call-to-action band with a call-back form; and the map.
    Every part reads Classic's content and shows only when the school has it.
--}}
@extends('school-site.mint.layout')
@php
    use App\Models\SchoolWebsite;
    use App\Support\ModernSite;
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $stored     = $site->content ?? [];
    $sw         = fn ($i) => ModernSite::swatch('mint', $i, $theme['primary']);
    $on         = fn ($slug) => $site->isPageEnabled($slug);
    $pct        = fn ($v) => rtrim(rtrim(number_format($v, 1), '0'), '.') . '%';
    $slides     = ModernSite::slides($c);
    $pool       = ModernSite::photos($c);
    $facilities = array_slice(ModernSite::facilities($c), 0, 6);
    $messages   = array_slice(ModernSite::messages($site, $c), 0, 3);
    $stats      = ModernSite::stats($c, $lms);
    $faces      = ModernSite::faces($c, 4);
    $points     = array_slice(ModernSite::points($site, $c), 0, 4);
    $range      = ModernSite::classRange($lms, $c);
    $events     = $lms->events();
    $month      = $events ? ModernSite::month($events) : null;
    $next       = ModernSite::upcoming($events, 3);
    $classes    = $lms->classes();
    $res        = ModernSite::results($c['results'] ?? []);
    $latest     = $res['latest'];
    $gallery    = array_slice(ModernSite::gallery($c), 0, 6);
    $tel        = ModernSite::tel($c);
    $mapSrc     = SchoolWebsite::mapSrc($c);
    $welcome    = !empty($stored['about_heading']) ? $c['about_heading'] : 'Welcome to ' . $c['school_name'];
    $lead       = trim((string) ($stored['tagline'] ?? ''));
    $board      = trim((string) ($c['board'] ?? ''));
    $medium     = trim((string) ($c['medium'] ?? ''));
    $session    = trim((string) ($c['admission_session'] ?? ''));
    $badge      = $session !== '' ? 'Admissions open · ' . $session
                : ($board !== '' ? $board . ' affiliated school' : (trim((string) ($c['tagline'] ?? '')) ?: 'Quality education'));
    $apply      = $on('admission') ? url('admission') : ($on('contact') ? url('contact') : null);
    $docs       = array_values(array_filter(array_map(fn ($d) => trim((string) ($d['text'] ?? '')), $c['documents_required'] ?? [])));
    $steps      = array_values(array_filter($c['admission_steps'] ?? [], fn ($s) => trim((string) ($s['title'] ?? '')) !== ''));
    $boardUrl   = SchoolWebsite::media($c['board_logo'] ?? null);

    // The proof row under the buttons: the board, the classes, the medium, the
    // result — two when the class range is long, so the row stays on one line.
    $proof = array_slice(array_values(array_filter([
        $board !== '' ? [$board, 'Affiliated'] : null,
        $range !== '' ? [$range, 'Classes'] : null,
        $medium !== '' && mb_strlen($medium) <= 24 ? [$medium, 'Medium'] : null,
        $latest ? [$pct($latest['pct']), 'Board result'] : null,
    ])), 0, mb_strlen($range) > 22 ? 2 : 3);

    // The figures in the hero's app window: the school's counters, else the proof row's.
    $kpis = $stats ? array_slice($stats, 0, 3) : array_map(fn ($p) => ['value' => $p[0], 'label' => $p[1]], $proof);

    // The band of facts under the hero (the admission dates are in the strip, the badge and the band below).
    $facts = array_values(array_filter([
        $board !== '' ? ['award', 'Affiliated to', $board] : null,
        !empty($c['affiliation_no']) ? ['file', 'Affiliation No.', $c['affiliation_no']] : null,
        !empty($c['school_code']) ? ['grid', 'School Code', $c['school_code']] : null,
        $medium !== '' ? ['book', 'Medium', Str::limit($medium, 30)] : null,
        $range !== '' ? ['users', 'Classes', $range] : null,
    ]));

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
        ]));
    }
    $checks = array_slice($checks, 0, 4);

    // The classes: the Standard page's classes, else Website Data's class cards.
    $clsRows = [];
    if ($classes) {
        $most = max(1, ...array_map(fn ($k) => count($k['subjects']), $classes));
        foreach (array_slice($classes, 0, 4) as $k) {
            $clsRows[] = ['name' => $k['name'],
                'meta' => count($k['sections']) . ' ' . Str::plural('section', count($k['sections'])) . ' · ' . count($k['subjects']) . ' ' . Str::plural('subject', count($k['subjects'])),
                'fill' => max(8, (int) round(count($k['subjects']) / $most * 100))];
        }
    } else {
        foreach (array_slice(array_values(array_filter($c['classes'] ?? [], fn ($k) => trim((string) ($k['title'] ?? '')) !== '')), 0, 4) as $i => $k) {
            $clsRows[] = ['name' => $k['title'], 'meta' => implode(' · ', array_filter([$k['age'] ?? '', $k['time'] ?? ''])) ?: 'Class', 'fill' => 100 - $i * 16];
        }
    }
    $yearBars = array_slice($res['rows'], -5);

    // "At a glance": a card a part the school has, each with its small screen.
    $glance = array_values(array_filter([
        $next ? 'cal' : null,
        $clsRows ? 'cls' : null,
        $yearBars ? 'res' : null,
        $checks ? 'apply' : null,
    ]));
    $stages = ['bg-peach', 'bg-sky', 'bg-mint', 'bg-lilac'];

    $pointIcon = function (string $title) {
        foreach (['Philosophy' => 'sparkle', 'Vision' => 'eye', 'Mission' => 'target', 'Values' => 'heart', 'Goals' => 'flag', 'History' => 'clock'] as $word => $icon) {
            if (stripos($title, $word) !== false) { return $icon; }
        }
        return 'school';
    };
@endphp

@push('styles')
<style>
    /* ══════════ HERO ══════════ */
    .m-hero { position: relative; isolation: isolate; overflow: hidden; padding: 60px 0 84px; }
    .m-hero::after { content: ''; position: absolute; z-index: -1; right: -10%; top: 4%; width: 760px; height: 640px; pointer-events: none; background: radial-gradient(closest-side, color-mix(in srgb, var(--primary) 15%, transparent), color-mix(in srgb, var(--primary) 5%, transparent) 55%, transparent 80%); }
    .m-hero-in { display: grid; grid-template-columns: minmax(0, 1.12fr) minmax(0, 1fr); gap: 48px; align-items: center; }
    .m-badge { display: inline-flex; align-items: center; gap: 10px; padding: 6px 14px 6px 8px; margin-bottom: 24px; border-radius: 999px; background: #fff; border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(12,38,32,.06), 0 1px 3px rgba(12,38,32,.05); font-size: 14px; font-weight: 500; color: var(--text); transition: border-color .2s; animation: m-rise .8s var(--ease) both; }
    a.m-badge:hover { border-color: var(--soft-2); }
    .m-badge .ico { width: 15px; height: 15px; color: var(--primary); }
    .bp-dot { position: relative; flex: none; width: 22px; height: 22px; border-radius: 50%; background: var(--soft); display: grid; place-items: center; }
    .bp-dot::after { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--primary); animation: m-pulse 2s infinite; }
    @keyframes m-pulse { 0% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--primary) 50%, transparent); } 70% { box-shadow: 0 0 0 8px transparent; } 100% { box-shadow: 0 0 0 0 transparent; } }
    .m-hero h1 { font-family: var(--display); font-size: clamp(38px, 4.2vw, 58px); line-height: 1.05; font-weight: 800; letter-spacing: -.045em; color: var(--ink); text-wrap: balance; animation: m-rise .9s var(--ease) both .06s; }
    .m-hero-text > p { margin-top: 22px; max-width: 560px; font-size: clamp(16px, 1.5vw, 19px); line-height: 1.62; color: var(--text); animation: m-rise .9s var(--ease) both .12s; }
    .m-hero .btns { margin-top: 32px; gap: 12px; animation: m-rise .9s var(--ease) both .18s; }
    .m-proof { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 18px; margin-top: 26px; font-size: 14.5px; color: var(--muted); animation: m-rise .9s var(--ease) both .24s; }
    .m-proof b { color: var(--ink); font-weight: 600; }
    .m-proof .pf { display: inline-flex; align-items: center; gap: 6px; }
    .m-proof .pf + .pf::before { content: ''; width: 4px; height: 4px; border-radius: 50%; background: var(--faint); margin-right: 12px; }
    .m-faces { display: flex; margin-right: 4px; }
    .m-faces .ph { width: 34px; height: 34px; border-radius: 50%; border: 2px solid #fff; margin-left: -10px; box-shadow: 0 2px 6px rgba(12,38,32,.14); }
    .m-faces .ph:first-child { margin-left: 0; }

    .m-art { position: relative; animation: m-rise 1s var(--ease) both .15s; }
    .m-app { position: relative; border-radius: 26px; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow-lg); overflow: hidden; }
    .m-app-bar { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-bottom: 1px solid var(--line); background: #fbfcfc; }
    .m-app-bar .dots { display: flex; gap: 6px; }
    .m-app-bar .dots i { width: 10px; height: 10px; border-radius: 50%; background: #e3e8e6; }
    .m-app-bar .dots i:first-child { background: #f0506e; } .m-app-bar .dots i:nth-child(2) { background: #f5a524; } .m-app-bar .dots i:nth-child(3) { background: #22a75a; }
    .m-url { flex: 1; min-width: 0; height: 28px; display: flex; align-items: center; gap: 6px; padding: 0 12px; border-radius: 999px; background: var(--grey); font-size: 12.5px; color: var(--muted); overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
    .m-url .ico { width: 13px; height: 13px; color: var(--primary); }
    .m-live { display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 10px; border-radius: 999px; background: var(--soft); color: var(--deep); font-size: 12px; font-weight: 600; flex: none; }
    .m-live::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: var(--primary); animation: m-pulse 1.8s infinite; }
    .m-app-body { padding: 14px; }
    .m-slider { position: relative; overflow: hidden; aspect-ratio: 16 / 11; border-radius: 18px; background: var(--soft); touch-action: pan-y pinch-zoom; }
    .m-slider .slide { position: absolute; inset: 0; opacity: 0; transition: opacity 1s ease; }
    .m-slider .slide.on { opacity: 1; }
    .m-slider .slide > img { width: 100%; height: 100%; object-fit: cover; }
    .m-slider .slide.on > img { animation: m-kb 8s ease-out forwards; }
    @keyframes m-kb { from { transform: scale(1); } to { transform: scale(1.06); } }
    .m-slider .wm { display: none; }
    .m-slider .slide.is-mark .wm { display: flex; }
    .m-cap { position: absolute; left: 12px; right: 12px; bottom: 12px; padding: 12px 16px; border-radius: 16px; background: rgba(255,255,255,.9); -webkit-backdrop-filter: blur(10px); backdrop-filter: blur(10px); }
    .m-cap h3 { font-family: var(--display); font-size: 15.5px; line-height: 1.3; font-weight: 700; letter-spacing: -.02em; color: var(--ink); }
    .m-cap p { font-size: 12.5px; color: var(--muted); margin-top: 2px; }
    .m-slider .sl-dots { position: absolute; top: 12px; right: 12px; display: flex; gap: 5px; padding: 6px 8px; border-radius: 999px; background: rgba(255,255,255,.85); }
    .m-slider .sl-dots button { width: 7px; height: 7px; padding: 0; border: 0; border-radius: 99px; background: var(--faint); cursor: pointer; transition: width .3s, background .3s; }
    .m-slider .sl-dots button.on { width: 20px; background: var(--primary); }
    .m-kpis { display: grid; grid-template-columns: repeat(var(--n, 3), minmax(0, 1fr)); gap: 10px; margin-top: 12px; }
    .m-kpi { padding: 12px 14px; border-radius: 16px; background: var(--grey); min-width: 0; }
    .m-kpi b { display: block; font-family: var(--display); font-size: clamp(17px, 1.8vw, 23px); line-height: 1.15; font-weight: 800; letter-spacing: -.035em; color: var(--ink); overflow-wrap: anywhere; }
    .m-kpi:first-child b { color: var(--primary); }
    .m-kpi span { display: block; margin-top: 3px; font-size: 12px; color: var(--muted); line-height: 1.3; }
    .m-chip { position: absolute; z-index: 2; display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px 10px 10px; border-radius: 16px; background: #fff; box-shadow: 0 14px 30px -14px rgba(12,38,32,.35), 0 1px 3px rgba(12,38,32,.06); animation: m-bob 6s ease-in-out infinite; max-width: 260px; }
    .m-chip .badge-ico { width: 36px; height: 36px; border-radius: 11px; }
    .m-chip .badge-ico .ico { width: 18px; height: 18px; }
    .m-chip b { display: block; font-size: 13.5px; line-height: 1.25; color: var(--ink); font-weight: 600; }
    .m-chip > span:last-child > span { display: block; font-size: 12px; color: var(--muted); }
    .m-chip.c1 { left: -30px; top: 84px; }
    .m-chip.c2 { right: -22px; bottom: 96px; animation-delay: -3s; }
    @keyframes m-bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

    /* ══════════ FACTS BAND ══════════ */
    .m-facts { padding: 26px 0; background: linear-gradient(90deg, #eef7fb 0%, color-mix(in srgb, var(--primary) 6%, #f1fbf6) 45%, #f6f8ee 100%); }
    .m-facts ul { list-style: none; display: grid; grid-template-columns: repeat(var(--n, 4), minmax(0, 1fr)); gap: 16px 28px; }
    .m-facts li { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .m-facts .badge-ico { width: 42px; height: 42px; border-radius: 13px; background: #fff; color: var(--primary); box-shadow: 0 1px 2px rgba(12,38,32,.06); }
    .m-facts .badge-ico .ico { width: 19px; height: 19px; }
    .m-facts .badge-ico img { width: 30px; height: 30px; object-fit: contain; }
    .m-facts span { display: block; font-size: 12px; color: var(--muted); line-height: 1.25; }
    .m-facts b { display: block; font-size: 15px; color: var(--ink); font-weight: 600; line-height: 1.3; }

    /* ══════════ SECTIONS ══════════ */
    .m-sec { padding: 96px 0; }
    .m-sec.soft { background: var(--grey); }
    .m-head { max-width: 820px; margin: 0 auto 50px; text-align: center; }
    .m-head .lead-text { max-width: 720px; margin: 18px auto 0; }
    .m-head-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px 30px; flex-wrap: wrap; margin-bottom: 40px; }
    .m-head-row .lead-text { margin-top: 14px; }

    /* Welcome */
    .m-about { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 64px; align-items: center; }
    .m-about-text > p { margin-top: 18px; color: var(--text); font-size: 16.5px; line-height: 1.7; }
    .m-about-text > p.m-lead { color: var(--ink); font-weight: 600; }
    .m-about .btns { margin-top: 28px; }
    .m-stage { position: relative; padding: 24px; border-radius: 34px; background: radial-gradient(120% 120% at 100% 0%, rgba(255,255,255,.55), transparent 55%), color-mix(in srgb, var(--primary) 14%, #eef7f3); }
    .m-stage .ph { aspect-ratio: 4 / 3.3; border-radius: 22px; box-shadow: 0 24px 50px -28px rgba(12,38,32,.45); }
    .m-stage .m-chip { position: absolute; animation: none; }
    .m-stage .m-chip.s1 { left: -22px; bottom: 44px; }
    .m-stage .m-chip.s2 { right: -16px; top: 46px; }
    .m-stage .m-chip b.big { font-family: var(--display); font-size: 22px; font-weight: 800; letter-spacing: -.03em; color: var(--primary); }

    /* What we stand for: pastel cards */
    .m-points { display: grid; grid-template-columns: repeat(var(--n, 3), minmax(0, 1fr)); gap: 22px; }
    .m-pcard { display: flex; flex-direction: column; background: #fff; border-radius: 26px; overflow: hidden; box-shadow: var(--shadow); transition: transform .4s var(--ease), box-shadow .4s var(--ease); }
    .m-pcard:hover { transform: translateY(-6px); box-shadow: 0 2px 4px rgba(12,38,32,.05), 0 30px 60px -30px rgba(12,38,32,.38); }
    .m-pstage { position: relative; height: 150px; margin: 10px 10px 0; border-radius: 20px; overflow: hidden; display: grid; place-items: center; }
    .m-pstage::before, .m-pstage::after { content: ''; position: absolute; border-radius: 50%; background: rgba(255,255,255,.35); }
    .m-pstage::before { width: 160px; height: 160px; left: -40px; bottom: -70px; }
    .m-pstage::after { width: 90px; height: 90px; right: 18px; top: -30px; }
    .m-pstage .m-pic { position: relative; z-index: 1; width: 66px; height: 66px; border-radius: 20px; display: grid; place-items: center; background: #fff; color: var(--ink); box-shadow: 0 16px 30px -14px rgba(12,38,32,.4); }
    .m-pstage .m-pic .ico { width: 28px; height: 28px; }
    .bg-peach { background: linear-gradient(160deg, #fde6cf 0%, #f7c9a0 100%); }
    .bg-sky { background: linear-gradient(160deg, #dbe9ff 0%, #a9c6f7 100%); }
    .bg-mint { background: linear-gradient(160deg, color-mix(in srgb, var(--primary) 12%, #e3f7ef) 0%, color-mix(in srgb, var(--primary) 34%, #b8e8da) 100%); }
    .bg-lilac { background: linear-gradient(160deg, #ece3ff 0%, #c4adf6 100%); }
    .m-pbody { padding: 20px 24px 26px; flex: 1; display: flex; flex-direction: column; }
    .m-kicker { font-family: var(--display); font-size: 12px; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; color: var(--primary); }
    .m-pbody h3 { margin: 6px 0 8px; font-family: var(--display); font-size: 19.5px; font-weight: 700; letter-spacing: -.02em; line-height: 1.3; color: var(--ink); }
    .m-pbody p { font-size: 15px; line-height: 1.62; color: var(--muted); flex: 1; }
    .m-more { display: inline-flex; align-items: center; gap: 6px; margin-top: 14px; font-size: 14.5px; font-weight: 600; color: var(--deep); }
    .m-more .ico { width: 15px; height: 15px; transition: transform .25s var(--ease); }
    .m-more:hover .ico { transform: translateX(3px); }

    /* Figures */
    .m-stats { display: grid; grid-template-columns: repeat(var(--n, 4), minmax(0, 1fr)); gap: 14px; padding: 14px; border-radius: 36px; background: var(--grey); }
    .m-stat { display: flex; flex-direction: column; justify-content: space-between; min-height: 170px; padding: 28px 26px; border-radius: 26px; background: #fff; }
    .m-stat b { font-family: var(--display); font-size: clamp(32px, 3.8vw, 54px); line-height: 1; font-weight: 800; letter-spacing: -.045em; color: var(--ink); overflow-wrap: anywhere; }
    .m-stat:first-child b { color: var(--primary); }
    .m-stat span { display: flex; align-items: center; gap: 8px; margin-top: 18px; font-size: 15px; color: var(--muted); }
    .m-stat span .ico { width: 18px; height: 18px; color: var(--primary); }

    /* Facilities on coloured panels */
    .m-fac { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; }
    .m-fcard { display: flex; flex-direction: column; background: #fff; border-radius: 26px; overflow: hidden; box-shadow: var(--shadow); transition: transform .4s var(--ease), box-shadow .4s var(--ease); }
    .m-fcard:hover { transform: translateY(-6px); box-shadow: 0 2px 4px rgba(12,38,32,.05), 0 30px 60px -30px rgba(12,38,32,.38); }
    .m-fstage { height: 220px; margin: 10px 10px 0; padding: 22px 22px 0; border-radius: 20px; background: var(--t); display: flex; align-items: flex-end; overflow: hidden; }
    .m-fstage .ph { width: 100%; height: 100%; border-radius: 16px 16px 0 0; box-shadow: 0 12px 30px -16px rgba(12,38,32,.35); }
    .m-fcard:hover .m-fstage .ph > img { transform: scale(1.05); }
    .m-fstage .wm small { font-size: 46px; letter-spacing: 0; opacity: 1; }
    .m-fbody { padding: 20px 24px 26px; }
    .m-tag { display: inline-flex; align-items: center; gap: 6px; height: 26px; padding: 0 11px; border-radius: 999px; font-size: 12.5px; font-weight: 600; color: var(--c); background: var(--t); }
    .m-fbody h3 { margin: 12px 0 6px; font-family: var(--display); font-size: 19px; font-weight: 700; letter-spacing: -.02em; color: var(--ink); }
    .m-fbody p { font-size: 14.5px; line-height: 1.62; color: var(--muted); }

    /* At a glance: small screens on pastel stages */
    .m-glance { display: grid; grid-template-columns: repeat(var(--n, 4), minmax(0, 1fr)); gap: 22px; }
    .m-gstage { height: 250px; margin: 10px 10px 0; padding: 20px 18px 0; border-radius: 20px; overflow: hidden; display: flex; align-items: flex-start; justify-content: center; }
    .m-screen { width: 100%; height: calc(100% + 24px); padding: 16px; border-radius: 16px 16px 0 0; background: #fff; box-shadow: 0 12px 30px -16px rgba(12,38,32,.35); overflow: hidden; }
    .m-screen h4 { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 12px; font-size: 12.5px; font-weight: 600; color: var(--ink); }
    .m-screen h4 small { font-size: 11px; font-weight: 500; color: var(--muted); }
    .m-ev { list-style: none; display: grid; gap: 9px; }
    .m-ev li { display: flex; align-items: center; gap: 10px; font-size: 12.5px; color: var(--ink); min-width: 0; }
    .m-ev .d { flex: none; width: 40px; padding: 4px 0; border-radius: 10px; text-align: center; background: var(--t); color: var(--c); font-size: 10px; font-weight: 700; line-height: 1.15; text-transform: uppercase; }
    .m-ev .d b { display: block; font-size: 15px; }
    .m-ev span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
    .m-cls { list-style: none; display: grid; gap: 11px; }
    .m-cls li { font-size: 12.5px; min-width: 0; }
    .m-cls b { display: flex; justify-content: space-between; gap: 8px; color: var(--ink); font-weight: 600; }
    .m-cls b small { color: var(--muted); font-weight: 500; font-size: 11px; white-space: nowrap; }
    .m-cls i { display: block; height: 6px; margin-top: 5px; border-radius: 99px; background: var(--grey-2); overflow: hidden; }
    .m-cls i em { display: block; height: 100%; border-radius: 99px; background: var(--primary); }
    .m-yb { display: flex; align-items: flex-end; gap: 8px; height: 120px; margin-top: 6px; }
    .m-yb div { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 5px; height: 100%; }
    .m-yb i { display: block; width: 100%; max-width: 30px; border-radius: 8px 8px 4px 4px; background: linear-gradient(180deg, color-mix(in srgb, var(--primary) 50%, #fff), var(--primary)); }
    .m-yb span { font-size: 10px; color: var(--muted); font-weight: 600; }
    .m-big { font-family: var(--display); font-size: 28px; font-weight: 800; letter-spacing: -.04em; color: var(--ink); line-height: 1; }
    .m-chk { list-style: none; display: grid; gap: 10px; }
    .m-chk li { display: flex; gap: 9px; align-items: flex-start; font-size: 12.5px; color: var(--ink); font-weight: 500; line-height: 1.35; }
    .m-chk .g { flex: none; width: 20px; height: 20px; border-radius: 50%; display: grid; place-items: center; background: var(--soft); color: var(--primary); }
    .m-chk li:first-child .g { background: var(--primary); color: #fff; }
    .m-chk .g .ico { width: 11px; height: 11px; }
    .m-glance .m-pbody h3 { font-size: 18px; }

    /* Messages */
    .m-msgs { display: grid; grid-template-columns: repeat(var(--n, 3), minmax(0, 1fr)); gap: 22px; }
    .m-msgs figure { display: flex; flex-direction: column; padding: 30px; border-radius: 26px; background: #fff; box-shadow: var(--shadow); }
    .m-msgs .quote-mark { width: 44px; height: 44px; border-radius: 14px; margin-bottom: 18px; }
    .m-msgs blockquote { flex: 1; font-size: 16px; line-height: 1.7; color: var(--text); }
    .m-msgs figcaption { display: flex; align-items: center; gap: 12px; margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--line); }
    .m-msgs figcaption .ph { width: 46px; height: 46px; border-radius: 50%; flex: none; }
    .m-msgs figcaption b { display: block; color: var(--ink); font-size: 15px; font-weight: 600; }
    .m-msgs figcaption span span { display: block; font-size: 13px; color: var(--muted); }
    .m-arrow { flex: none; width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center; border: 1px solid var(--line); color: var(--ink); transition: background .2s, color .2s, border-color .2s, transform .2s; }
    .m-arrow:hover { background: var(--primary); border-color: var(--primary); color: #fff; transform: rotate(45deg); }
    .m-arrow .ico { width: 15px; height: 15px; }

    /* Photo grid */
    .m-photos { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); grid-auto-rows: 220px; gap: 16px; }
    /* Six photos fill a 3 × 3 grid: a tall first, the fourth and sixth wide; four and five fill their last row. */
    .m-photos.n6 > :nth-child(1) { grid-row: span 2; }
    .m-photos.n6 > :nth-child(4), .m-photos.n6 > :nth-child(6) { grid-column: span 2; }
    .m-photos.n4 > :nth-child(4) { grid-column: span 3; }
    .m-photos.n5 > :nth-child(5) { grid-column: span 2; }
    .m-photos figure { position: relative; overflow: hidden; border-radius: 24px; }
    .m-photos .ph { width: 100%; height: 100%; cursor: zoom-in; }
    .m-photos figure:hover .ph > img { transform: scale(1.05); }
    .m-photos figcaption { position: absolute; left: 12px; bottom: 12px; padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.92); font-size: 12.5px; font-weight: 600; color: var(--ink); pointer-events: none; }

    /* Admissions as an FAQ */
    .m-adm { display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: 56px; align-items: start; }
    .m-adm .btns { margin-top: 26px; }
    .m-faq { border-top: 1px solid var(--line); }
    .m-faq details { border-bottom: 1px solid var(--line); }
    .m-faq summary { list-style: none; display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 22px 2px; cursor: pointer; font-family: var(--display); font-size: clamp(16.5px, 1.6vw, 19px); font-weight: 600; letter-spacing: -.02em; color: var(--ink); transition: color .2s; }
    .m-faq summary::-webkit-details-marker { display: none; }
    .m-faq summary:hover { color: var(--deep); }
    .m-faq summary > span:first-child { display: flex; align-items: center; gap: 14px; }
    .m-faq .n { flex: none; width: 32px; height: 32px; border-radius: 10px; display: grid; place-items: center; background: var(--soft); color: var(--deep); font-size: 12.5px; font-weight: 700; font-family: var(--font); letter-spacing: 0; }
    .m-faq .faq-i { flex: none; width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; color: var(--muted); transition: transform .35s var(--ease), background .25s, color .25s; }
    .m-faq .faq-i svg { width: 16px; height: 16px; }
    .m-faq details[open] .faq-i { transform: rotate(45deg); background: var(--ink); color: #fff; }
    .m-faq .faq-a { padding: 0 50px 24px 48px; font-size: 15.5px; line-height: 1.75; color: var(--muted); }
    .m-faq .faq-a ul { margin: 0; padding-left: 18px; display: grid; gap: 4px; }

    /* The call-back form in the band */
    .m-callback { max-width: 620px; margin: 26px auto 0; }
    .m-callback .row { display: flex; align-items: center; gap: 6px; padding: 6px; border-radius: 999px; background: #fff; box-shadow: 0 1px 2px rgba(12,38,32,.06), 0 18px 40px -26px rgba(12,38,32,.35); }
    .m-callback input { flex: 1; min-width: 0; height: 46px; padding: 0 16px; border: 0 !important; border-radius: 999px; background: transparent; box-shadow: none !important; }
    .m-callback .row > i { width: 1px; height: 24px; background: var(--line); flex: none; }
    .m-callback .sform-msg { margin-top: 10px; text-align: center; }

    /* Location */
    .m-loc { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); gap: 20px; padding: 14px; border-radius: 30px; background: #fff; box-shadow: var(--shadow); }
    .m-loc.nomap { grid-template-columns: minmax(0, 1fr); }
    .m-loc .info-list { padding: 12px; }
    .m-loc.nomap .info-list { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
    .m-loc .info-card { box-shadow: none; border-color: transparent; background: var(--grey); }
    .m-loc .map-card { border-radius: 22px; }

    @media (max-width: 1100px) {
        .m-glance { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .m-points { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 1020px) {
        .m-hero-in, .m-about, .m-adm { grid-template-columns: minmax(0, 1fr); }
        .m-facts ul { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .m-art { max-width: 640px; }
        .m-chip.c1 { left: 10px; top: -20px; } .m-chip.c2 { right: 10px; }
        .m-fac { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .m-msgs { grid-template-columns: minmax(0, 1fr); }
        .m-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .m-loc { grid-template-columns: minmax(0, 1fr); }
        .m-stage { max-width: 600px; }
    }
    @media (max-width: 720px) {
        .m-hero { padding: 30px 0 46px; }
        .m-hero-in { gap: 40px; }
        .m-hero .btns .btn { flex: 1; }
        .m-chip { padding: 8px 12px 8px 8px; }
        .m-chip .badge-ico { width: 30px; height: 30px; }
        .m-chip.c2 { display: none; }
        .m-kpis { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .m-kpis .m-kpi:nth-child(3) { display: none; }
        .m-sec { padding: 56px 0; }
        .m-head { margin-bottom: 32px; }
        .m-facts ul { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 16px; }
        .m-stage { padding: 14px; border-radius: 26px; }
        .m-stage .m-chip.s1 { left: 8px; bottom: 22px; }
        .m-stage .m-chip.s2 { right: 8px; top: 22px; }
        .m-points, .m-glance, .m-fac { grid-template-columns: minmax(0, 1fr); }
        .m-stats { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); padding: 10px; gap: 10px; border-radius: 26px; }
        .m-stat { min-height: 130px; padding: 20px 18px; border-radius: 20px; }
        .m-stat span { font-size: 13.5px; margin-top: 12px; }
        .m-photos, .m-photos.n6 { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: 150px; gap: 10px; }
        .m-photos.n6 > :nth-child(1), .m-photos.n6 > :nth-child(4), .m-photos.n6 > :nth-child(6), .m-photos.n4 > :nth-child(4), .m-photos.n5 > :nth-child(5) { grid-row: auto; grid-column: auto; }
        .m-photos.n5 > :nth-child(5) { grid-column: 1 / -1; }
        .m-photos figure { border-radius: 18px; }
        .m-faq .faq-a { padding: 0 6px 20px 48px; }
        .m-callback .row { flex-direction: column; align-items: stretch; border-radius: 22px; padding: 8px; }
        .m-callback .row > i { display: none; }
        .m-callback .btn { width: 100%; }
        .m-loc { padding: 8px; border-radius: 24px; }
        .m-loc .info-list { padding: 6px; }
    }
</style>
@endpush

@section('content')

    {{-- ══════════ HERO ══════════ --}}
    <section class="m-hero">
        <div class="wrap m-hero-in">
            <div class="m-hero-text">
                @if ($apply)
                    <a class="m-badge" href="{{ $apply }}"><span class="bp-dot"></span>{{ $badge }}@include('school-site.modern.partials.icon', ['n' => 'right'])</a>
                @else
                    <span class="m-badge"><span class="bp-dot"></span>{{ $badge }}</span>
                @endif
                <h1>{!! ModernSite::highlight($c['hero_title'], $c['school_name']) !!}</h1>
                @if (!empty($c['hero_subtitle']))<p>{{ $c['hero_subtitle'] }}</p>@endif
                <div class="btns">
                    @if ($apply)<a class="btn btn-lg" href="{{ $apply }}">Apply for Admission</a>@endif
                    @if ($on('about'))<a class="btn btn-outline btn-lg" href="{{ url('about') }}">Explore the School @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>
                    @elseif ($tel !== '')<a class="btn btn-outline btn-lg" href="tel:{{ $tel }}">@include('school-site.modern.partials.icon', ['n' => 'phone']) Call Us</a>@endif
                </div>
                @if ($faces || $proof)
                    <div class="m-proof">
                        @if ($faces)
                            <span class="m-faces">@foreach ($faces as $f)@include('school-site.classic.partials.ph', ['src' => $f['photo'], 'alt' => $f['name']])@endforeach</span>
                        @endif
                        @foreach ($proof as [$value, $label])
                            <span class="pf"><b>{{ $value }}</b> {{ $label }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="m-art">
                <div class="m-app">
                    <div class="m-app-bar">
                        <span class="dots" aria-hidden="true"><i></i><i></i><i></i></span>
                        <span class="m-url">@include('school-site.modern.partials.icon', ['n' => 'school']){{ $site->domain ?: $c['school_name'] }}</span>
                        <span class="m-live">Live</span>
                    </div>
                    <div class="m-app-body">
                        <div class="m-slider" data-slider="5500" aria-roledescription="carousel" aria-label="{{ $c['school_name'] }}">
                            @foreach ($slides as $i => $s)
                                @php $slideUrl = SchoolWebsite::media($s['image'] ?: null); @endphp
                                <div class="slide {{ $i === 0 ? 'on' : '' }} {{ $slideUrl ? '' : 'is-mark' }}" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($slides) }}">
                                    @if ($slideUrl)
                                        <img src="{{ $slideUrl }}" alt="{{ $s['title'] }}" @if ($i) loading="lazy" @endif onerror="this.parentNode.classList.add('is-mark');this.remove();">
                                    @endif
                                    @include('school-site.classic.partials.watermark', ['label' => null])
                                    @if (trim($s['title'] . $s['subtitle']) !== '' && $s['title'] !== $c['hero_title'])
                                        <div class="m-cap">
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
                        @if ($kpis)
                            <div class="m-kpis" style="--n: {{ count($kpis) }}">
                                @foreach ($kpis as $k)
                                    <div class="m-kpi"><b>{{ $k['value'] }}</b><span>{{ $k['label'] }}</span></div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="m-chip c1">
                    <span class="badge-ico" style="{{ $sw(5) }}">@include('school-site.modern.partials.icon', ['n' => 'check'])</span>
                    <span><b>{{ $c['cta_heading'] }}</b><span>{{ $session !== '' ? $session : ($range !== '' ? $range : $c['school_name']) }}</span></span>
                </div>
                @if ($latest || $board !== '')
                    <div class="m-chip c2">
                        <span class="badge-ico" style="{{ $sw(4) }}">@include('school-site.modern.partials.icon', ['n' => 'award'])</span>
                        @if ($latest)
                            <span><b>{{ $pct($latest['pct']) }} pass</b><span>Board result {{ $latest['year'] }}</span></span>
                        @else
                            <span><b>Affiliated to {{ $board }}</b><span>{{ !empty($c['affiliation_no']) ? 'Aff. No. ' . $c['affiliation_no'] : $c['school_name'] }}</span></span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ══════════ FACTS ══════════ --}}
    @if (count($facts) >= 2)
        <div class="m-facts">
            <div class="wrap">
                <ul style="--n: {{ count($facts) }}">
                    @foreach ($facts as $i => [$icon, $label, $value])
                        <li>
                            <span class="badge-ico">
                                @if ($i === 0 && $boardUrl && $board !== '')<img src="{{ $boardUrl }}" alt="{{ $board }}">@else @include('school-site.modern.partials.icon', ['n' => $icon])@endif
                            </span>
                            <span style="display:block;min-width:0;"><span>{{ $label }}</span><b>{{ $value }}</b></span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ══════════ WELCOME ══════════ --}}
    <section class="m-sec">
        <div class="wrap m-about">
            <div class="m-about-text">
                <span class="eyebrow">Welcome</span>
                <h2 class="h2">{!! ModernSite::highlight($welcome, $c['school_name']) !!}</h2>
                @if ($lead !== '')<p class="m-lead">{{ $lead }}</p>@endif
                <p>{{ ModernSite::excerpt(trim($c['about_text'] . ' ' . ($c['about_text2'] ?? '')), 520) }}</p>
                <div class="btns">
                    @if ($on('about'))<a class="btn" href="{{ url('about') }}">Read More @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                    @if ($on('appointment'))<a class="btn btn-ghost" href="{{ url('appointment') }}">Book a Visit</a>@endif
                </div>
            </div>
            <div class="m-stage">
                @include('school-site.classic.partials.ph', ['src' => $pool[0] ?? null, 'alt' => $c['school_name']])
                @if ($stats)
                    <div class="m-chip s1"><span><b class="big" data-count>{{ $stats[0]['value'] }}</b><span>{{ $stats[0]['label'] }}</span></span></div>
                @endif
                @if ($medium !== '' || $board !== '')
                    <div class="m-chip s2">
                        <span class="badge-ico" style="{{ $sw(2) }}">@include('school-site.modern.partials.icon', ['n' => $board !== '' ? 'award' : 'book'])</span>
                        <span><b>{{ $board !== '' ? $board : Str::limit($medium, 26) }}</b><span>{{ $board !== '' ? 'Affiliated school' : 'Medium' }}</span></span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ══════════ WHAT WE STAND FOR ══════════ --}}
    @if ($points)
        <section class="m-sec soft">
            <div class="wrap">
                <div class="m-head">
                    <span class="eyebrow">Our Purpose</span>
                    <h2 class="h2">What we <span class="hl">stand for</span></h2>
                </div>
                <div class="m-points" style="--n: {{ count($points) }}">
                    @foreach ($points as $i => $p)
                        <article class="m-pcard">
                            <div class="m-pstage {{ $stages[$i % 4] }}"><span class="m-pic">@include('school-site.modern.partials.icon', ['n' => $pointIcon($p['title'])])</span></div>
                            <div class="m-pbody">
                                <span class="m-kicker">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <h3>{{ $p['title'] }}</h3>
                                <p>{{ ModernSite::excerpt($p['text'], 170) }}</p>
                                @if ($p['url'])<a class="m-more" href="{{ url($p['url']) }}">Read more @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ FIGURES ══════════ --}}
    @if (count($stats) > 1)
        <section class="m-sec" style="padding-bottom:0;">
            <div class="wrap">
                <div class="m-stats" style="--n: {{ count($stats) }}">
                    @foreach ($stats as $i => $s)
                        <div class="m-stat">
                            <b data-count>{{ $s['value'] }}</b>
                            <span>@include('school-site.modern.partials.icon', ['n' => ['school', 'award', 'star', 'users'][$i % 4]]){{ $s['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ FEATURES & FACILITIES ══════════ --}}
    <section class="m-sec">
        <div class="wrap">
            <div class="m-head">
                <span class="eyebrow">Features &amp; Facilities</span>
                <h2 class="h2">A campus made for <span class="hl">curious minds</span></h2>
                <p class="lead-text">At {{ $c['school_name'] }}, we are committed to the best facilities for the overall development of your child.</p>
            </div>
            <div class="m-fac">
                @foreach ($facilities as $i => $f)
                    <article class="m-fcard" style="{{ $sw($i + 1) }}">
                        <div class="m-fstage">@include('school-site.classic.partials.ph', ['src' => $f['image'] ?? null, 'alt' => $f['title'] ?? '', 'label' => $f['icon'] ?? null])</div>
                        <div class="m-fbody">
                            <span class="m-tag">{{ $f['icon'] ?? '✦' }} Facility</span>
                            <h3>{{ $f['title'] ?? '' }}</h3>
                            <p>{{ $f['desc'] ?? '' }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
            @if ($on('facilities'))
                <div class="btns" style="justify-content:center;margin-top:40px;"><a class="btn btn-outline" href="{{ url('facilities') }}">All facilities @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a></div>
            @endif
        </div>
    </section>

    {{-- ══════════ AT A GLANCE ══════════ --}}
    @if ($glance)
        <section class="m-sec soft">
            <div class="wrap">
                <div class="m-head">
                    <span class="eyebrow">At a Glance</span>
                    <h2 class="h2">School life, <span class="hl">in one place</span></h2>
                    <p class="lead-text">The calendar, the classes, the results and how to join — straight from the school’s own records.</p>
                </div>
                <div class="m-glance" style="--n: {{ count($glance) }}">
                    @foreach ($glance as $gi => $key)
                        <article class="m-pcard">
                            <div class="m-gstage {{ $stages[$gi % 4] }}">
                                <div class="m-screen">
                                    @if ($key === 'cal')
                                        <h4>School calendar <small>{{ $month['label'] ?? '' }}</small></h4>
                                        <ul class="m-ev">
                                            @foreach ($next as $ei => $e)
                                                <li style="{{ $sw($ei + 1) }}"><span class="d">{{ Carbon::parse($e['date'])->format('M') }}<b>{{ Carbon::parse($e['date'])->format('d') }}</b></span><span>{{ $e['title'] }}</span></li>
                                            @endforeach
                                        </ul>
                                    @elseif ($key === 'cls')
                                        <h4>Our classes <small>{{ $range }}</small></h4>
                                        <ul class="m-cls">
                                            @foreach ($clsRows as $k)
                                                <li><b>{{ $k['name'] }} <small>{{ $k['meta'] }}</small></b><i><em style="width: {{ $k['fill'] }}%"></em></i></li>
                                            @endforeach
                                        </ul>
                                    @elseif ($key === 'res')
                                        <h4>Board results <small>Pass %</small></h4>
                                        <span class="m-big">{{ $pct($latest['pct']) }}</span>
                                        <div class="m-yb" role="img" aria-label="Pass percentage by year">
                                            @foreach ($yearBars as $r)
                                                <div><i style="height: {{ max(4, $r['pct']) }}%"></i><span>{{ $r['year'] }}</span></div>
                                            @endforeach
                                        </div>
                                    @else
                                        <h4>{{ $checksTitle }}</h4>
                                        <ul class="m-chk">
                                            @foreach ($checks as [$checkTitle, $desc])
                                                <li><span class="g">@include('school-site.modern.partials.icon', ['n' => 'check'])</span>{{ $checkTitle }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                            <div class="m-pbody">
                                @if ($key === 'cal')
                                    <h3>Upcoming at school</h3>
                                    <p>{{ count($next) }} {{ Str::plural('date', count($next)) }} coming up, from the school’s academic calendar.</p>
                                    @if ($on('academic-calendar'))<a class="m-more" href="{{ url('academic-calendar') }}">Academic calendar @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                                @elseif ($key === 'cls')
                                    <h3>Classes &amp; subjects</h3>
                                    <p>{{ $range !== '' ? $range . ', ' : '' }}{{ $classes ? 'with sections and subjects for every class.' : 'with the age group and timings of each class.' }}</p>
                                    @if ($on('classes'))<a class="m-more" href="{{ url('classes') }}">All classes @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                                @elseif ($key === 'res')
                                    <h3>Board results</h3>
                                    <p>Pass percentage · {{ $latest['year'] }} — {{ number_format($latest['passed']) }} of {{ number_format($latest['appeared']) }} passed.</p>
                                    @if ($on('result'))<a class="m-more" href="{{ url('result') }}">All results @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                                @else
                                    <h3>{{ $checksTitle }}</h3>
                                    <p>{{ ModernSite::excerpt($c['admission_intro'] ?? '', 110) }}</p>
                                    @if ($apply)<a class="m-more" href="{{ $apply }}">Apply now @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ MESSAGES ══════════ --}}
    @if ($messages)
        <section class="m-sec">
            <div class="wrap">
                <div class="m-head">
                    <span class="eyebrow">Messages</span>
                    <h2 class="h2">Words from our <span class="hl">leadership</span></h2>
                </div>
                <div class="m-msgs" style="--n: {{ max(1, min(count($messages), 3)) }}">
                    @foreach ($messages as $m)
                        <figure>
                            <span class="quote-mark">@include('school-site.modern.partials.icon', ['n' => 'quote'])</span>
                            <blockquote>“{{ ModernSite::excerpt($m['message'], count($messages) === 1 ? 520 : 260) }}”</blockquote>
                            <figcaption>
                                @if ($m['photo'] !== '')@include('school-site.classic.partials.ph', ['src' => $m['photo'], 'alt' => $m['name']])@endif
                                <span style="flex:1;min-width:0;"><b>{{ $m['name'] ?: $m['role'] }}</b>@if ($m['name'] !== '' && $m['role'] !== '')<span>{{ $m['role'] }}</span>@endif</span>
                                @if ($m['url'])<a class="m-arrow" href="{{ url($m['url']) }}" aria-label="Read the full message">@include('school-site.modern.partials.icon', ['n' => 'arrow-ur'])</a>@endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ PHOTOS ══════════ --}}
    @if (count($gallery) >= 3)
        <section class="m-sec" @if ($messages) style="padding-top:0;" @endif>
            <div class="wrap">
                <div class="m-head-row">
                    <div>
                        <span class="eyebrow">Photogallery</span>
                        <h2 class="h2">Moments from <span class="hl">our campus</span></h2>
                    </div>
                    @if ($on('gallery'))<a class="btn btn-outline" href="{{ url('gallery') }}">View all photos @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                </div>
                <div class="m-photos n{{ count($gallery) }}">
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

    {{-- ══════════ ADMISSIONS: how to apply ══════════ --}}
    @if ($steps || $docs)
        <section class="m-sec soft">
            <div class="wrap m-adm">
                <div>
                    <span class="eyebrow">{{ $session !== '' ? 'Admissions · ' . $session : 'Admissions' }}</span>
                    <h2 class="h2">How to <span class="hl">apply</span></h2>
                    @if (!empty($c['admission_intro']))<p class="lead-text" style="margin-top:16px;">{{ $c['admission_intro'] }}</p>@endif
                    <div class="btns">
                        @if ($on('admission'))<a class="btn" href="{{ url('admission') }}">Apply Online @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                        @if ($on('fee-structure'))<a class="btn btn-ghost" href="{{ url('fee-structure') }}">Fee Structure</a>@endif
                    </div>
                </div>
                <div class="m-faq">
                    @foreach (array_slice($steps, 0, 6) as $si => $st)
                        <details @if ($si === 0) open @endif>
                            <summary><span><span class="n">{{ str_pad((string) ($si + 1), 2, '0', STR_PAD_LEFT) }}</span>{{ $st['title'] }}</span><span class="faq-i"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span></summary>
                            <div class="faq-a">{{ trim((string) ($st['desc'] ?? '')) !== '' ? $st['desc'] : 'Our admission office will guide you through this step.' }}</div>
                        </details>
                    @endforeach
                    @if ($docs)
                        <details @if (! $steps) open @endif>
                            <summary><span><span class="n">@include('school-site.modern.partials.icon', ['n' => 'file'])</span>Documents required</span><span class="faq-i"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span></summary>
                            <div class="faq-a"><ul>@foreach ($docs as $d)<li>{{ $d }}</li>@endforeach</ul></div>
                        </details>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ══════════ CALL TO ACTION + CALL BACK ══════════ --}}
    <section class="m-sec" style="padding-bottom:40px;">
        <div class="wrap">
            <div class="m-cta">
                <span class="m-cta-chip">@include('school-site.modern.partials.icon', ['n' => 'calendar'])<b>{{ $session !== '' ? 'Session ' . $session : 'Admissions open' }}</b></span>
                <h2 class="h2">{!! ModernSite::highlight($c['cta_heading'], '') !!}</h2>
                @if (!empty($c['cta_text']))<p class="lead-text">{{ $c['cta_text'] }}</p>@endif
                <div class="btns">
                    @if ($apply)<a class="btn btn-lg" href="{{ $apply }}">Apply Now @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>@endif
                    @if ($tel !== '')<a class="btn btn-outline btn-lg" href="tel:{{ $tel }}">@include('school-site.modern.partials.icon', ['n' => 'phone']) Call {{ trim(explode(',', $c['phone'])[0]) }}</a>@endif
                </div>
                <form class="sform m-callback" data-org="{{ (int) $site->organization_id }}" data-kind="Call back request" data-type="query" novalidate>
                    <div class="hp" aria-hidden="true"><input type="text" name="sf_check" tabindex="-1" autocomplete="off"></div>
                    <div class="row">
                        <input type="text" name="name" data-label="Name" placeholder="Parent's name" maxlength="200" required aria-label="Parent's name">
                        <i aria-hidden="true"></i>
                        <input type="tel" name="phone" data-label="Phone" placeholder="Mobile number" maxlength="15" required aria-label="Mobile number">
                        <button class="btn" type="submit">Get a call back</button>
                    </div>
                    <p class="sform-msg" role="status"></p>
                </form>
            </div>
        </div>
    </section>

    {{-- ══════════ LOCATION ══════════ --}}
    <section class="m-sec" style="padding-top:56px;">
        <div class="wrap">
            <div class="m-head">
                <span class="eyebrow">Visit Us</span>
                <h2 class="h2">Our <span class="hl">Location</span></h2>
                <p class="lead-text">Reach us at the school or just drop in an email{{ !empty($c['email']) ? ' — ' . $c['email'] : '' }}.</p>
            </div>
            <div class="m-loc {{ $mapSrc ? '' : 'nomap' }}">
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
