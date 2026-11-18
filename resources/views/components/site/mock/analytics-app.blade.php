{{-- Big browser window: the Analytics screen, live (typing search, rings, charts, table) --}}
<div class="mk" style="--cyc:12s">
    <div class="browser">
        <div class="browser-bar"><i></i><i></i><i></i>
            <div class="browser-url"><x-site.icon name="lock" /> app.superlms.in/analytics</div>
        </div>
        <div class="appx" style="min-height:520px;">
            <aside class="appx-side">
                <div class="appx-brand"><img src="{{ \App\Support\Platform::logoUrl() }}" alt="">SUPERLMS</div>
                <div class="appx-nav"><x-site.icon name="home" /> Home</div>
                <div class="appx-nav on"><x-site.icon name="chart" /> Analytics</div>
                <div class="appx-nav"><x-site.icon name="cap" /> Students</div>
                <div class="appx-nav"><x-site.icon name="calendar-check" /> Attendance</div>
                <div class="appx-nav"><x-site.icon name="wallet" /> Fees</div>
                <div class="appx-nav"><x-site.icon name="trending-up" /> Performance</div>
                <div class="appx-nav"><x-site.icon name="clipboard" /> Report Card</div>
                <div class="appx-label">Reports</div>
                <div class="appx-nav"><x-site.icon name="file-text" /> Monthly attendance</div>
                <div class="appx-nav"><x-site.icon name="receipt" /> Fee collection</div>
                <div class="appx-nav"><x-site.icon name="award" /> Term-1 results</div>
                <div class="appx-user"><div class="av a1">R</div><div>Principal<small>2026-27</small></div></div>
            </aside>
            <div class="appx-main">
                <div class="appx-top">
                    <div class="aa-search"><x-site.icon name="search" /><span data-type="Attendance heatmap for Class 8-A|Fee collection this month|Subject-wise performance, Class 10"></span></div>
                    <span class="pill-s live">Live</span>
                </div>

                <div class="aa-kpis">
                    <div class="mbox aa-k ap" style="--d:.2s">
                        <div class="ring-wrap">
                            <svg class="ring" viewBox="0 0 100 100"><circle class="rbg" cx="50" cy="50" r="40"/><circle class="rfg" cx="50" cy="50" r="40" style="--len:251;--to:28;--d:.3s"/></svg>
                            <div class="rv-n">89%<small>Attendance</small></div>
                        </div>
                        <div>
                            <div class="msub">Attendance</div>
                            <div class="kpi-n">89%</div>
                            <div class="kpi-t">▲ 3.2% this week</div>
                        </div>
                    </div>
                    <div class="mbox ap" style="--d:.4s">
                        <div class="msub">Fee collection</div>
                        <div class="kpi-n">₹12.4L</div>
                        <svg class="spark" viewBox="0 0 200 54" preserveAspectRatio="none" style="width:100%;height:46px;margin-top:6px;--d:.5s">
                            <defs><linearGradient id="sparkfill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#0d9488" stop-opacity=".22"/><stop offset="1" stop-color="#0d9488" stop-opacity="0"/></linearGradient></defs>
                            <path class="ar" d="M0 44 L25 38 L50 40 L75 28 L100 31 L125 20 L150 22 L175 12 L200 8 L200 54 L0 54Z"/>
                            <path class="ln" d="M0 44 L25 38 L50 40 L75 28 L100 31 L125 20 L150 22 L175 12 L200 8"/>
                        </svg>
                    </div>
                    <div class="mbox ap" style="--d:.6s">
                        <div class="msub">Monthly Progress</div>
                        <div class="kpi-n">78%</div>
                        <div class="hbar" style="margin-top:12px;"><i style="--w:78%;--d:.8s"></i></div>
                        <div class="msub" style="margin-top:8px;">Goal: 95% average this semester</div>
                    </div>
                </div>

                <div class="mrow" style="grid-template-columns:1.2fr 1fr;">
                    <div class="mbox">
                        <div class="mbox-h">Monthly Progress <span>Apr – Mar</span></div>
                        <div class="bars" style="height:128px;gap:6px;">
                            @foreach ([46, 58, 52, 66, 61, 72, 69, 78, 74, 83, 80, 89] as $i => $h)
                                <div><b style="height:{{ $h }}%;--d:{{ 0.5 + $i * 0.08 }}s"></b><small>{{ ['A','M','J','J','A','S','O','N','D','J','F','M'][$i] }}</small></div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mbox" style="padding:0;overflow:hidden;">
                        <div class="mbox-h" style="padding:12px 12px 0;">Fee dues <span>Class-wise</span></div>
                        <table class="mtable">
                            <tr><th>Student</th><th>Class</th><th>Status</th></tr>
                            <tr class="ap" style="--d:1.4s"><td>Riya Verma</td><td>7-B</td><td><span class="st ok">Paid</span></td></tr>
                            <tr class="ap" style="--d:1.6s"><td>Kabir Khan</td><td>9-A</td><td><span class="st wait">Due</span></td></tr>
                            <tr class="ap" style="--d:1.8s"><td>Ananya Gupta</td><td>6-C</td><td><span class="st ok">Paid</span></td></tr>
                            <tr class="ap" style="--d:2s"><td>Vivaan Singh</td><td>8-A</td><td><span class="st ok">Paid</span></td></tr>
                            <tr class="ap" style="--d:2.2s"><td>Sara Ali</td><td>10-C</td><td><span class="st wait">Due</span></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="aa-float toast-in" style="--d:3s">
        <div class="fnote" style="position:static;">
            <span class="fi t-teal"><x-site.icon name="download" /></span>
            <div>Report ready<small>Exported as PDF</small></div>
        </div>
    </div>
</div>
