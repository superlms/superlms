{{-- Home hero: the live SUPERLMS dashboard in a browser window.
     KPI ids (dashSchools / dashStudents / dashRating) are filled from /api/website/stats. --}}
<div class="mk hero-mk" style="--cyc:11s">
    <div class="fnote n1">
        <span class="fi t-teal"><x-site.icon name="check-circle" /></span>
        <div>Attendance Synced<small>Just now</small></div>
    </div>
    <div class="fnote n2">
        <span class="fi t-amber"><x-site.icon name="rupee" /></span>
        <div>Fees Collected<small>+18% this month</small></div>
    </div>

    <div class="browser">
        <div class="browser-bar"><i></i><i></i><i></i>
            <div class="browser-url"><x-site.icon name="lock" /> app.superlms.in/dashboard</div>
        </div>
        <div class="appx">
            <aside class="appx-side">
                <div class="appx-brand"><img src="{{ \App\Support\Platform::logoUrl() }}" alt="">SUPERLMS</div>
                <div class="appx-nav on"><x-site.icon name="home" /> Dashboard</div>
                <div class="appx-nav"><x-site.icon name="cap" /> Students</div>
                <div class="appx-nav"><x-site.icon name="users" /> Teachers</div>
                <div class="appx-nav"><x-site.icon name="calendar-check" /> Attendance</div>
                <div class="appx-nav"><x-site.icon name="wallet" /> Fee</div>
                <div class="appx-nav"><x-site.icon name="calendar" /> Time Table</div>
                <div class="appx-nav"><x-site.icon name="file-text" /> Exam</div>
                <div class="appx-nav"><x-site.icon name="bus" /> Transportation</div>
                <div class="appx-user"><div class="av a3">P</div><div>Principal<small>2026-27</small></div></div>
            </aside>
            <div class="appx-main">
                <div class="appx-top">
                    <div>
                        <div class="appx-title">SUPERLMS Dashboard</div>
                        <div class="appx-sub">Live school overview</div>
                    </div>
                    <span class="pill-s"><x-site.icon name="calendar" /> 2026-27</span>
                </div>

                <div class="kpis">
                    <div class="kpi ap" style="--d:.1s"><div class="kpi-l"><x-site.icon name="school" /> Schools</div><div class="kpi-n" id="dashSchools">many</div></div>
                    <div class="kpi ap" style="--d:.25s"><div class="kpi-l"><x-site.icon name="cap" /> Students</div><div class="kpi-n" id="dashStudents">100K+</div></div>
                    <div class="kpi ap" style="--d:.4s"><div class="kpi-l"><x-site.icon name="grid" /> Modules</div><div class="kpi-n">50+</div></div>
                    <div class="kpi ap" style="--d:.55s"><div class="kpi-l"><x-site.icon name="star" /> Rating</div><div class="kpi-n" id="dashRating">4.9★</div></div>
                </div>

                <div class="mrow">
                    <div class="mbox">
                        <div class="mbox-h">Weekly Attendance <span class="pill-s">This week</span></div>
                        <div class="bars">
                            <div><b style="height:62%;--d:.6s"></b><small>M</small></div>
                            <div><b style="height:80%;--d:.7s"></b><small>T</small></div>
                            <div><b style="height:55%;--d:.8s"></b><small>W</small></div>
                            <div><b style="height:92%;--d:.9s"></b><small>T</small></div>
                            <div><b style="height:74%;--d:1s"></b><small>F</small></div>
                            <div><b style="height:48%;--d:1.1s"></b><small>S</small></div>
                        </div>
                    </div>
                    <div class="mbox">
                        <div class="mbox-h">Recent Activity</div>
                        <div class="arow slide-in" style="--d:1.4s"><span class="ai t-violet"><x-site.icon name="pen" /></span><span class="at">Class 8-A attendance marked</span><span class="am">2m</span></div>
                        <div class="arow slide-in" style="--d:2.2s"><span class="ai t-rose"><x-site.icon name="receipt" /></span><span class="at">Fee receipt #4821 generated</span><span class="am">9m</span></div>
                        <div class="arow slide-in" style="--d:3s"><span class="ai t-green"><x-site.icon name="chart" /></span><span class="at">Term-1 results published</span><span class="am">1h</span></div>
                    </div>
                </div>

                <div class="hd-pills">
                    <span class="pill-s"><x-site.icon name="book-open" /> Syllabus</span>
                    <span class="pill-s"><x-site.icon name="calendar" /> Timetable</span>
                    <span class="pill-s"><x-site.icon name="bus" /> Transport</span>
                    <span class="pill-s"><x-site.icon name="clipboard" /> Exams</span>
                </div>

                <div class="hd-foot"><span>Synced moments ago</span><span class="pill-s live">All systems live</span></div>
            </div>
        </div>
    </div>
    <div class="mcursor cur-hero"><svg viewBox="0 0 24 24"><path d="M4 2l16 9-7 2-3 7z" fill="#0c2620" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"/></svg></div>
</div>
