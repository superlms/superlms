{{-- Larger illustrated scenes for Why Us / Services / About / Demo.
     @include('components.site.mock.scene', ['m' => 'transport']) --}}
@switch($m)

    {{-- ───────── All modules, one partner ───────── --}}
    @case('tiles')
        <div class="mk" style="--cyc:9s;width:100%;max-width:400px;">
            <div class="tiles">
                @foreach ([['school','LMS'],['calendar-check','Attendance'],['wallet','Fees'],['clipboard','Exams'],['id-card','ID Cards'],['flask','Labs'],['presentation','Smart Class'],['bus','Transport'],['shirt','Uniforms'],['book','Books'],['laptop','Online'],['landmark','Loans']] as $i => $t)
                    <div class="tile" style="--d:{{ 0.18 * $i }}s"><x-site.icon :name="$t[0]" />{{ $t[1] }}</div>
                @endforeach
            </div>
        </div>
        @break

    {{-- ───────── ₹250 price card ───────── --}}
    @case('price')
        <div class="mk" style="--cyc:9s">
            <div class="bigprice">
                <div class="kicker" style="color:var(--teal);">School Plan</div>
                <div class="bigprice-n" style="margin-top:10px;">₹250</div>
                <div class="bigprice-s">per student / year · all 50+ modules</div>
                <div class="bp-list">
                    <span class="ap" style="--d:.5s"><x-site.icon name="check" /> All modules</span>
                    <span class="ap" style="--d:.8s"><x-site.icon name="check" /> Onboarding</span>
                    <span class="ap" style="--d:1.1s"><x-site.icon name="check" /> Training</span>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── Laptop + phone, same live data ───────── --}}
    @case('hybrid')
        <div class="mk hy" style="--cyc:8s;width:100%;max-width:460px;">
            <div class="browser hy-web">
                <div class="browser-bar"><i></i><i></i><i></i><div class="browser-url"><x-site.icon name="lock" /> app.superlms.in</div></div>
                <div style="padding:14px;display:grid;gap:8px;">
                    <div class="kpis" style="grid-template-columns:repeat(3,1fr);">
                        <div class="kpi"><div class="kpi-l">Present</div><div class="kpi-n">31/32</div></div>
                        <div class="kpi"><div class="kpi-l">Fees today</div><div class="kpi-n">₹48K</div></div>
                        <div class="kpi"><div class="kpi-l">Results</div><div class="kpi-n">Live</div></div>
                    </div>
                    <div class="mbox"><div class="bars" style="height:70px;">
                        @foreach ([50, 70, 60, 85, 75, 92] as $i => $h)<div><b style="height:{{ $h }}%;--d:{{ $i * .1 }}s"></b></div>@endforeach
                    </div></div>
                </div>
            </div>
            <div class="phone sm hy-phone">
                <div class="phone-s">
                    <div class="phone-status"><span>9:41</span></div>
                    <div class="phone-body">
                        <div class="mtitle" style="font-size:13px;">Class 8-A</div>
                        <div class="mbox" style="padding:8px 10px;"><div class="mline"><span>Present</span><b>31/32</b></div><div class="mline"><span>Fees today</span><b>₹48K</b></div></div>
                        <div class="mbox" style="padding:8px;"><div class="bars" style="height:56px;">
                            @foreach ([50, 70, 60, 85, 75, 92] as $i => $h)<div><b style="height:{{ $h }}%;--d:{{ $i * .1 }}s"></b></div>@endforeach
                        </div></div>
                    </div>
                </div>
            </div>
            <div class="hy-sync"><x-site.icon name="refresh" /></div>
        </div>
        @break

    {{-- ───────── Support chat (WhatsApp) ───────── --}}
    @case('support')
        <div class="mk" style="--cyc:10s">
            <div class="phone sm">
                <div class="phone-s" style="background:#efe7dc;">
                    <div class="phone-status" style="background:#075e54;color:#fff;"><span>11:20</span><span class="ps-sig"><x-site.icon name="signal" /><x-site.icon name="battery" /></span></div>
                    <div class="wa-head">
                        <img src="{{ \App\Support\Platform::logoUrl() }}" alt="" style="width:30px;height:30px;border-radius:50%;background:#fff;padding:3px;object-fit:contain;">
                        <div>SUPERLMS Support<small>online</small></div>
                    </div>
                    <div class="wa-body">
                        <div class="wa-m me ap" style="--d:.3s">Hi! How do we add a new section for Class 6?<time>11:18</time></div>
                        <div class="wa-m ap" style="--d:1.4s">Hello! Go to Standard → Class 6 → Add Section. We've just done it for you — check now 🙂<time>11:19</time></div>
                        <div class="wa-m me ap" style="--d:2.6s">Done, thank you! 🙏<time>11:19</time></div>
                        <div class="typing ap" style="--d:3.4s"><i></i><i></i><i></i></div>
                    </div>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── Indian fee structure + boards ───────── --}}
    @case('india')
        <div class="mk" style="--cyc:10s;width:100%;max-width:400px;">
            <div class="mpanel round" style="padding:18px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div class="mtitle"><x-site.icon name="rupee" /> Fee structure · Class 9</div>
                    <span class="pill-s">2026-27</span>
                </div>
                @foreach ([['Tuition Fee','₹2,400 / month'],['Transport Fee','₹900 / month'],['Exam Fee','₹1,200 / term'],['Sibling concession','− 10%']] as $i => $r)
                    <div class="mline ap" style="--d:{{ 0.3 * $i }}s"><span>{{ $r[0] }}</span><b>{{ $r[1] }}</b></div>
                @endforeach
                <div class="in-boards">
                    <span class="pop" style="--d:1.6s">CBSE</span><span class="pop" style="--d:1.8s">ICSE</span><span class="pop" style="--d:2s">State Board</span><span class="pop" style="--d:2.2s">हिन्दी · English</span>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── School loan EMI ───────── --}}
    @case('loan')
        <div class="mk" style="--cyc:10s;width:100%;max-width:380px;">
            <div class="mpanel round" style="padding:20px;">
                <div class="mtitle"><x-site.icon name="landmark" /> School infrastructure loan</div>
                <div class="msub" style="margin-top:3px;">Through our lending partners</div>
                <div class="ln-amt"><span class="msub">Loan amount</span><b>₹25,00,000</b></div>
                <div class="ln-slider"><i></i><span></span></div>
                <div class="mline"><span>Tenure</span><b>60 months</b></div>
                <div class="mline"><span>Monthly EMI</span><b class="teal">₹52,400</b></div>
                <div class="trk" style="margin-top:14px;">
                    <div class="trk-s ap" style="--d:1s"><span class="trk-d t-teal"><x-site.icon name="check" /></span><div class="trk-t">Application<small>Documents uploaded</small></div></div>
                    <div class="trk-s ap" style="--d:1.8s"><span class="trk-d t-teal"><x-site.icon name="check" /></span><div class="trk-t">Approved<small>Bank partner</small></div></div>
                    <div class="trk-s ap" style="--d:2.6s"><span class="trk-d t-amber"><x-site.icon name="banknote" /></span><div class="trk-t">Disbursal<small>To school account</small></div></div>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── Lab setup checklist ───────── --}}
    @case('labs')
        <div class="mk" style="--cyc:10s;width:100%;max-width:400px;">
            <div class="mpanel round" style="padding:18px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div class="mtitle"><x-site.icon name="flask" /> Lab setup · Block B</div>
                    <span class="pill-s live">On site</span>
                </div>
                <div class="lab-grid">
                    @foreach (range(1, 12) as $i)
                        <span class="lab-pc pop" style="--d:{{ 0.12 * $i }}s"><x-site.icon name="monitor" /></span>
                    @endforeach
                </div>
                @foreach ([['Computers & networking',100],['Physics & chemistry kits',100],['Furniture & layout',100],['Safety compliance',80]] as $i => $r)
                    <div class="lab-row"><span>{{ $r[0] }}</span><div class="hbar" style="flex:1;"><i style="--w:{{ $r[1] }}%;--d:{{ 1.4 + 0.3 * $i }}s"></i></div></div>
                @endforeach
            </div>
        </div>
        @break

    {{-- ───────── Smart classroom panel ───────── --}}
    @case('smartclass')
        <div class="mk" style="--cyc:9s;width:100%;max-width:470px;">
            <div class="sc-board">
                <div class="sc-screen">
                    <div class="sc-title">Photosynthesis</div>
                    <svg viewBox="0 0 300 150" class="sc-svg">
                        <circle cx="250" cy="34" r="20" fill="#fcd77f"/>
                        <path class="sc-ray" d="M226 48 L170 80"/><path class="sc-ray" d="M232 58 L186 98"/>
                        <path d="M120 140 C120 110 125 92 130 80" stroke="#22a75a" stroke-width="5" fill="none" stroke-linecap="round"/>
                        <ellipse cx="108" cy="92" rx="26" ry="12" fill="#5cc785" transform="rotate(-25 108 92)"/>
                        <ellipse cx="152" cy="84" rx="26" ry="12" fill="#3fb36d" transform="rotate(20 152 84)"/>
                        <text x="18" y="40" class="sc-t">CO₂ + H₂O</text>
                        <text x="18" y="62" class="sc-t">→ Glucose + O₂</text>
                        <path class="sc-ink" d="M14 70 C40 76 70 74 110 70"/>
                    </svg>
                </div>
                <div class="sc-stand"></div>
            </div>
            <div class="sc-chips">
                <span class="pill-s ap" style="--d:.6s"><x-site.icon name="presentation" /> Interactive panel</span>
                <span class="pill-s ap" style="--d:1s"><x-site.icon name="video" /> Digital content</span>
            </div>
        </div>
        @break

    {{-- ───────── Bus on a live map ───────── --}}
    @case('transport')
        <div class="mk" style="--cyc:12s;width:100%;max-width:440px;">
            <div class="mpanel round" style="padding:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 4px 10px;">
                    <div class="mtitle"><x-site.icon name="bus" /> Route 4 · Bus UP81 AB 2041</div>
                    <span class="pill-s live">Live</span>
                </div>
                <div class="map" style="height:230px;">
                    <svg viewBox="0 0 400 230" preserveAspectRatio="xMidYMid slice">
                        <rect width="400" height="230" fill="#eef3ef"/>
                        <path d="M0 60 H400 M0 170 H400 M90 0 V230 M260 0 V230" stroke="#e1e8e3" stroke-width="22"/>
                        <rect x="110" y="80" width="130" height="70" rx="8" fill="#e3ece6"/>
                        <rect x="280" y="80" width="100" height="70" rx="8" fill="#dbe9df"/>
                        <path class="road" d="M30 200 C 90 200, 90 60, 170 60 S 260 170, 330 150 S 380 40, 380 30"/>
                        <path class="route" id="busRoute" d="M30 200 C 90 200, 90 60, 170 60 S 260 170, 330 150 S 380 40, 380 30"/>
                        <circle cx="30" cy="200" r="7" fill="#fff" stroke="#0d9488" stroke-width="3"/>
                        <g transform="translate(380 30)"><circle r="12" fill="#0d9488" opacity=".18"/><circle r="6" fill="#0d9488"/></g>
                        <text x="296" y="20" font-size="11" fill="#2b433b" font-weight="600">School</text>
                        <g>
                            <circle r="16" fill="#0d9488" opacity=".18"/>
                            <rect x="-11" y="-11" width="22" height="22" rx="7" fill="#0d9488" stroke="#fff" stroke-width="2.5"/>
                            <rect x="-5.5" y="-5" width="11" height="7" rx="1.5" fill="#fff"/>
                            <circle cx="-3" cy="5" r="1.6" fill="#fff"/><circle cx="3" cy="5" r="1.6" fill="#fff"/>
                            <animateMotion dur="12s" repeatCount="indefinite" calcMode="linear"><mpath href="#busRoute"/></animateMotion>
                        </g>
                    </svg>
                </div>
                <div class="ps-toast toast-in tr-alert" style="--d:3s">
                    <span class="t-teal"><x-site.icon name="map-pin" /></span>
                    <div><b>Bus is 5 min away</b><p>Aarav's stop · Civil Lines</p></div>
                    <small>now</small>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── Uniform / books order tracker ───────── --}}
    @case('order')
        <div class="mk" style="--cyc:10s;width:100%;max-width:380px;">
            <div class="mpanel round" style="padding:18px;">
                <div style="display:flex;gap:12px;align-items:center;margin-bottom:14px;">
                    <span class="up-ic t-blue" style="width:44px;height:44px;font-size:22px;"><x-site.icon name="shirt" /></span>
                    <div style="flex:1;"><div class="mtitle">Uniform + Book set · Class 5</div><div class="msub">Order #SL-2041 · 2 items</div></div>
                </div>
                <div class="trk">
                    @foreach ([['Ordered','12 Mar · 10:20 am','package'],['Packed','13 Mar · 4:05 pm','check'],['Out for delivery','14 Mar · 9:30 am','truck'],['Delivered to school','14 Mar · 1:15 pm','school']] as $i => $s)
                        <div class="trk-s ap" style="--d:{{ 0.7 * $i }}s"><span class="trk-d t-teal"><x-site.icon :name="$s[2]" /></span><div class="trk-t">{{ $s[0] }}<small>{{ $s[1] }}</small></div></div>
                    @endforeach
                </div>
            </div>
        </div>
        @break

    {{-- ───────── Live online class ───────── --}}
    @case('liveclass')
        <div class="mk" style="--cyc:10s;width:100%;max-width:480px;">
            <div class="browser">
                <div class="browser-bar"><i></i><i></i><i></i><div class="browser-url"><x-site.icon name="lock" /> app.superlms.in/live</div></div>
                <div style="padding:10px;background:#0f1715;height:250px;">
                    <div class="vgrid">
                        <div class="vtile screen">
                            <span class="live-b">LIVE</span>
                            <div style="padding-top:22px;">
                                <div class="mtitle" style="font-size:12px;">Algebra · Linear equations</div>
                                <div class="msub" style="margin-bottom:8px;">Class 9 · Mr. Verma</div>
                                <div class="lc-eq"><span class="ap" style="--d:.3s">2x + 6 = 14</span><span class="ap" style="--d:1.2s">2x = 8</span><span class="ap" style="--d:2.1s">x = 4 ✓</span></div>
                            </div>
                        </div>
                        <div style="display:grid;gap:6px;">
                            <div class="vtile speaking"><div class="av a3">V</div><span class="vn">Mr. Verma <span class="eq"><i></i><i></i><i></i></span></span></div>
                            <div class="vtile"><div class="av a4">I</div><span class="vn">Ishita</span></div>
                            <div class="vtile"><div class="av a5">R</div><span class="vn">Rohan</span></div>
                        </div>
                    </div>
                </div>
                <div class="lc-bar"><span><x-site.icon name="mic" /></span><span><x-site.icon name="video" /></span><span class="lc-end">Leave</span><span class="msub">38 watching</span></div>
            </div>
        </div>
        @break

    {{-- ───────── Demo call: specialist sharing the dashboard ───────── --}}
    @case('democall')
        <div class="mk" style="--cyc:12s;width:100%;">
            <div class="browser">
                <div class="browser-bar"><i></i><i></i><i></i><div class="browser-url"><x-site.icon name="video" /> SUPERLMS live demo · 30 min</div></div>
                <div style="padding:10px;background:#0f1715;">
                    <div class="dc-grid">
                        <div class="vtile screen" style="min-height:230px;">
                            <span class="live-b">LIVE</span>
                            <div style="padding-top:26px;display:grid;gap:8px;">
                                <div class="kpis" style="grid-template-columns:repeat(3,1fr);">
                                    <div class="kpi"><div class="kpi-l">Students</div><div class="kpi-n" style="font-size:15px;">1,240</div></div>
                                    <div class="kpi"><div class="kpi-l">Present</div><div class="kpi-n" style="font-size:15px;">96%</div></div>
                                    <div class="kpi"><div class="kpi-l">Fees</div><div class="kpi-n" style="font-size:15px;">₹12.4L</div></div>
                                </div>
                                <div class="mbox" style="padding:8px;"><div class="bars" style="height:80px;">
                                    @foreach ([55, 72, 60, 88, 70, 94, 80] as $i => $h)<div><b style="height:{{ $h }}%;--d:{{ $i * .1 }}s"></b></div>@endforeach
                                </div></div>
                            </div>
                            <div class="mcursor cur-demo"><svg viewBox="0 0 24 24"><path d="M4 2l16 9-7 2-3 7z" fill="#0c2620" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"/></svg></div>
                        </div>
                        <div style="display:grid;gap:6px;">
                            <div class="vtile speaking"><div class="av a2">S</div><span class="vn">Education specialist <span class="eq"><i></i><i></i><i></i></span></span></div>
                            <div class="vtile"><div class="av a1">Y</div><span class="vn">You</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── About: mission orbit ───────── --}}
    @case('orbit')
        <div class="mk" style="width:100%;">
            <div class="orbit">
                <div class="orbit-ring"></div>
                <div class="orbit-ring r2"></div>
                <div class="orbit-core"><x-site.icon name="target" /></div>
                <div class="orbit-spin">
                    @foreach (['chart', 'check-circle', 'rupee', 'pen', 'award', 'smartphone'] as $i => $ic)
                        <div class="orbit-node" style="--a:{{ $i * 60 }}deg"><span><x-site.icon :name="$ic" /></span></div>
                    @endforeach
                </div>
            </div>
            <div class="orbit-cards">
                <div class="vchip" style="position:static;animation-delay:-1s;"><x-site.icon name="map-pin" /><div>Since 2024<small>Aligarh, India</small></div></div>
                <div class="vchip" style="position:static;animation-delay:-4s;"><x-site.icon name="cap" /><div>50,000+ Students<small>learning on SUPERLMS</small></div></div>
            </div>
        </div>
        @break

    {{-- ───────── About: India network map ───────── --}}
    @case('imap')
        <div class="mk imap">
            <svg viewBox="0 0 400 360" fill="none">
                @php $cities = [['Delhi',165,95],['Lucknow',240,110],['Jaipur',110,170],['Patna',230,190],['Bhopal',155,210],['Mumbai',200,270],['Hyderabad',270,240],['Bangalore',160,300]]; @endphp
                <ellipse cx="200" cy="180" rx="150" ry="168" fill="#0d9488" opacity=".05"/>
                <ellipse cx="200" cy="180" rx="104" ry="122" fill="#0d9488" opacity=".06"/>
                @foreach ($cities as $c)
                    <line class="link" x1="130" y1="120" x2="{{ $c[1] }}" y2="{{ $c[2] }}"/>
                @endforeach
                @foreach ($cities as $i => $c)
                    <circle class="pulse" cx="{{ $c[1] }}" cy="{{ $c[2] }}" r="5" style="animation-delay:{{ $i * .3 }}s"/>
                    <circle class="pin" cx="{{ $c[1] }}" cy="{{ $c[2] }}" r="4.5"/>
                    <text x="{{ $c[1] + 9 }}" y="{{ $c[2] + 4 }}">{{ $c[0] }}</text>
                @endforeach
                <circle class="pulse" cx="130" cy="120" r="9"/>
                <circle cx="130" cy="120" r="9" fill="#0d9488"/>
                <circle cx="130" cy="120" r="18" stroke="#0d9488" stroke-dasharray="4 4" opacity=".5"/>
                <text x="146" y="125" class="big">Aligarh ★</text>
            </svg>
            <div class="vchip c1" style="top:6%;left:-2%;"><x-site.icon name="rocket" /><div>Founded 2024<small>Aligarh, Uttar Pradesh</small></div></div>
            <div class="vchip c3" style="top:44%;right:-4%;"><x-site.icon name="school" /><div>Growing Network<small>of schools across India</small></div></div>
            <div class="vchip c2" style="bottom:4%;left:4%;right:auto;"><x-site.icon name="cap" /><div>50,000+ Students<small>actively learning</small></div></div>
        </div>
        @break

    {{-- ───────── Security shield ───────── --}}
    @case('shield')
        <div class="mk shield-wrap">
            <svg viewBox="0 0 280 300" width="260" height="280" fill="none">
                <path d="M140 14 L250 56 L250 158 Q250 238 140 284 Q30 238 30 158 L30 56 Z" fill="#0d9488" opacity=".08"/>
                <path d="M140 36 L228 70 L228 158 Q228 222 140 262 Q52 222 52 158 L52 70 Z" fill="#fff" stroke="#d8f3ec" stroke-width="2"/>
                <circle cx="140" cy="148" r="44" fill="#effaf7" stroke="#0d9488" stroke-width="2"/>
                <rect x="120" y="140" width="40" height="30" rx="7" stroke="#0d9488" stroke-width="3"/>
                <path d="M127 140 V128 a13 13 0 0 1 26 0 V140" stroke="#0d9488" stroke-width="3"/>
                <circle cx="140" cy="154" r="4" fill="#0d9488"/>
            </svg>
            <div class="scan"></div>
            <div class="vchip c1" style="top:10%;left:-6%;"><x-site.icon name="lock" />SSL Encrypted</div>
            <div class="vchip c3" style="top:40%;right:-10%;"><x-site.icon name="cloud" />Daily Backups</div>
            <div class="vchip c2" style="bottom:8%;left:-2%;right:auto;"><x-site.icon name="badge-check" />DPDP Aligned</div>
        </div>
        @break

@endswitch
