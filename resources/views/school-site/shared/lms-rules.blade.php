{{--
    The school's Rules & Regulations as saved on More → Rules & Regulations
    (App\Support\SchoolSiteData::rules): each rule's heading and text, the
    additional information and the PDFs to download. Param: $rules.
--}}
@include('school-site.shared.lms-style')
<div class="lms lms-rules">
    @if ($rules['sections'])
        <ol>
            @foreach ($rules['sections'] as $s)
                <li>
                    @if ($s['head'] !== '')<b>{{ $s['head'] }}</b>@endif
                    @if ($s['desc'] !== '')<div class="desc">{!! nl2br(e($s['desc'])) !!}</div>@endif
                </li>
            @endforeach
        </ol>
    @endif

    @if ($rules['info'])
        <dl class="lms-info">
            @foreach ($rules['info'] as $i)
                <dt>{{ $i['key'] ?: '—' }}</dt>
                <dd>{{ $i['value'] }}</dd>
            @endforeach
        </dl>
    @endif

    @if ($rules['files'])
        <div class="lms-files">
            @foreach ($rules['files'] as $f)
                <a href="{{ $f['url'] }}" target="_blank" rel="noopener">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ $f['title'] }}
                </a>
            @endforeach
        </div>
    @endif
</div>
