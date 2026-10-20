{{--
    The school's Rules & Regulations as saved on More → Rules & Regulations
    (App\Support\SchoolSiteData::rules), in the Mark Attendance list's look:
    the rules (a heading and its text), the admission rules from Website
    Data, the additional information and the PDFs — a table each, only the
    ones there are. Params: $rules, optional $extra = [{text}] (admission rules).
--}}
@php
    $extra   = array_values(array_filter(array_map(fn ($r) => trim((string) ($r['text'] ?? '')), $extra ?? [])));
    $heads   = array_column($rules['sections'], 'head');
    $headed  = (bool) array_filter($heads);
    $headFit = max(array_map('mb_strlen', $heads) ?: [0]) <= 28 ? 'fit' : '';
    $keyFit  = max(array_map('mb_strlen', array_column($rules['info'], 'key')) ?: [0]) <= 28 ? 'fit' : '';
@endphp
@include('school-site.shared.lms-style')

@if ($rules['sections'])
    <div class="lms-card">
        <div class="lms-head">
            <h2>Rules &amp; Regulations</h2>
            <span class="meta">{{ count($rules['sections']) }} {{ \Illuminate\Support\Str::plural('rule', count($rules['sections'])) }}</span>
        </div>
        <div class="lms-scroll">
            <table class="lms-tbl">
                <thead>
                    <tr>
                        <th class="sno">S.No</th>
                        @if ($headed)<th class="{{ $headFit }}" @if (! $headFit) style="width:28%;" @endif>Rule</th>@endif
                        <th>{{ $headed ? 'Details' : 'Rule' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rules['sections'] as $i => $s)
                        <tr>
                            <td class="sno">{{ $i + 1 }}</td>
                            @if ($headed)<td class="strong {{ $headFit }}">{{ $s['head'] ?: '—' }}</td>@endif
                            <td class="desc">{!! nl2br(e($s['desc'])) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($extra)
    <div class="lms-card">
        <div class="lms-head">
            <h2>{{ $rules['sections'] ? 'Admission Rules' : 'Rules & Regulations' }}</h2>
            <span class="meta">{{ count($extra) }} {{ \Illuminate\Support\Str::plural('rule', count($extra)) }}</span>
        </div>
        <table class="lms-tbl">
            <tbody>
                @foreach ($extra as $i => $text)
                    <tr>
                        <td class="sno">{{ $i + 1 }}</td>
                        <td class="desc">{{ $text }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if ($rules['info'])
    <div class="lms-card">
        <div class="lms-head"><h2>Additional Information</h2></div>
        <table class="lms-tbl">
            <tbody>
                @foreach ($rules['info'] as $info)
                    <tr>
                        <td class="strong {{ $keyFit }}" @if (! $keyFit) style="width:30%;" @endif>{{ $info['key'] ?: '—' }}</td>
                        <td class="desc">{{ $info['value'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@if ($rules['files'])
    @include('school-site.shared.lms-docs', ['title' => 'Documents', 'docs' => $rules['files']])
@endif
