{{--
    A small drawing of a template's home page, for its card on the School
    Website builder's Template tab — boxes only, in the colour picked.
    Params: $key (the template), $accent (the main colour).
--}}
@php
    // Only a colour is drawn with (the custom colour field is free text until it is saved).
    $a = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $accent) ? $accent : '#2B5BF5';
    $soft = "color-mix(in srgb, {$a} 14%, #fff)";
    $bar = fn ($w, $h = 4, $bg = '#E5E7EB', $extra = '') => "<span style=\"display:block;width:{$w};height:{$h}px;border-radius:9px;background:{$bg};{$extra}\"></span>";
@endphp
<span class="block w-full overflow-hidden" style="aspect-ratio: 16 / 10; background: #F8FAFC;" aria-hidden="true">
    @if ($key === 'classic')
        {{-- Strip · tricolour masthead · menu · slider · welcome card --}}
        <span style="display:flex;flex-direction:column;height:100%;">
            <span style="height:6%;background:#2D6944;"></span>
            <span style="height:22%;display:flex;align-items:center;justify-content:space-between;padding:0 8%;background:radial-gradient(ellipse 60% 140% at 0% 0%, rgba(255,153,51,.5), transparent 70%), radial-gradient(ellipse 50% 140% at 100% 100%, rgba(19,136,8,.3), transparent 70%), #FFFDF7;">
                <span style="width:16px;height:16px;border-radius:50%;border:2px solid {{ $a }};background:#fff;"></span>
                {!! $bar('48%', 6, $a) !!}
                <span style="width:16px;height:16px;border-radius:50%;border:2px solid #2D6944;background:#fff;"></span>
            </span>
            <span style="height:8%;display:flex;align-items:center;justify-content:center;gap:5px;background:#fff;border-top:1px solid #eee;border-bottom:1px solid #eee;">
                @for ($i = 0; $i < 7; $i++){!! $bar('7%', 3, '#9CA3AF') !!}@endfor
            </span>
            <span style="flex:1;position:relative;background:linear-gradient(135deg,#2D3C69,#1E2A4D);">
                <span style="position:absolute;left:50%;top:34%;transform:translateX(-50%);width:42%;">{!! $bar('100%', 5, '#ADFF2F') !!}</span>
                <span style="position:absolute;left:10%;right:10%;bottom:-1px;height:34%;background:#fff;border:1px solid #eee;padding:6px 8px;display:flex;flex-direction:column;gap:4px;">
                    {!! $bar('40%', 4, '#E6007E') !!}{!! $bar('90%', 3) !!}{!! $bar('75%', 3) !!}
                </span>
            </span>
        </span>
    @elseif ($key === 'vibrant')
        {{-- Gradient strip · pill menu · centred headline · colourful bento cards --}}
        <span style="display:flex;flex-direction:column;height:100%;background:radial-gradient(circle at 15% 30%, {{ $soft }}, transparent 45%), radial-gradient(circle at 85% 20%, #FCE7F3, transparent 40%), radial-gradient(circle at 70% 90%, #FEF3C7, transparent 45%), #fff;">
            <span style="height:5%;background:linear-gradient(90deg, {{ $a }}, #7C3AED, #EC4899);"></span>
            <span style="display:flex;justify-content:center;padding-top:5%;">
                <span style="display:flex;gap:4px;padding:3px 8px;border-radius:99px;background:#fff;box-shadow:0 1px 4px rgba(15,23,42,.12);">
                    @for ($i = 0; $i < 6; $i++){!! $bar('9px', 3, $i === 0 ? $a : '#CBD5E1') !!}@endfor
                </span>
            </span>
            <span style="display:flex;flex-direction:column;align-items:center;gap:4px;padding:6% 0 5%;">
                {!! $bar('46%', 6, '#0F172A') !!}
                {!! $bar('34%', 6, "linear-gradient(90deg, {$a}, #7C3AED, #EC4899)") !!}
                <span style="display:flex;gap:3px;margin-top:3px;">{!! $bar('42px', 9, '#fff', 'border:1px solid #E2E8F0;') !!}{!! $bar('22px', 9, $a) !!}</span>
            </span>
            <span style="flex:1;display:grid;grid-template-columns:1fr 1fr 1fr;grid-template-rows:1fr 1fr;gap:4px;padding:0 7% 0;">
                <span style="border-radius:6px 6px 0 0;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.1);"></span>
                <span style="border-radius:6px 6px 0 0;background:#fff;box-shadow:0 1px 3px rgba(15,23,42,.1);"></span>
                <span style="grid-row:span 2;border-radius:6px 6px 0 0;background:radial-gradient(circle at 50% 80%, #F0ABFC, transparent 55%), radial-gradient(circle at 30% 100%, #93C5FD, transparent 50%), #fff;box-shadow:0 1px 3px rgba(15,23,42,.1);"></span>
                <span style="grid-column:span 2;border-radius:6px 6px 0 0;background:linear-gradient(120deg, {{ $a }}, #7C3AED 70%, #EC4899);"></span>
            </span>
        </span>
    @else
        {{-- Light header with a capsule menu · split hero with a photo card · soft dashboard cards --}}
        <span style="display:flex;flex-direction:column;height:100%;background:linear-gradient(180deg, {{ $soft }} 0%, #F8FAFC 60%);">
            <span style="height:12%;display:flex;align-items:center;justify-content:space-between;padding:0 7%;background:#fff;border-bottom:1px solid #EEF1F6;">
                <span style="display:flex;align-items:center;gap:4px;"><span style="width:9px;height:9px;border-radius:50%;background:{{ $a }};"></span>{!! $bar('28px', 4, '#334155') !!}</span>
                <span style="display:flex;gap:3px;padding:2px 5px;border-radius:99px;background:#F1F5F9;">@for ($i = 0; $i < 4; $i++){!! $bar('8px', 3, $i === 1 ? $a : '#CBD5E1') !!}@endfor</span>
                {!! $bar('20px', 8, '#fff', "border:1.5px solid {$a};") !!}
            </span>
            <span style="display:grid;grid-template-columns:1.1fr 1fr;gap:8%;align-items:center;padding:7% 7% 5%;">
                <span style="display:flex;flex-direction:column;gap:4px;">
                    {!! $bar('40%', 5, $soft, "border:1px solid {$a}33;") !!}
                    {!! $bar('95%', 6, '#0F172A') !!}{!! $bar('70%', 6, $a) !!}
                    {!! $bar('85%', 3, '#CBD5E1') !!}
                    <span style="display:flex;gap:3px;margin-top:2px;">{!! $bar('26px', 8, $a) !!}{!! $bar('26px', 8, '#fff', 'border:1px solid #E2E8F0;') !!}</span>
                </span>
                <span style="aspect-ratio:4/3;border-radius:8px;background:linear-gradient(135deg, {{ $soft }}, #E2E8F0);box-shadow:0 2px 6px rgba(15,23,42,.08);position:relative;">
                    <span style="position:absolute;left:-10%;bottom:12%;width:48%;height:22%;border-radius:5px;background:#fff;box-shadow:0 2px 5px rgba(15,23,42,.12);"></span>
                </span>
            </span>
            <span style="flex:1;display:grid;grid-template-columns:repeat(3,1fr);gap:4px;padding:0 7%;">
                @foreach (['#EEF2FF', '#FDF2F8', '#ECFDF5'] as $tint)
                    <span style="border-radius:6px 6px 0 0;background:#fff;border:1px solid #EEF1F6;border-bottom:0;padding:5px;display:flex;flex-direction:column;gap:3px;">
                        <span style="width:10px;height:10px;border-radius:3px;background:{{ $tint }};"></span>
                        {!! $bar('80%', 3) !!}{!! $bar('60%', 3, $loop->first ? $a : '#E5E7EB') !!}
                    </span>
                @endforeach
            </span>
        </span>
    @endif
</span>
