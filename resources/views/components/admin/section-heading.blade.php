@props([
    'title' => '',
    'subtitle' => null,
])

{{-- A quiet rule with a label on it. The section's weight comes from the
     content underneath, not from the heading. --}}
<div class="flex items-center gap-4 pt-2">
    <div>
        <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-widest whitespace-nowrap">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-xs text-slate-400 mt-0.5 font-normal normal-case tracking-normal">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="flex-1 h-px bg-slate-200"></div>
</div>
