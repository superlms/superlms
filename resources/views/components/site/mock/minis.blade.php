{{-- Small live panels that sit inside the pastel feature cards.
     @include('components.site.mock.minis', ['m' => 'heatmap']) --}}
@switch($m)

    {{-- ───────── Analytics: attendance heatmap ───────── --}}
    @case('heatmap')
        <div class="mpanel mk" style="--cyc:9s">
            <div class="mtitle"><x-site.icon name="calendar-check" /> Attendance · Class 8-A</div>
            <div class="msub" style="margin:4px 0 12px;">Last 5 weeks</div>
            <div class="heat">
                @php $lv = [2,3,3,4,2,1,0, 3,4,4,3,2,1,0, 1,2,3,4,4,2,0, 3,3,2,4,3,1,0, 4,4,3,3,2,2,0]; @endphp
                @foreach ($lv as $i => $v)
                    <i class="{{ $v ? 'h'.$v : '' }}" style="--d:{{ 0.04 * $i }}s"></i>
                @endforeach
            </div>
            <div class="mline" style="margin-top:10px;border:0;"><span>Avg. present</span><b class="teal">96%</b></div>
        </div>
        @break

    {{-- ───────── Analytics: subject-wise performance & rank ───────── --}}
    @case('rank')
        <div class="mpanel mk" style="--cyc:9s">
            <div class="mtitle"><x-site.icon name="trending-up" /> Subject-wise performance</div>
            <div class="msub" style="margin:4px 0 12px;">Class 10 · Term 1</div>
            @foreach ([['Maths', 92, '#1'], ['Science', 86, '#2'], ['English', 81, '#3'], ['Hindi', 77, '#4'], ['Social Sc.', 73, '#5']] as $i => $r)
                <div class="rk-row">
                    <span class="rk-n">{{ $r[2] }}</span>
                    <span class="rk-s">{{ $r[0] }}</span>
                    <div class="hbar" style="flex:1;"><i style="--w:{{ $r[1] }}%;--d:{{ 0.15 * $i }}s"></i></div>
                    <b class="rk-v">{{ $r[1] }}%</b>
                </div>
            @endforeach
        </div>
        @break

    {{-- ───────── Analytics: fee collection forecast ───────── --}}
    @case('forecast')
        <div class="mpanel mk" style="--cyc:9s">
            <div class="mtitle"><x-site.icon name="rupee" /> Fee collection</div>
            <div class="msub" style="margin:4px 0 6px;">Monthly · with forecast</div>
            <div class="kpi-n" style="font-size:22px;">₹12,40,500</div>
            <svg class="spark" viewBox="0 0 240 90" style="width:100%;height:96px;margin-top:6px;">
                <defs><linearGradient id="sparkfill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#0d9488" stop-opacity=".22"/><stop offset="1" stop-color="#0d9488" stop-opacity="0"/></linearGradient></defs>
                <path class="ar" d="M0 74 L30 66 L60 70 L90 52 L120 56 L150 38 L170 34 L170 90 L0 90Z"/>
                <path class="ln" d="M0 74 L30 66 L60 70 L90 52 L120 56 L150 38 L170 34"/>
                <path class="dash" d="M170 34 L200 26 L240 14"/>
                <circle cx="170" cy="34" r="4.5" fill="#fff" stroke="#0d9488" stroke-width="2.4"/>
            </svg>
            <div class="mline" style="border:0;padding-bottom:0;"><span>Forecast · next month</span><b class="teal">▲ 8%</b></div>
        </div>
        @break

    {{-- ───────── Analytics: report cards in one click ───────── --}}
    @case('reportgen')
        <div class="mpanel mk" style="--cyc:9s">
            <div class="mtitle"><x-site.icon name="clipboard" /> Report Card · Term 1</div>
            <div class="msub" style="margin:4px 0 12px;">Class 8-A · 42 students</div>
            <div class="rg-btn"><span class="rg-a">Generate</span><span class="rg-b"><span class="spinner"></span> Generating…</span><span class="rg-c"><x-site.icon name="check" /> 42 ready</span></div>
            <div class="rg-files">
                <div class="rg-f ap" style="--d:3.4s"><span class="t-rose">PDF</span>Aarav_Sharma.pdf</div>
                <div class="rg-f ap" style="--d:3.6s"><span class="t-rose">PDF</span>Riya_Verma.pdf</div>
                <div class="rg-f ap" style="--d:3.8s"><span class="t-rose">PDF</span>Kabir_Khan.pdf</div>
            </div>
        </div>
        @break

    {{-- ───────── How it works 1: data upload ───────── --}}
    @case('upload')
        <div class="mpanel mk" style="--cyc:9s">
            <div class="mtitle"><x-site.icon name="upload" /> Importing school data</div>
            <div class="msub" style="margin:4px 0 10px;">Handled by the SUPERLMS team</div>
            @foreach ([['Students.xlsx', '1,240 rows'], ['Teachers.xlsx', '64 rows'], ['Classes & subjects', '32 sections'], ['Fee structure.xlsx', '18 heads']] as $i => $f)
                <div class="up-row">
                    <span class="up-ic t-green"><x-site.icon name="file-text" /></span>
                    <div style="flex:1;min-width:0;"><b>{{ $f[0] }}</b><small>{{ $f[1] }}</small>
                        <div class="hbar" style="height:5px;margin-top:5px;"><i style="--w:100%;--d:{{ 0.7 * $i }}s"></i></div>
                    </div>
                    <span class="up-ok check-in" style="--d:{{ 0.7 * $i + 1 }}s"><x-site.icon name="check" /></span>
                </div>
            @endforeach
        </div>
        @break

    {{-- ───────── How it works 2: accounts for every role ───────── --}}
    @case('accounts')
        <div class="mpanel mk" style="--cyc:9s;text-align:center;">
            <img src="{{ \App\Support\Platform::logoUrl() }}" alt="" style="width:34px;height:34px;margin:4px auto 8px;object-fit:contain;">
            <div class="mtitle" style="justify-content:center;">Welcome to SUPERLMS</div>
            <div class="msub" style="margin:4px 0 14px;">Your secure account is ready</div>
            <div class="acc-roles">
                <span class="pop" style="--d:.4s"><x-site.icon name="landmark" /> Admin</span>
                <span class="pop" style="--d:.8s"><x-site.icon name="teacher" /> Teacher</span>
                <span class="pop" style="--d:1.2s"><x-site.icon name="cap" /> Student</span>
                <span class="pop" style="--d:1.6s"><x-site.icon name="family" /> Parent</span>
            </div>
            <div class="acc-field"><x-site.icon name="user" /><span data-type="principal@school.in|teacher.8a@school.in|parent.aarav@school.in"></span></div>
            <div class="acc-ok ap" style="--d:2.4s"><x-site.icon name="badge-check" /> Account created</div>
        </div>
        @break

    {{-- ───────── How it works 3: start learning ───────── --}}
    @case('learn')
        <div class="mpanel mk" style="--cyc:9s">
            <div class="ln-video">
                <span class="ln-play"><x-site.icon name="play" /></span>
                <span class="ln-tag">Science · Class 7</span>
                <div class="ln-prog"><i></i></div>
            </div>
            <div class="mtitle" style="margin-top:12px;">Chapter 3 · Photosynthesis</div>
            <div class="msub" style="margin-top:2px;">Notes · Video · Quiz</div>
            <div class="ln-items">
                <span class="ap" style="--d:.8s"><x-site.icon name="file-text" /> Notes.pdf</span>
                <span class="ap" style="--d:1.2s"><x-site.icon name="check-circle" /> Quiz 8/10</span>
            </div>
        </div>
        @break

@endswitch
