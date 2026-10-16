{{-- Text as typed in Website Data: a blank line starts a new paragraph. Param: $text --}}
@foreach (preg_split('/\r?\n\s*\r?\n/', trim((string) $text)) as $para)
    @if (trim($para) !== '')
        <p>{!! nl2br(e(trim($para))) !!}</p>
    @endif
@endforeach
