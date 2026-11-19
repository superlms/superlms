{{-- The fields' look of partials.panel-ui (part 1 and the file-drop and
     button styles), set in the page's <head> (components.layouts.app) so a
     page is drawn in its own look from the first frame — at the end of the
     page the old look showed for a moment while it loaded (2026-11-19). --}}
@php
    $in   = 'body.lms-ui #main-scroll';
    // A box to fill — a text box (not a tick, a file, …), a dropdown or a text
    // area — but not a small one of a filter bar or a toolbar.
    $box  = ':is(input:not([type="checkbox"], [type="radio"], [type="file"], [type="hidden"], [type="range"], [type="color"], [type="submit"], [type="button"]), select, textarea)'
          . ':not(.text-xs, [class~="py-1"], [class~="py-0.5"], [class~="h-7"], [class~="h-8"])';
    // Its name right before it in a form: not hidden, not set beside it in a
    // row (or in a grid cell of its own), not in a page's sticky header.
    $name = 'label:not(.sr-only):not(:is(.flex, .inline-flex, .grid):not(.flex-col) > *):not(.sticky *)';
@endphp
<style>
    /* ─── 1. Fields with their name in the border ───
       The name stays where it is in the page, set small on a white strip;
       the box is pulled up under it by half the name's height, so the name
       sits on the box's top edge. Every name-and-box pair takes it, wherever
       it stands in its form. */
    {!! $in !!} {!! $name !!}:has(+ {!! $box !!}) {
        position: relative; z-index: 2;
        display: block; width: fit-content; max-width: calc(100% - 1.75rem);
        margin-bottom: -.45rem !important; margin-left: .875rem !important; margin-right: 0 !important; padding: 0 .375rem !important;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        background: #fff; line-height: 1.2 !important;
        font-size: .75rem !important; font-weight: 600 !important; color: #6b7280 !important;
        letter-spacing: normal !important; text-transform: none !important;
        pointer-events: none; transition: color .15s;
    }
    {!! $in !!} {!! $name !!} + {!! $box !!} {
        min-height: 2.75rem;
        margin-top: 0 !important;
        border: 1.5px solid #9ca3af !important;
        border-radius: 9999px !important;
        background-color: #fff;
        padding-left: 1.125rem !important; padding-right: 1.125rem !important;
        box-shadow: none !important; outline: none !important;
        transition: border-color .15s;
    }
    {!! $in !!} {!! $name !!} + {!! $box !!}:is(select) { padding-right: 2.5rem !important; background-position: right .875rem center; }
    {!! $in !!} {!! $name !!} + {!! $box !!}:is(textarea) {
        border-radius: 1rem !important;
        padding-top: .75rem !important; padding-bottom: .625rem !important;
    }
    {!! $in !!} {!! $name !!} + {!! $box !!}:focus { border-color: #2563eb !important; }
    {!! $in !!} {!! $name !!}:has(+ {!! $box !!}:focus) { color: #2563eb !important; }
    {!! $in !!} {!! $name !!} + {!! $box !!}[class*="border-red"] { border-color: #dc2626 !important; }
    {!! $in !!} {!! $name !!}:has(+ {!! $box !!}[class*="border-red"]) { color: #dc2626 !important; }
    {!! $in !!} {!! $name !!} + {!! $box !!}:disabled { background-color: #f9fafb; border-color: #d1d5db !important; }

    /* ─── 2. Dropping a file on a picker (Super Admin) ─── */
    body.lms-sa .sa-drop-ready { outline: 1.5px dashed #93c5fd; outline-offset: 3px; border-radius: .75rem; transition: outline-color .15s; }
    body.lms-sa .sa-drop-over { outline: 2px dashed #2563eb; outline-offset: 3px; border-radius: .75rem; background-color: rgb(37 99 235 / .04); }
    body.lms-sa .sa-drop-bad { outline: 2px dashed #dc2626; outline-offset: 3px; border-radius: .75rem; }

    /* ─── 3. Buttons: a press, and a spinner only while a slow answer is awaited ───
       The button stays as it is for the first .5s (the skeletons' wait,
       ListSkeleton::DELAY_MS); a quick answer shows the screen straight away,
       and only a slower one turns the button into a spinner. */
    @keyframes sa-spin { to { transform: rotate(360deg); } }
    @keyframes sa-show { to { opacity: 1; } }
    body.lms-ui :is(button, a[wire\:click]):not(:disabled) {
        transition-property: color, background-color, border-color, box-shadow, opacity, transform, scale, translate, filter;
        transition-duration: .15s;
    }
    body.lms-ui :is(button, a[wire\:click]):not(:disabled):active { scale: .96; }
    body.lms-ui [data-sa-busy] {
        pointer-events: none; cursor: progress;
        color: transparent !important;
        transition: color 0s .5s !important;
    }
    body.lms-ui [data-sa-busy] > * { visibility: hidden; transition: visibility 0s .5s; }
    body.lms-ui [data-sa-busy]::after {
        content: ''; position: absolute; left: 50%; top: 50%;
        width: 1rem; height: 1rem; margin: -.5rem 0 0 -.5rem;
        border-radius: 9999px; border: 2px solid var(--sa-spin, #6b7280); border-right-color: transparent;
        opacity: 0; animation: sa-spin .6s linear infinite, sa-show .12s ease .5s forwards;
    }

    /* ─── 4. Skeletons: grey shapes of a list's rows, shimmering, while it loads ─── */
    @keyframes sa-shimmer { from { background-position: 100% 0; } to { background-position: 0 0; } }
    /* The shape's defaults sit in Tailwind's base layer, under its utilities,
       so a shape's own classes (block, rounded-full, rounded-lg …) win. */
    @layer base { .sa-skel { display: inline-block; border-radius: .375rem; } }
    .sa-skel {
        background: linear-gradient(90deg, #eef0f3 25%, #e2e5ea 37%, #eef0f3 63%);
        background-size: 400% 100%;
        animation: sa-shimmer 1.4s ease infinite;
    }
</style>
