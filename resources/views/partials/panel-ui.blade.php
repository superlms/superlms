{{-- The Super Admin's and the school admin's panels, on every page of them
     (body.lms-ui; the Super Admin's also body.lms-sa):

     1. Fields (both panels): every form field with its name right before it
        — a text box, a dropdown or a text area — takes the login page's look:
        a rounded box with its name in the border (the label sits on the top
        edge), grey until it is being typed in (blue), red while it shows an
        error. The forms keep their own markup; only the look changes. Left as
        they are: the small boxes of a filter bar or a toolbar (text-xs, py-1,
        h-8 …), a name set beside its box in a row, and a page's header.
     2. Files (Super Admin): wherever a file is picked, a file can also be
        dragged in and dropped on the picker. The picker stays as it was;
        while files are dragged over the page each picker shows a faint dashed
        edge, and the one under the pointer a blue one. A file the picker does
        not take (another type) is turned away with a red edge.
     3. Buttons (both panels): every button settles a little when pressed, and
        one that asks the server for something shows a spinner in its place
        until the answer is back (a button with a loader of its own keeps that). --}}
{{-- The styles are in partials.panel-ui-styles, in the page's <head>. --}}
<script>
    (function () {
        if (window.__saUi) return;
        window.__saUi = true;

        // ─── 2. Drop a file on a picker (Super Admin) ─────────────────────────
        if (document.body.classList.contains('lms-sa')) {
            // A picker's drop area: the label that holds it (a styled picker), or
            // else the box it sits in (with its preview beside it).
            const zoneOf = input => input.closest('label') || input.parentElement;
            const pickers = () => [...document.querySelectorAll('#main-scroll input[type="file"]:not([disabled])')];
            const hasFiles = e => [...(e.dataTransfer?.types || [])].includes('Files');

            const hit = target => {
                let best = null;
                for (const input of pickers()) {
                    const zone = zoneOf(input);
                    if (zone && zone.contains(target) && (!best || best.zone.contains(zone))) best = { zone, input };
                }
                return best;
            };

            const takes = (input, file) => {
                const accept = (input.getAttribute('accept') || '').split(',').map(s => s.trim().toLowerCase()).filter(Boolean);
                if (!accept.length) return true;
                const name = file.name.toLowerCase(), type = (file.type || '').toLowerCase();
                return accept.some(a => a.startsWith('.') ? name.endsWith(a)
                    : a.endsWith('/*') ? type.startsWith(a.slice(0, -1))
                    : type === a);
            };

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
        }

        // ─── 4. A list's skeleton while its page loads (Super Admin) ──────────
        // A link to a page that has a skeleton (template[data-skeleton-for],
        // partials.list-skeletons) shows it in place of the page once the new
        // page has taken longer than {{ \App\Support\ListSkeleton::DELAY_MS }}ms, until the browser has it; a
        // quicker page simply opens. Back to this page from the history shows
        // the page again.
        const slot = document.querySelector('#main-scroll .lms-slot');
        const skel = document.querySelector('#main-scroll .lms-skeleton');
        if (slot && skel && document.body.classList.contains('lms-sa')) {
            const wait = () => { clearTimeout(window.__saSkelWait); clearTimeout(window.__saSkelTimer); };
            const restore = () => { wait(); skel.hidden = true; skel.replaceChildren(); slot.hidden = false; };
            document.addEventListener('click', e => {
                if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                const a = e.target.closest('a[href]');
                if (!a || (a.target && a.target !== '_self') || a.hasAttribute('download')) return;
                const url = new URL(a.href, location.href);
                if (url.origin !== location.origin || (url.pathname === location.pathname && url.search === location.search)) return;
                const tpl = document.querySelector('template[data-skeleton-for="' + CSS.escape(url.pathname) + '"]');
                if (!tpl) return;
                wait();
                window.__saSkelWait = setTimeout(() => {
                    skel.replaceChildren(tpl.content.cloneNode(true));
                    slot.hidden = true;
                    skel.hidden = false;
                    document.getElementById('main-scroll').scrollTop = 0;
                    window.__saSkelTimer = setTimeout(restore, 20000);
                }, {{ \App\Support\ListSkeleton::DELAY_MS }});
            });
            window.addEventListener('pagehide', wait);
            window.addEventListener('pageshow', e => { if (e.persisted) restore(); });
        }

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
