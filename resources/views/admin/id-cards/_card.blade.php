{{-- Shared ID-card design (front + back). Expects $c = IdCardService::cardViewData().

     Design language: quiet, typographic, CR80 portrait. One ink, one muted grey,
     one hairline and a single accent used sparingly -- a rule at the head of each
     face and the tick before the role. No gradients, no glows, no rotated frames:
     the hierarchy is carried by size and space instead of ornament, which is also
     what survives being printed on a card printer.

     Front: who this is (photo, name, role) then the facts, on hairline rows.
     Back:  what to do with it (QR), who to reach, and how long it is good for. --}}
@php $mono = strtoupper(mb_substr($c['school']['name'] ?? 'S', 0, 1)); @endphp
<style>
    .idc-wrap {
        --idc-ink:    #101828;   /* headings, values                */
        --idc-body:   #344054;   /* ordinary text                   */
        --idc-muted:  #8a94a6;   /* labels, captions                */
        --idc-line:   #e7eaf0;   /* hairlines                       */
        --idc-wash:   #f7f8fa;   /* the one tinted surface          */
        --idc-accent: #1b3a5c;   /* used three times, never as fill */
    }

    .idc-wrap * { box-sizing: border-box; margin: 0; padding: 0; }

    .idc-wrap {
        display: flex; flex-wrap: wrap; gap: 32px;
        justify-content: center; align-items: flex-start;
        font-family: 'Poppins', 'Segoe UI', -apple-system, Arial, sans-serif;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }

    .idc-card {
        width: 326px; height: 516px; background: #fff; position: relative;
        border-radius: 12px; overflow: hidden;
        border: 1px solid var(--idc-line);
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 8px 24px rgba(16, 24, 40, .06);
        display: flex; flex-direction: column;
    }

    /* The only piece of colour on the card. */
    .idc-rule { height: 4px; background: var(--idc-accent); flex: 0 0 auto; }

    /* ── Masthead ─────────────────────────────────────────────────────── */
    .idc-mast { padding: 16px 22px 14px; text-align: center; border-bottom: 1px solid var(--idc-line); flex: 0 0 auto; }
    .idc-logo { width: 38px; height: 38px; margin: 0 auto 9px; }
    .idc-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .idc-logo .ph {
        width: 100%; height: 100%; border-radius: 50%;
        background: var(--idc-wash); border: 1px solid var(--idc-line);
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; font-weight: 600; color: var(--idc-accent);
    }
    .idc-school {
        font-size: 13.5px; font-weight: 600; color: var(--idc-ink);
        letter-spacing: .2px; line-height: 1.25; text-transform: uppercase;
    }
    .idc-school-sub {
        margin-top: 4px; font-size: 7.5px; line-height: 1.45; color: var(--idc-muted);
        letter-spacing: .5px;
    }

    /* ── Identity ─────────────────────────────────────────────────────── */
    .idc-id { padding: 20px 22px 0; text-align: center; flex: 0 0 auto; }
    .idc-photo, .idc-photo-ph {
        width: 104px; height: 124px; margin: 0 auto; border-radius: 8px;
        border: 1px solid var(--idc-line); background: var(--idc-wash); display: block;
    }
    .idc-photo { object-fit: cover; }
    .idc-photo-ph {
        display: flex; align-items: center; justify-content: center;
        font-size: 38px; font-weight: 500; color: #c2c9d6;
    }
    .idc-name {
        margin-top: 14px; font-size: 17px; font-weight: 600; color: var(--idc-ink);
        line-height: 1.2; letter-spacing: -.1px;
    }
    .idc-role {
        margin-top: 6px; font-size: 8px; font-weight: 600; color: var(--idc-body);
        letter-spacing: 1.6px; text-transform: uppercase;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .idc-role::before {
        content: ''; width: 3px; height: 3px; border-radius: 50%;
        background: var(--idc-accent); flex: 0 0 auto;
    }

    /* ── Facts ────────────────────────────────────────────────────────── */
    .idc-rows { padding: 16px 22px 0; flex: 1 1 auto; min-height: 0; }
    .idc-row {
        display: flex; align-items: baseline; gap: 10px;
        padding: 5px 0; border-bottom: 1px solid var(--idc-line);
        font-size: 9.5px; line-height: 1.4;
    }
    .idc-row:last-child { border-bottom: 0; }
    .idc-row .k {
        flex: 0 0 84px; color: var(--idc-muted); font-weight: 500;
        font-size: 8px; letter-spacing: .7px; text-transform: uppercase;
    }
    .idc-row .v { flex: 1 1 auto; color: var(--idc-ink); font-weight: 500; word-break: break-word; }

    /* ── Foot ─────────────────────────────────────────────────────────── */
    .idc-foot {
        flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between;
        padding: 10px 22px; background: var(--idc-wash); border-top: 1px solid var(--idc-line);
    }
    .idc-foot .lbl { font-size: 7px; color: var(--idc-muted); letter-spacing: 1px; text-transform: uppercase; }
    .idc-foot .val { font-size: 10px; color: var(--idc-ink); font-weight: 600; margin-top: 2px; }
    .idc-foot .r { text-align: right; }

    /* ── Back ─────────────────────────────────────────────────────────── */
    .idc-back { flex: 1 1 auto; padding: 18px 22px 0; display: flex; flex-direction: column; min-height: 0; }
    .idc-backhead {
        font-size: 9px; font-weight: 600; color: var(--idc-ink); text-align: center;
        letter-spacing: 1.4px; text-transform: uppercase;
        padding-bottom: 12px; border-bottom: 1px solid var(--idc-line);
    }

    .idc-qr { text-align: center; padding: 16px 0 14px; }
    .idc-qr .tile {
        width: 118px; height: 118px; margin: 0 auto; padding: 8px; background: #fff;
        border: 1px solid var(--idc-line); border-radius: 8px;
    }
    .idc-qr .tile img { width: 100%; height: 100%; display: block; }
    .idc-qr .ph {
        width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
        background: var(--idc-wash); color: var(--idc-muted); font-size: 11px; letter-spacing: 2px;
    }
    .idc-qr .cap { margin-top: 9px; font-size: 7.5px; color: var(--idc-muted); letter-spacing: 1.3px; text-transform: uppercase; }
    .idc-qr .no { margin-top: 3px; font-size: 10.5px; font-weight: 600; color: var(--idc-ink); letter-spacing: .4px; }

    .idc-sec {
        font-size: 7px; color: var(--idc-muted); letter-spacing: 1.4px; text-transform: uppercase;
        padding-bottom: 6px; border-bottom: 1px solid var(--idc-line); margin-bottom: 8px;
    }
    .idc-contact { }
    .idc-ci { display: flex; gap: 8px; font-size: 8.5px; line-height: 1.5; color: var(--idc-body); padding: 1.5px 0; }
    .idc-ci .k { flex: 0 0 42px; color: var(--idc-muted); }
    .idc-ci .v { flex: 1 1 auto; word-break: break-word; }

    .idc-note {
        margin-top: auto; padding: 12px 0 10px;
        font-size: 7.5px; line-height: 1.55; color: var(--idc-muted); text-align: center;
    }

    .idc-sign { text-align: right; padding-bottom: 10px; }
    .idc-sign .line { width: 108px; margin-left: auto; border-bottom: 1px solid var(--idc-body); }
    .idc-sign .role { margin-top: 5px; font-size: 8px; font-weight: 600; color: var(--idc-ink); letter-spacing: .6px; }
    .idc-sign .sub { margin-top: 1px; font-size: 7px; color: var(--idc-muted); letter-spacing: .5px; }

    .idc-backfoot {
        flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between;
        padding: 10px 22px; background: var(--idc-wash); border-top: 1px solid var(--idc-line);
    }
    .idc-backfoot .lbl { font-size: 7px; color: var(--idc-muted); letter-spacing: 1px; text-transform: uppercase; }
    .idc-backfoot .val { font-size: 9.5px; color: var(--idc-ink); font-weight: 600; margin-top: 2px; }
    .idc-status {
        font-size: 7.5px; font-weight: 600; letter-spacing: 1.1px; text-transform: uppercase;
        padding: 3px 9px; border-radius: 999px; border: 1px solid var(--idc-line);
        background: #fff; color: var(--idc-body);
    }
    .idc-status.active { color: #17663a; border-color: #bfe3cd; }
    .idc-status.inactive { color: #9a2c2c; border-color: #eec4c4; }
</style>

<div class="idc-wrap">

    {{-- ══════════════════ FRONT ══════════════════ --}}
    <div class="idc-card">
        <div class="idc-rule"></div>

        <div class="idc-mast">
            <div class="idc-logo">
                @if (!empty($c['school']['logo']))
                    <img src="{{ $c['school']['logo'] }}" alt="">
                @else
                    <span class="ph">{{ $mono }}</span>
                @endif
            </div>
            <div class="idc-school">{{ $c['school']['name'] }}</div>
            @if (!empty($c['school']['address']))
                <div class="idc-school-sub">{{ $c['school']['address'] }}</div>
            @endif
        </div>

        <div class="idc-id">
            @if (!empty($c['photo']))
                <img src="{{ $c['photo'] }}" class="idc-photo" alt="">
            @else
                <div class="idc-photo-ph">{{ strtoupper(mb_substr($c['name'], 0, 1)) }}</div>
            @endif
            <div class="idc-name">{{ $c['name'] }}</div>
            <div><span class="idc-role">{{ $c['subtitle'] }}</span></div>
        </div>

        <div class="idc-rows">
            @foreach ($c['front_rows'] as $k => $v)
                <div class="idc-row">
                    <span class="k">{{ $k }}</span>
                    <span class="v">{{ $v ?: '—' }}</span>
                </div>
            @endforeach
        </div>

        <div class="idc-foot">
            <div>
                <div class="lbl">Card No.</div>
                <div class="val">{{ $c['card_number'] }}</div>
            </div>
            <div class="r">
                <div class="lbl">Valid Till</div>
                <div class="val">{{ $c['expiry_date'] }}</div>
            </div>
        </div>
    </div>

    {{-- ══════════════════ BACK ══════════════════ --}}
    <div class="idc-card">
        <div class="idc-rule"></div>

        <div class="idc-back">
            <div class="idc-backhead">{{ $c['school']['name'] }}</div>

            <div class="idc-qr">
                <div class="tile">
                    @if (!empty($c['qr_code']))
                        <img src="data:image/png;base64,{{ $c['qr_code'] }}" alt="QR">
                    @else
                        <div class="ph">QR</div>
                    @endif
                </div>
                <div class="cap">Scan to verify</div>
                <div class="no">{{ $c['card_number'] }}</div>
            </div>

            @if (!empty($c['school']['phone']) || !empty($c['school']['email']) || !empty($c['school']['website']) || !empty($c['school']['address']))
                <div class="idc-contact">
                    <div class="idc-sec">School</div>
                    @if (!empty($c['school']['phone']))
                        <div class="idc-ci"><span class="k">Phone</span><span class="v">{{ $c['school']['phone'] }}</span></div>
                    @endif
                    @if (!empty($c['school']['email']))
                        <div class="idc-ci"><span class="k">Email</span><span class="v">{{ $c['school']['email'] }}</span></div>
                    @endif
                    @if (!empty($c['school']['website']))
                        <div class="idc-ci"><span class="k">Web</span><span class="v">{{ $c['school']['website'] }}</span></div>
                    @endif
                    @if (!empty($c['school']['address']))
                        <div class="idc-ci"><span class="k">Address</span><span class="v">{{ $c['school']['address'] }}</span></div>
                    @endif
                </div>
            @endif

            <div class="idc-note">
                Property of {{ $c['school']['name'] }} and not transferable.
                If found, please return it to the school.
            </div>

            <div class="idc-sign">
                <div class="line"></div>
                <div class="role">Principal</div>
                <div class="sub">Authorised Signatory</div>
            </div>
        </div>

        <div class="idc-backfoot">
            <div>
                <div class="lbl">Issued</div>
                <div class="val">{{ $c['issue_date'] }}</div>
            </div>
            <span class="idc-status {{ $c['status'] === 'active' ? 'active' : 'inactive' }}">{{ $c['status'] }}</span>
            <div style="text-align:right;">
                <div class="lbl">Valid Till</div>
                <div class="val">{{ $c['expiry_date'] }}</div>
            </div>
        </div>
    </div>
</div>
