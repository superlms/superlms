{{-- Shared ID-card design (front + back). Expects $c = IdCardService::cardViewData().

     Design language: "Executive Navy" — deep navy + antique gold on clean white,
     CR80 vertical proportions, geometric diagonal header cut, generous whitespace.
     Front stays uncluttered (photo, name, key rows); QR lives on the back, centred
     with a proper quiet zone, above the school contacts and principal signature. --}}
@php $mono = strtoupper(mb_substr($c['school']['name'] ?? 'S', 0, 1)); @endphp
<style>
    .idc-wrap {
        --idc-navy: #0e2647; --idc-navy-2: #15355f; --idc-navy-3: #0a1c36;
        --idc-gold: #c9a227; --idc-gold-l: #e6cd7e; --idc-gold-d: #9e7c14;
        --idc-ink: #1b2840; --idc-muted: #7c8aa3; --idc-faint: #aeb8c9;
        --idc-line: #e8ecf3; --idc-paper: #f7f9fc;
    }
    .idc-wrap * { box-sizing: border-box; margin: 0; padding: 0; }
    .idc-wrap { display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; align-items: flex-start;
        font-family: 'Poppins','Segoe UI',Arial,sans-serif; padding: 6px; }
    .idc-card { width: 326px; height: 516px; background: #fff; border-radius: 16px; position: relative;
        overflow: hidden; box-shadow: 0 24px 48px rgba(10,28,54,.22), 0 4px 10px rgba(10,28,54,.10);
        -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    /* ───────────── FRONT ───────────── */
    .idc-header { position: absolute; top: 0; left: 0; right: 0; height: 168px; z-index: 2;
        background: linear-gradient(160deg, var(--idc-navy-2) 0%, var(--idc-navy) 55%, var(--idc-navy-3) 100%); overflow: hidden; }
    .idc-header .lines { position: absolute; inset: 0; opacity: .55;
        background-image: repeating-linear-gradient(115deg, rgba(255,255,255,.045) 0 1px, transparent 1px 14px); }
    .idc-header .orb { position: absolute; width: 240px; height: 240px; border-radius: 50%; right: -90px; top: -120px;
        border: 1px solid rgba(230,205,126,.18); }
    .idc-header .orb.o2 { width: 320px; height: 320px; right: -130px; top: -160px; border-color: rgba(230,205,126,.10); }
    .idc-cut { position: absolute; left: 0; right: 0; bottom: -1px; display: block; z-index: 3; }
    .idc-goldline { position: absolute; left: 0; right: 0; bottom: 26px; z-index: 4; }

    .idc-head-inner { position: relative; z-index: 5; padding: 18px 18px 0; text-align: center; }
    .idc-logo { width: 46px; height: 46px; margin: 0 auto 8px; border-radius: 50%; background: #fff;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 0 0 2px rgba(230,205,126,.55), 0 6px 14px rgba(0,0,0,.28); }
    .idc-logo img { width: 100%; height: 100%; object-fit: contain; border-radius: 50%; padding: 5px; }
    .idc-logo .ph { font-size: 20px; font-weight: 700; color: var(--idc-navy); font-family: Georgia,'Times New Roman',serif; }
    .idc-school { color: #fff; font-size: 16.5px; font-weight: 700; letter-spacing: .4px; line-height: 1.18;
        font-family: Georgia,'Times New Roman',serif; text-shadow: 0 1px 3px rgba(0,0,0,.3); }
    .idc-school-sub { color: rgba(255,255,255,.75); font-size: 7.5px; line-height: 1.5; letter-spacing: 1.1px;
        text-transform: uppercase; margin-top: 5px; padding: 0 14px; }

    .idc-photo-frame { position: absolute; left: 50%; top: 118px; transform: translateX(-50%); z-index: 6;
        width: 108px; height: 126px; }
    .idc-photo-frame::before { content: ''; position: absolute; inset: -5px; border-radius: 14px;
        border: 1.5px solid var(--idc-gold-l); transform: rotate(-2.2deg); }
    .idc-photo, .idc-photo-ph { width: 100%; height: 100%; border-radius: 12px; border: 3.5px solid #fff;
        box-shadow: 0 12px 24px rgba(10,28,54,.28); }
    .idc-photo { object-fit: cover; background: #e7ecf3; display: block; }
    .idc-photo-ph { background: linear-gradient(165deg, #eef2f8, #d8e0ec); color: var(--idc-navy); font-size: 40px;
        font-weight: 700; display: flex; align-items: center; justify-content: center; font-family: Georgia,serif; }

    .idc-body { position: absolute; top: 252px; left: 0; right: 0; bottom: 48px; z-index: 2; padding: 6px 26px 0; }
    .idc-name { text-align: center; font-size: 17.5px; font-weight: 700; color: var(--idc-ink);
        text-transform: uppercase; letter-spacing: .8px; line-height: 1.15; }
    .idc-desig { width: max-content; max-width: 92%; margin: 7px auto 0; display: flex; align-items: center; gap: 6px;
        font-size: 8px; color: var(--idc-navy); font-weight: 600; letter-spacing: 2.2px; text-transform: uppercase;
        padding: 3.5px 12px; border-radius: 999px; background: #eef2f8; border: 1px solid var(--idc-line); }
    .idc-desig::before { content: ''; width: 4px; height: 4px; border-radius: 50%; background: var(--idc-gold); flex: 0 0 auto; }

    .idc-rows { margin-top: 9px; padding: 0 2px; }
    .idc-row { display: flex; align-items: baseline; font-size: 10px; line-height: 1.35; padding: 4.5px 0;
        border-bottom: 1px solid var(--idc-line); color: var(--idc-ink); }
    .idc-row:last-child { border-bottom: 0; }
    .idc-row .k { width: 92px; flex: 0 0 92px; font-weight: 600; color: var(--idc-muted);
        text-transform: uppercase; font-size: 7.5px; letter-spacing: 1px; }
    .idc-row .v { flex: 1; font-weight: 600; word-break: break-word; text-align: right; }

    .idc-foot { position: absolute; left: 0; right: 0; bottom: 0; height: 48px; z-index: 4;
        background: linear-gradient(160deg, var(--idc-navy-2), var(--idc-navy-3));
        display: flex; align-items: center; gap: 10px; padding: 0 18px; }
    .idc-foot::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1.5px;
        background: linear-gradient(90deg, transparent, var(--idc-gold-l), transparent); }
    .idc-seal { flex: 0 0 30px; width: 30px; height: 30px; border-radius: 50%;
        border: 1px solid rgba(230,205,126,.7); display: flex; align-items: center; justify-content: center; }
    .idc-seal i { font-style: normal; font-family: Georgia,serif; font-weight: 700; font-size: 13px; color: var(--idc-gold-l); }
    .idc-foot .col { line-height: 1.3; }
    .idc-foot .col.r { margin-left: auto; text-align: right; }
    .idc-foot .lbl { font-size: 6.5px; text-transform: uppercase; letter-spacing: 1.2px; color: rgba(255,255,255,.65); }
    .idc-foot .val { font-size: 10px; font-weight: 700; color: #fff; font-family: 'Courier New',monospace; letter-spacing: .5px; }

    /* ───────────── BACK ───────────── */
    .idc-backbar { position: relative; height: 30px; background: linear-gradient(160deg, var(--idc-navy-2), var(--idc-navy-3));
        display: flex; align-items: center; justify-content: center; }
    .idc-backbar::after { content: ''; position: absolute; bottom: -1.5px; left: 0; right: 0; height: 1.5px;
        background: linear-gradient(90deg, transparent, var(--idc-gold-l), transparent); }
    .idc-backbar span { color: rgba(255,255,255,.85); font-size: 8px; font-weight: 600; letter-spacing: 2.4px;
        text-transform: uppercase; font-family: Georgia,serif; }

    .idc-back { position: relative; z-index: 2; height: calc(100% - 30px); display: flex; flex-direction: column;
        padding: 14px 22px 0; }
    .idc-notice { text-align: center; font-size: 7.8px; color: var(--idc-muted); line-height: 1.55; padding: 0 6px; }
    .idc-notice b { color: var(--idc-ink); font-weight: 600; }

    .idc-qr-zone { margin: 12px auto 0; text-align: center; }
    .idc-qr-tile { position: relative; width: 124px; height: 124px; margin: 0 auto; background: #fff; padding: 9px; }
    .idc-qr-tile::before, .idc-qr-tile::after, .idc-qr-tile i::before, .idc-qr-tile i::after {
        content: ''; position: absolute; width: 16px; height: 16px; border-color: var(--idc-gold); border-style: solid; }
    .idc-qr-tile::before { top: 0; left: 0; border-width: 2px 0 0 2px; }
    .idc-qr-tile::after  { top: 0; right: 0; border-width: 2px 2px 0 0; }
    .idc-qr-tile i::before { bottom: 0; left: 0; border-width: 0 0 2px 2px; }
    .idc-qr-tile i::after  { bottom: 0; right: 0; border-width: 0 2px 2px 0; }
    .idc-qr-tile img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .idc-qr-ph { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
        background: var(--idc-paper); color: var(--idc-faint); font-size: 8px; letter-spacing: 1px; text-transform: uppercase; }
    .idc-qr-lbl { margin-top: 7px; font-size: 7px; font-weight: 600; color: var(--idc-muted);
        text-transform: uppercase; letter-spacing: 2.2px; }
    .idc-qr-no { margin-top: 3px; font-family: 'Courier New',monospace; font-size: 10px; font-weight: 700;
        color: var(--idc-ink); letter-spacing: 2px; }

    .idc-contact { margin-top: 13px; }
    .idc-sec { display: flex; align-items: center; gap: 8px; font-size: 7.5px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1.6px; color: var(--idc-navy); margin-bottom: 8px; }
    .idc-sec::before, .idc-sec::after { content: ''; flex: 1; height: 1px; background: var(--idc-line); }
    .idc-contact .ci { display: flex; align-items: center; justify-content: center; gap: 7px;
        font-size: 9px; color: var(--idc-ink); font-weight: 500; padding: 2.5px 0; }
    .idc-contact .ci svg { width: 10px; height: 10px; color: var(--idc-gold-d); flex: 0 0 auto; }
    .idc-contact .ci span { word-break: break-word; text-align: center; line-height: 1.4; }

    .idc-signature { margin-top: auto; padding: 4px 0 12px; text-align: center; }
    .idc-sign-script { font-family: 'Segoe Script','Brush Script MT',cursive; font-size: 15px; color: var(--idc-navy);
        opacity: .85; line-height: 1; margin-bottom: 2px; }
    .idc-sign-line { width: 128px; height: 1px; margin: 0 auto 5px; background: var(--idc-ink); }
    .idc-sign-role { font-size: 9.5px; font-weight: 700; color: var(--idc-ink); letter-spacing: .4px; }
    .idc-sign-sub { font-size: 6.5px; color: var(--idc-muted); text-transform: uppercase; letter-spacing: 1.6px; margin-top: 1.5px; }

    .idc-backfoot { margin: 0 -22px; background: var(--idc-paper); border-top: 1px solid var(--idc-line);
        padding: 8px 22px 10px; display: flex; align-items: center; justify-content: space-between; }
    .idc-validity { line-height: 1.35; }
    .idc-validity .lbl { font-size: 6.5px; text-transform: uppercase; letter-spacing: 1.2px; color: var(--idc-muted); }
    .idc-validity .val { font-size: 9px; font-weight: 700; color: var(--idc-ink); }
    .idc-status { display: inline-block; font-size: 7px; font-weight: 700; text-transform: uppercase;
        padding: 3px 11px; border-radius: 999px; letter-spacing: .8px; }
    .idc-status.active { background: #e3f4e9; color: #1a7f3c; }
    .idc-status.inactive { background: #eef0f4; color: #6b7280; }
</style>

<div class="idc-wrap">
    {{-- ============ FRONT ============ --}}
    <div class="idc-card">
        <div class="idc-header">
            <span class="lines"></span>
            <span class="orb"></span>
            <span class="orb o2"></span>
            <div class="idc-head-inner">
                <div class="idc-logo">
                    @if (!empty($c['school']['logo']))
                        <img src="{{ $c['school']['logo'] }}" alt="logo">
                    @else
                        <span class="ph">{{ $mono }}</span>
                    @endif
                </div>
                <div class="idc-school">{{ $c['school']['name'] }}</div>
                @if (!empty($c['school']['address']))
                    <div class="idc-school-sub">{{ $c['school']['address'] }}</div>
                @endif
            </div>
            <svg class="idc-goldline" viewBox="0 0 326 8" width="326" height="8" preserveAspectRatio="none">
                <path d="M0,7 L120,7 L150,1 L326,1" stroke="#e6cd7e" stroke-width="1.2" fill="none" opacity=".8"/>
            </svg>
            <svg class="idc-cut" viewBox="0 0 326 26" width="326" height="26" preserveAspectRatio="none">
                <path d="M0,26 L326,26 L326,0 L150,22 L0,8 Z" fill="#fff"/>
            </svg>
        </div>

        <div class="idc-photo-frame">
            @if (!empty($c['photo']))
                <img src="{{ $c['photo'] }}" class="idc-photo" alt="photo">
            @else
                <div class="idc-photo-ph">{{ strtoupper(mb_substr($c['name'], 0, 1)) }}</div>
            @endif
        </div>

        <div class="idc-body">
            <div class="idc-name">{{ $c['name'] }}</div>
            <div class="idc-desig">{{ $c['subtitle'] }}</div>
            <div class="idc-rows">
                @foreach ($c['front_rows'] as $k => $v)
                    <div class="idc-row"><span class="k">{{ $k }}</span><span class="v">{{ $v ?: '—' }}</span></div>
                @endforeach
            </div>
        </div>

        <div class="idc-foot">
            <div class="idc-seal"><i>{{ $mono }}</i></div>
            <div class="col"><div class="lbl">Card No.</div><div class="val">{{ $c['card_number'] }}</div></div>
            <div class="col r"><div class="lbl">Valid Till</div><div class="val">{{ $c['expiry_date'] }}</div></div>
        </div>
    </div>

    {{-- ============ BACK ============ --}}
    <div class="idc-card">
        <div class="idc-backbar"><span>{{ $c['school']['name'] }}</span></div>
        <div class="idc-back">

            <div class="idc-notice">
                This card is the property of <b>{{ $c['school']['name'] }}</b> and is non-transferable.
                If found, please return it to the school at the address below.
            </div>

            {{-- Centred QR with quiet zone + gold corner ticks --}}
            <div class="idc-qr-zone">
                <div class="idc-qr-tile"><i></i>
                    @if (!empty($c['qr_code']))
                        <img src="data:image/png;base64,{{ $c['qr_code'] }}" alt="QR">
                    @else
                        <div class="idc-qr-ph">QR</div>
                    @endif
                </div>
                <div class="idc-qr-lbl">Scan to verify</div>
                <div class="idc-qr-no">{{ $c['card_number'] }}</div>
            </div>

            {{-- School contact details --}}
            <div class="idc-contact">
                <div class="idc-sec">Contact</div>
                @if (!empty($c['school']['phone']))
                    <div class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg><span>{{ $c['school']['phone'] }}</span></div>
                @endif
                @if (!empty($c['school']['email']))
                    <div class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg><span>{{ $c['school']['email'] }}</span></div>
                @endif
                @if (!empty($c['school']['website']))
                    <div class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0a8.949 8.949 0 004.951-1.488A3.987 3.987 0 0013 16h-2a3.987 3.987 0 00-3.951 3.512A8.949 8.949 0 0012 21zM3.6 9h16.8M3.6 15h16.8"/></svg><span>{{ $c['school']['website'] }}</span></div>
                @endif
                @if (!empty($c['school']['address']))
                    <div class="ci"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg><span>{{ $c['school']['address'] }}</span></div>
                @endif
            </div>

            {{-- Principal signature --}}
            <div class="idc-signature">
                <div class="idc-sign-script">Principal</div>
                <div class="idc-sign-line"></div>
                <div class="idc-sign-role">Principal</div>
                <div class="idc-sign-sub">Authorised Signatory</div>
            </div>

            <div class="idc-backfoot">
                <div class="idc-validity">
                    <div class="lbl">Issued</div>
                    <div class="val">{{ $c['issue_date'] }}</div>
                </div>
                <span class="idc-status {{ $c['status'] === 'active' ? 'active' : 'inactive' }}">{{ $c['status'] }}</span>
                <div class="idc-validity" style="text-align:right;">
                    <div class="lbl">Valid Till</div>
                    <div class="val">{{ $c['expiry_date'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
