{{-- Student app on a phone: greeting, KPI tiles, performance chart, quick actions --}}
<div class="mk" style="--cyc:10s">
    <div class="phone">
        <div class="phone-s">
            <div class="phone-status"><span>9:41</span><span class="ps-sig"><x-site.icon name="signal" /><x-site.icon name="wifi" /><x-site.icon name="battery" /></span></div>
            <div class="phone-body">
                <div class="ps-head">
                    <div class="av a2" style="width:38px;height:38px;font-size:14px;">A</div>
                    <div style="flex:1;min-width:0;">
                        <div class="msub">Good morning,</div>
                        <div class="mtitle" style="font-size:15px;">Aarav's Dashboard</div>
                    </div>
                    <div class="ps-bell"><x-site.icon name="bell" /><i></i></div>
                </div>

                <div class="ps-kpis">
                    <div class="ps-k ap" style="--d:.2s"><b>94%</b><span>Attendance</span></div>
                    <div class="ps-k ap" style="--d:.4s"><b>A+</b><span>Avg. Grade</span></div>
                    <div class="ps-k ap" style="--d:.6s"><b>3</b><span>Tasks Due</span></div>
                    <div class="ps-k ap" style="--d:.8s"><b class="teal">Paid</b><span>Fee Status</span></div>
                </div>

                <div class="mbox">
                    <div class="mbox-h">Performance Overview <span>Term 1</span></div>
                    <div class="bars" style="height:78px;">
                        <div><b style="height:55%;--d:1s"></b></div>
                        <div><b style="height:72%;--d:1.1s"></b></div>
                        <div><b style="height:48%;--d:1.2s"></b></div>
                        <div><b style="height:88%;--d:1.3s"></b></div>
                        <div><b style="height:66%;--d:1.4s"></b></div>
                        <div><b style="height:95%;--d:1.5s"></b></div>
                    </div>
                    <div class="msub" style="margin-top:8px;">Steady improvement this term 📈</div>
                </div>

                <div class="ps-quick">
                    <div class="ps-q"><span class="t-teal"><x-site.icon name="book-open" /></span>Classes</div>
                    <div class="ps-q"><span class="t-amber"><x-site.icon name="pen" /></span>Homework</div>
                    <div class="ps-q"><span class="t-blue"><x-site.icon name="calendar" /></span>Timetable</div>
                    <div class="ps-q"><span class="t-rose"><x-site.icon name="wallet" /></span>Fees</div>
                </div>
            </div>
            <div class="ps-tab">
                <span class="on"><x-site.icon name="home" /></span>
                <span><x-site.icon name="book-open" /></span>
                <span><x-site.icon name="wallet" /></span>
                <span><x-site.icon name="user" /></span>
            </div>
            <div class="ps-toast toast-in" style="--d:2.6s">
                <span class="t-teal"><x-site.icon name="receipt" /></span>
                <div><b>SUPERLMS</b><p>Fee receipt #4821 generated</p></div>
                <small>now</small>
            </div>
        </div>
    </div>
</div>
