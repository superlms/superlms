{{--
    A list of documents to open or download, in the Mark Attendance list's
    look. An Updated column only when some document has a date.
    Params: $title, $docs = [{title, url, date?}].
--}}
@php $dated = (bool) array_filter(array_map(fn ($d) => trim((string) ($d['date'] ?? '')), $docs)); @endphp
@include('school-site.shared.lms-style')
<div class="lms-card">
    <div class="lms-head">
        <h2>{{ $title }}</h2>
        <span class="meta">{{ count($docs) }} {{ \Illuminate\Support\Str::plural('document', count($docs)) }}</span>
    </div>
    <div class="lms-scroll">
        <table class="lms-tbl">
            <thead>
                <tr>
                    <th class="sno">S.No</th>
                    <th>Document</th>
                    @if ($dated)<th class="fit">Updated</th>@endif
                    <th class="fit"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($docs as $i => $d)
                    <tr>
                        <td class="sno">{{ $i + 1 }}</td>
                        <td class="strong">{{ $d['title'] ?: 'Document' }}</td>
                        @if ($dated)<td class="fit">{!! trim((string) ($d['date'] ?? '')) !== '' ? e($d['date']) : '<span class="muted">—</span>' !!}</td>@endif
                        <td class="fit">
                            @if (!empty($d['url']))
                                <a class="lms-btn" href="{{ $d['url'] }}" target="_blank" rel="noopener">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v12m0 0l-4-4m4 4l4-4M5 20h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Download
                                </a>
                            @else
                                <span class="muted">Not uploaded</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
