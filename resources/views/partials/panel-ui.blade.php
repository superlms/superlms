{{-- The Super Admin panel's own touches, on every page of it (body.lms-sa):

     1. Fields: every form field with its label right before it — a text box,
        a dropdown or a text area — takes the login page's look: a rounded
        box with its name in the border (the label sits on the top edge), grey
        until it is being typed in (blue), red while it shows an error. The
        forms keep their own markup; only the look changes.
     2. Files: wherever a file is picked, a file can also be dragged in and
        dropped on the picker. The picker stays as it was; while files are
        dragged over the page each picker shows a faint dashed edge, and the
        one under the pointer a blue one. A file the picker does not take
        (another type) is turned away with a red edge.
     3. Buttons: every button settles a little when pressed, and one that asks
        the server for something shows a spinner in its place until the
        answer is back (a button with a loader of its own keeps that). --}}
<style>
    /* ─── 1. Fields with their name in the border ───
       A label right before its box stays where it is in the page, set small
       on a white strip; the box is pulled up under it by half the label's
       height, so the name sits on the box's top edge. Every label-and-box
       pair takes it, wherever it stands in its form. */
    body.lms-sa #main-scroll label:has(+ :is(input:not([type="checkbox"], [type="radio"], [type="file"], [type="hidden"], [type="range"], [type="color"], [type="submit"], [type="button"]), select, textarea)) {
        position: relative; z-index: 2;
        display: block; width: fit-content; max-width: calc(100% - 1.75rem);
        margin-bottom: -.45rem !important; margin-left: .875rem !important; margin-right: 0 !important; padding: 0 .375rem !important;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        background: #fff; line-height: 1.2 !important;
        font-size: .75rem !important; font-weight: 600 !important; color: #6b7280 !important;
        letter-spacing: normal !important; text-transform: none !important;
        pointer-events: none; transition: color .15s;
    }
    body.lms-sa #main-scroll label + :is(input:not([type="checkbox"], [type="radio"], [type="file"], [type="hidden"], [type="range"], [type="color"], [type="submit"], [type="button"]), select, textarea) {
        width: 100%;
        min-height: 2.75rem;
        margin-top: 0 !important;
        border: 1.5px solid #9ca3af !important;
        border-radius: 9999px !important;
        background-color: #fff;
        padding-left: 1.125rem !important; padding-right: 1.125rem !important;
        box-shadow: none !important; outline: none !important;
        transition: border-color .15s;
    }
    body.lms-sa #main-scroll label + select:not([type]) { padding-right: 2.5rem !important; background-position: right .875rem center; }
    body.lms-sa #main-scroll label + textarea:not([type]) {
        border-radius: 1rem !important;
        padding-top: .75rem !important; padding-bottom: .625rem !important;
    }
    body.lms-sa #main-scroll label + :is(input, select, textarea):focus { border-color: #2563eb !important; }
    body.lms-sa #main-scroll label:has(+ :is(input, select, textarea):focus) { color: #2563eb !important; }
    body.lms-sa #main-scroll label + :is(input, select, textarea)[class*="border-red"] { border-color: #dc2626 !important; }
    body.lms-sa #main-scroll label:has(+ :is(input, select, textarea)[class*="border-red"]) { color: #dc2626 !important; }
    body.lms-sa #main-scroll label + :is(input, select, textarea):disabled { background-color: #f9fafb; border-color: #d1d5db !important; }

    /* ─── 2. Dropping a file on a picker ─── */
    body.lms-sa .sa-drop-ready { outline: 1.5px dashed #93c5fd; outline-offset: 3px; border-radius: .75rem; transition: outline-color .15s; }
    body.lms-sa .sa-drop-over { outline: 2px dashed #2563eb; outline-offset: 3px; border-radius: .75rem; background-color: rgb(37 99 235 / .04); }
    body.lms-sa .sa-drop-bad { outline: 2px dashed #dc2626; outline-offset: 3px; border-radius: .75rem; }

    /* ─── 3. Buttons: a press, and a spinner while the server answers ─── */
    @keyframes sa-spin { to { transform: rotate(360deg); } }
    @keyframes sa-show { to { opacity: 1; } }
    body.lms-sa :is(button, a[wire\:click]):not(:disabled) {
        transition-property: color, background-color, border-color, box-shadow, opacity, transform, scale, translate, filter;
        transition-duration: .15s;
    }
    body.lms-sa :is(button, a[wire\:click]):not(:disabled):active { scale: .96; }
    body.lms-sa [data-sa-busy] {
        pointer-events: none; cursor: progress;
        color: transparent !important;
        transition: color 0s .15s !important;
    }
    body.lms-sa [data-sa-busy] > * { visibility: hidden; transition: visibility 0s .15s; }
    body.lms-sa [data-sa-busy]::after {
        content: ''; position: absolute; left: 50%; top: 50%;
        width: 1rem; height: 1rem; margin: -.5rem 0 0 -.5rem;
        border-radius: 9999px; border: 2px solid var(--sa-spin, #6b7280); border-right-color: transparent;
        opacity: 0; animation: sa-spin .6s linear infinite, sa-show .12s ease .15s forwards;
    }
</style>
<script>
    (function () {
        if (window.__saUi) return;
        window.__saUi = true;

        // ─── 2. Drop a file on a picker ───────────────────────────────────────
        // A picker's drop area: the label that holds it (a styled picker), or
        // else the box it sits in (with its preview beside it).
        const zoneOf = input => input.closest('label') || input.parentElement;
        const pickers = () => [...document.querySelectorAll('#main-scroll input[type="file"]:not([disabled])')];
        const hasFiles = e => [...(e.dataTransfer?.types || [])].includes('Files');

        function hit(target) {
            let best = null;
            for (const input of pickers()) {
                const zone = zoneOf(input);
                if (zone && zone.contains(target) && (!best || best.zone.contains(zone))) best = { zone, input };
            }
            return best;
        }

        function takes(input, file) {
            const accept = (input.getAttribute('accept') || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean);
            if (!accept.length) return true;
            const name = file.name.toLowerCase(), type = (file.type || '').toLowerCase();
            return accept.some(a => a.startsWith('.') ? name.endsWith(a)
                : a.endsWith('/*') ? type.startsWith(a.slice(0, -1))
                : type === a);
        }

        let depth = 0, over = null;
        const ready = on => pickers().forEach(i => zoneOf(i)?.classList.toggle('sa-drop-ready', on));
        const setOver = zone => {
            if (over === zone) return;
            over?.classList.remove('sa-drop-over');
            over = zone;
            over?.classList.add('sa-drop-over');
        };
        const reset = () => { depth = 0; ready(false); setOver(null); };

        window.addEventListener('dragenter', e => {
            if (!hasFiles(e)) return;
            if (depth++ === 0) ready(true);
        });
        window.addEventListener('dragleave', e => {
            if (!hasFiles(e)) return;
            if (--depth <= 0) reset();
        });
        window.addEventListener('dragover', e => {
            if (!hasFiles(e)) return;
            // A drop area of the page's own (it took the drag already) is left to itself.
            if (e.defaultPrevented) { setOver(null); return; }
            const h = hit(e.target);
            setOver(h ? h.zone : null);
            // Outside a picker a dropped file does nothing (the browser would
            // otherwise open it in place of the panel).
            e.preventDefault();
            e.dataTransfer.dropEffect = h ? 'copy' : 'none';
        });
        window.addEventListener('drop', e => {
            const handled = e.defaultPrevented;
            reset();
            if (!hasFiles(e) || handled) return;
            e.preventDefault();
            const h = hit(e.target);
            if (!h) return;
            const files = [...e.dataTransfer.files].filter(f => takes(h.input, f));
            if (!files.length) {
                h.zone.classList.add('sa-drop-bad');
                setTimeout(() => h.zone.classList.remove('sa-drop-bad'), 1200);
                return;
            }
            const dt = new DataTransfer();
            (h.input.multiple ? files : files.slice(0, 1)).forEach(f => dt.items.add(f));
            h.input.files = dt.files;
            // As if picked by hand: wire:model and the pickers' own handlers hear it.
            h.input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // ─── 3. A spinner on the button that asked the server ─────────────────
        const asks = el => [...el.attributes].some(a =>
            a.name.startsWith('wire:click')
            || ((a.name.startsWith('x-on:click') || a.name.startsWith('@click')) && a.value.includes('$wire.')));

        let armed = null;
        document.addEventListener('click', e => {
            const el = e.target.closest('button, a');
            armed = el && !el.disabled && asks(el) ? { el, at: performance.now() } : null;
        }, true);

        function busy(el) {
            const cs = getComputedStyle(el);
            el.style.setProperty('--sa-spin', cs.color);
            if (cs.position === 'static') { el.style.position = 'relative'; el.__saPos = true; }
            el.setAttribute('data-sa-busy', '');
            el.__saTimer = setTimeout(() => idle(el), 30000);
        }
        function idle(el) {
            clearTimeout(el.__saTimer);
            el.removeAttribute('data-sa-busy');
            el.style.removeProperty('--sa-spin');
            if (el.__saPos) { el.style.position = ''; el.__saPos = false; }
        }

        function hook() {
            if (window.__saUiHooked || !window.Livewire || typeof window.Livewire.hook !== 'function') return;
            window.__saUiHooked = true;
            window.Livewire.hook('commit', ({ succeed, fail }) => {
                if (!armed || performance.now() - armed.at > 1500) return;
                const el = armed.el;
                armed = null;
                // One with a loader of its own (wire:loading inside) keeps it.
                if (!el.isConnected || el.innerHTML.includes('wire:loading')) return;
                busy(el);
                succeed(() => idle(el));
                fail(() => idle(el));
            });
        }
        document.addEventListener('livewire:init', hook);
        hook();
    })();
</script>
