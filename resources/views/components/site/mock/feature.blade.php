{{-- Product screens for the feature tabs / split sections.
     @include('components.site.mock.feature', ['m' => 'attendance']) --}}
@switch($m)

    {{-- ───────── Analytics dashboard card ───────── --}}
    @case('analytics')
        <div class="mk" style="--cyc:10s;width:100%;max-width:470px;">
            <div class="browser">
                <div class="browser-bar"><i></i><i></i><i></i><div class="browser-url"><x-site.icon name="lock" /> app.superlms.in/analytics</div></div>
                <div style="padding:16px;display:grid;gap:10px;">
                    <div class="kpis" style="grid-template-columns:repeat(3,1fr);">
                        <div class="kpi ap" style="--d:.1s"><div class="kpi-l"><x-site.icon name="calendar-check" /> Attendance</div><div class="kpi-n">89%</div><div class="kpi-t">▲ 3.2%</div></div>
                        <div class="kpi ap" style="--d:.3s"><div class="kpi-l"><x-site.icon name="rupee" /> Fees</div><div class="kpi-n">₹12.4L</div><div class="kpi-t">▲ 18%</div></div>
                        <div class="kpi ap" style="--d:.5s"><div class="kpi-l"><x-site.icon name="trending-up" /> Avg score</div><div class="kpi-n">78%</div><div class="kpi-t">▲ 4%</div></div>
                    </div>
                    <div class="mbox">
                        <div class="mbox-h">Monthly Progress <span>2026-27</span></div>
                        <div class="bars" style="height:120px;">
                            @foreach ([40, 55, 48, 66, 60, 74, 70, 84, 79, 90] as $i => $h)
                                <div><b style="height:{{ $h }}%;--d:{{ 0.4 + $i * 0.08 }}s" class="{{ $i % 2 ? '' : 'alt' }}"></b></div>
                            @endforeach
                        </div>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span class="msub">Admin · Teacher · Student reports</span>
                        <span class="mbtn teal pop" style="--d:2s"><x-site.icon name="download" />&nbsp;Export</span>
                    </div>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── Attendance on a phone + parent alert ───────── --}}
    @case('attendance')
        <div class="mk" style="--cyc:10s">
            <div class="phone sm">
                <div class="phone-s">
                    <div class="phone-status"><span>8:05</span><span class="ps-sig"><x-site.icon name="signal" /><x-site.icon name="battery" /></span></div>
                    <div class="phone-body">
                        <div class="mtitle" style="font-size:15px;">Class 6-B · Attendance</div>
                        <div class="msub">Today · 32 students</div>
                        <div class="mbox" style="padding:6px 10px;">
                            @foreach ([['Aarav Sharma','a2','p'],['Ishita Rao','a4','p'],['Kabir Khan','a3','a'],['Meera Iyer','a1','p'],['Rohan Das','a6','p'],['Sana Ali','a5','p']] as $i => $s)
                                <div class="att">
                                    <span class="av {{ $s[1] }}" style="width:24px;height:24px;font-size:10px;">{{ substr($s[0],0,1) }}</span>
                                    <span class="nm">{{ $s[0] }}</span>
                                    <span class="pa"><span class="{{ $s[2] === 'p' ? 'p' : '' }}" style="--d:{{ 0.35 * $i }}s">P</span><span class="{{ $s[2] === 'a' ? 'a' : '' }}" style="--d:{{ 0.35 * $i }}s">A</span></span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mbtn teal" style="width:100%;height:36px;">Submit · 31 present</div>
                    </div>
                    <div class="ps-toast toast-in" style="--d:2.8s;top:54px;">
                        <span style="background:#25d366;color:#fff;"><x-site.icon name="whatsapp" /></span>
                        <div><b>WhatsApp</b><p>Kabir is absent today — parent alerted</p></div>
                        <small>now</small>
                    </div>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── Timetable grid ───────── --}}
    @case('timetable')
        <div class="mk" style="--cyc:11s;width:100%;max-width:470px;">
            <div class="mpanel round" style="padding:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div><div class="mtitle"><x-site.icon name="calendar" /> Time Table · Class 7-A</div><div class="msub">Conflict-free · auto-generated</div></div>
                    <span class="pill-s live">0 clashes</span>
                </div>
                <div class="tt">
                    <span></span><span class="th">Mon</span><span class="th">Tue</span><span class="th">Wed</span><span class="th">Thu</span><span class="th">Fri</span>
                    @php
                        $rows = ['8:00' => [['Maths','c1'],['English','c2'],['Science','c3'],['Maths','c1'],['Hindi','c4']],
                                 '8:45' => [['Science','c3'],['Maths','c1'],['Hindi','c4'],['English','c2'],['Maths','c1']],
                                 '9:30' => [['Hindi','c4'],['SST','c5'],['Maths','c1'],['Science','c3'],['English','c2']],
                                 '10:30' => [['English','c2'],['Science','c3'],['Sports','c6'],['SST','c5'],['Science','c3']]];
                        $k = 0;
                    @endphp
                    @foreach ($rows as $t => $cells)
                        <span class="tm">{{ $t }}</span>
                        @foreach ($cells as $c)
                            <span class="cl {{ $c[1] }}" style="--d:{{ 0.08 * $k++ }}s">{{ $c[0] }}</span>
                        @endforeach
                    @endforeach
                </div>
                <div class="tt-sub ap" style="--d:2.6s"><x-site.icon name="refresh" /> <span><b>Substitute assigned</b> · Mrs. Rao takes Science, Thu 9:30</span></div>
            </div>
        </div>
        @break

    {{-- ───────── Fee payment on a phone ───────── --}}
    @case('fee')
        <div class="mk" style="--cyc:10s">
            <div class="phone sm">
                <div class="phone-s">
                    <div class="phone-status"><span>10:02</span><span class="ps-sig"><x-site.icon name="signal" /><x-site.icon name="battery" /></span></div>
                    <div class="phone-body">
                        <div class="fee-card">
                            <div class="msub" style="color:rgba(255,255,255,.75);">Fee summary · 2026-27</div>
                            <div class="fee-ring">
                                <svg class="ring" viewBox="0 0 100 100" style="width:78px;height:78px;"><circle class="rbg" cx="50" cy="50" r="40" style="stroke:rgba(255,255,255,.18)"/><circle class="rfg" cx="50" cy="50" r="40" style="--len:251;--to:50;stroke:#fcd77f;--d:.2s"/></svg>
                                <div><b>₹17,550</b><small>paid of ₹35,100</small></div>
                            </div>
                        </div>
                        <div class="mbox" style="padding:10px 12px;">
                            <div class="mline"><span>2nd Installment</span><b>₹17,550</b></div>
                            <div class="mline"><span>Due date</span><b>10 Dec</b></div>
                            <div class="fee-pay"><span class="fp-a">Pay ₹17,550 by UPI</span><span class="fp-b"><span class="spinner" style="border-color:rgba(255,255,255,.35);border-top-color:#fff;"></span> Processing…</span><span class="fp-c"><x-site.icon name="check" /> Paid</span></div>
                        </div>
                        <div class="mbox ap" style="--d:4.2s;padding:10px 12px;display:flex;gap:10px;align-items:center;">
                            <span class="up-ic t-teal"><x-site.icon name="receipt" /></span>
                            <div style="flex:1"><b style="font-size:12px;">Receipt #4821</b><div class="msub">Sent on WhatsApp</div></div>
                            <x-site.icon name="download" class="teal" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── Quiz with instant result + leaderboard ───────── --}}
    @case('quiz')
        <div class="mk" style="--cyc:10s;width:100%;max-width:420px;">
            <div class="mpanel round" style="padding:18px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span class="pill-s"><x-site.icon name="timer" /> 00:42</span>
                    <span class="msub">Question 4 of 10</span>
                </div>
                <div class="hbar" style="margin:12px 0 14px;"><i style="--w:40%;--d:.1s"></i></div>
                <div class="mtitle" style="font-size:14px;line-height:1.4;">Which gas do plants take in during photosynthesis?</div>
                <div class="qz-opts">
                    <div class="qz-o">Oxygen</div>
                    <div class="qz-o qz-right">Carbon dioxide <x-site.icon name="check" /></div>
                    <div class="qz-o">Nitrogen</div>
                    <div class="qz-o">Hydrogen</div>
                </div>
                <div class="qz-board ap" style="--d:3.2s">
                    <div class="mbox-h" style="margin-bottom:6px;">Leaderboard <span>Class 7</span></div>
                    <div class="mline"><span><b>1</b>&nbsp; Ishita R.</span><b>10/10</b></div>
                    <div class="mline"><span><b>2</b>&nbsp; Aarav S.</span><b>9/10</b></div>
                    <div class="mline"><span><b>3</b>&nbsp; Kabir K.</span><b>8/10</b></div>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── Bulk ID cards ───────── --}}
    @case('idcards')
        <div class="mk" style="--cyc:10s;width:100%;max-width:460px;">
            <div class="id-stage">
                <div class="idc id-back"><div class="idc-top">SUNRISE ACADEMY</div><div class="idc-ph av a4">I</div><div class="idc-n">Ishita Rao</div><div class="idc-c">Class 6-B · Roll 08</div><div class="idc-bc"></div></div>
                <div class="idc id-mid"><div class="idc-top">SUNRISE ACADEMY</div><div class="idc-ph av a3">K</div><div class="idc-n">Kabir Khan</div><div class="idc-c">Class 9-A · Roll 21</div><div class="idc-bc"></div></div>
                <div class="idc id-front"><div class="idc-top">SUNRISE ACADEMY</div><div class="idc-ph av a2">A</div><div class="idc-n">Aarav Sharma</div><div class="idc-c">Class 8-A · Roll 12</div><div class="idc-bc"></div></div>
            </div>
            <div class="mpanel round id-bar">
                <span class="up-ic t-teal"><x-site.icon name="printer" /></span>
                <div style="flex:1;min-width:0;"><b style="font-size:12.5px;">Bulk generate · 480 ID cards</b>
                    <div class="hbar" style="margin-top:6px;"><i style="--w:100%;--d:.3s"></i></div></div>
                <span class="st ok pop" style="--d:2.2s">PDF ready</span>
            </div>
        </div>
        @break

    {{-- ───────── Digital content library ───────── --}}
    @case('content')
        <div class="mk" style="--cyc:10s;width:100%;max-width:440px;">
            <div class="mpanel round" style="padding:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <div><div class="mtitle"><x-site.icon name="library" /> Study Material</div><div class="msub">Science · Class 7</div></div>
                    <span class="mbtn teal"><x-site.icon name="upload" />&nbsp;Upload</span>
                </div>
                @foreach ([['PDF','t-rose','Chapter 3 – Notes.pdf','2.4 MB'],['MP4','t-violet','Photosynthesis explained','12:40'],['PPT','t-amber','Plant cells – slides','18 slides'],['PDF','t-rose','Practice worksheet.pdf','640 KB']] as $i => $f)
                    <div class="ct-row slide-in" style="--d:{{ 0.4 * $i }}s">
                        <span class="ct-t {{ $f[1] }}">{{ $f[0] }}</span>
                        <div style="flex:1;min-width:0;"><b>{{ $f[2] }}</b><small>{{ $f[3] }}</small></div>
                        <x-site.icon name="download" class="teal" />
                    </div>
                @endforeach
                <div class="ct-note ap" style="--d:2.4s"><x-site.icon name="bell" /> 42 students notified · available offline</div>
            </div>
        </div>
        @break

    {{-- ───────── Alerts on a lock screen ───────── --}}
    @case('alerts')
        <div class="mk" style="--cyc:10s">
            <div class="phone sm">
                <div class="phone-s lock">
                    <div class="phone-status"><span>10:02</span><span class="ps-sig"><x-site.icon name="signal" /><x-site.icon name="battery" /></span></div>
                    <div class="lock-date">Friday, 17 October</div>
                    <div class="lock-time">10:02</div>
                    <div style="display:grid;gap:8px;margin-top:24px;">
                        <div class="lnote toast-in" style="--d:.4s"><span class="li" style="background:#25d366;"><x-site.icon name="whatsapp" /></span><div><b>Sunrise Academy <span>now</span></b><p>Homework for Class 6-B is posted. Due tomorrow.</p></div></div>
                        <div class="lnote toast-in" style="--d:1.4s"><span class="li" style="background:var(--teal);"><x-site.icon name="megaphone" /></span><div><b>Announcement <span>now</span></b><p>PTM on Saturday at 10:00 am in the school hall.</p></div></div>
                        <div class="lnote toast-in" style="--d:2.4s"><span class="li" style="background:#f59e0b;"><x-site.icon name="bell" /></span><div><b>SMS <span>now</span></b><p>Aarav reached school at 7:52 am.</p></div></div>
                    </div>
                </div>
            </div>
        </div>
        @break

    {{-- ───────── Report card document ───────── --}}
    @case('reportcard')
        <div class="mk" style="--cyc:10s;width:100%;max-width:400px;">
            <div class="mpanel round rc">
                <div class="rc-head">
                    <img src="{{ \App\Support\Platform::logoUrl() }}" alt="">
                    <div><b>Report Card · Term 1</b><small>Session 2026-27</small></div>
                    <span class="st ok">Pass</span>
                </div>
                <div class="rc-st">
                    <div class="av a2" style="width:38px;height:38px;">A</div>
                    <div><b>Aarav Sharma</b><small>Class 8-A · Roll 12 · Attendance 94%</small></div>
                </div>
                <table class="mtable">
                    <tr><th>Subject</th><th>Marks</th><th>Grade</th></tr>
                    @foreach ([['Maths','92/100','A1'],['Science','86/100','A2'],['English','81/100','A2'],['Hindi','77/100','B1']] as $i => $r)
                        <tr class="ap" style="--d:{{ 0.3 + 0.25 * $i }}s"><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td><b class="teal">{{ $r[2] }}</b></td></tr>
                    @endforeach
                </table>
                <div class="rc-foot">
                    <span class="msub">Teacher's remark: Very Good</span>
                    <span class="rc-stamp pop" style="--d:2s">VERIFIED</span>
                </div>
            </div>
        </div>
        @break

@endswitch
