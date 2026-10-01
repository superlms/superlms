{{-- Inside an installed panel app the page's address stays under the app's own
     mount (App\Support\AppWindow): the page says so in its own history, so that
     every link, form and fetch made from it is known to be the app's, and its
     sign-in is the app's own — not the browser's. Nothing here in a browser tab. --}}
@if ($lmsMount = \App\Support\AppWindow::mount())
    <script>
        (function () {
            var mount = @json($lmsMount);

            function mounted(url) {
                try {
                    var a = new URL(url, window.location.href);
                    if (a.origin !== window.location.origin) return url;
                    if (a.pathname === mount || a.pathname.indexOf(mount + '/') === 0) return url;
                    if (/\.[a-z0-9]+$/i.test(a.pathname)) return url;
                    return mount + a.pathname + a.search + a.hash;
                } catch (e) { return url; }
            }

            ['pushState', 'replaceState'].forEach(function (name) {
                var original = window.history[name];
                window.history[name] = function (state, title, url) {
                    return original.call(this, state, title, url == null ? url : mounted(url));
                };
            });

            try { window.history.replaceState(window.history.state, '', window.location.href); } catch (e) {}

            /* What the panel opens "in a new tab" — a receipt, a PDF, a print
               page, an attached image — the browser would put in a tab of its
               own, outside the app. Inside the app it opens over the page
               instead, with Back and Close. Links to other sites, and files
               that only download (a spreadsheet), go on as they did. */
            if (window.__lmsViewer) return;
            window.__lmsViewer = true;

            var SHOWN = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'txt'];
            var open = null; // { box, frame, title, note, loads, timer }

            /* The address to show in the viewer, or null to leave it to the browser. */
            function viewable(url) {
                var a;
                try { a = new URL(url, window.location.href); } catch (e) { return null; }
                if (a.protocol !== 'http:' && a.protocol !== 'https:') return null;

                var ext = ((a.pathname.match(/\.([a-z0-9]+)$/i) || [])[1] || '').toLowerCase();
                if (a.origin === window.location.origin) {
                    return ext && SHOWN.indexOf(ext) < 0 && ext !== 'html' && ext !== 'php' ? null : mounted(a.href);
                }
                return SHOWN.indexOf(ext) >= 0 ? a.href : null;
            }

            function closeViewer() {
                if (!open) return;
                var was = open;
                open = null;
                clearTimeout(was.timer);
                if (was.box.parentNode) was.box.parentNode.removeChild(was.box);
            }

            function button(label) {
                var b = document.createElement('button');
                b.type = 'button';
                b.textContent = label;
                b.style.cssText = 'flex:none;display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 14px;'
                    + 'border:1px solid #e5e7eb;border-radius:8px;background:#fff;color:#111827;'
                    + 'font-family:inherit;font-size:14px;font-weight:500;line-height:1;cursor:pointer';
                b.onmouseenter = function () { b.style.background = '#f3f4f6'; };
                b.onmouseleave = function () { b.style.background = '#fff'; };
                return b;
            }

            /* Opens the viewer on an address ('' for an empty page the caller writes into). */
            function showViewer(src, label, name) {
                closeViewer();

                var box = document.createElement('div');
                box.setAttribute('data-lms-viewer', '');
                box.style.cssText = 'position:fixed;top:0;right:0;bottom:0;left:0;z-index:2147483000;display:flex;'
                    + 'flex-direction:column;background:#fff';

                var bar = document.createElement('div');
                bar.style.cssText = 'flex:none;display:flex;align-items:center;gap:12px;height:52px;padding:0 14px;'
                    + 'border-bottom:1px solid #e5e7eb;background:#fff';

                var back = button('‹ Back');
                var close = button('✕ Close');
                var title = document.createElement('div');
                title.style.cssText = 'flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;'
                    + 'text-align:center;font-family:inherit;font-size:14px;font-weight:600;line-height:1.2;color:#111827';
                title.textContent = label || '';

                var body = document.createElement('div');
                body.style.cssText = 'position:relative;flex:1;min-height:0;background:#f3f4f6';

                var note = document.createElement('div');
                note.style.cssText = 'position:absolute;top:0;right:0;bottom:0;left:0;display:flex;align-items:center;'
                    + 'justify-content:center;padding:24px;text-align:center;font-family:inherit;font-size:14px;font-weight:400;line-height:1.5;color:#6b7280';
                note.textContent = src ? 'Opening…' : '';

                var frame = document.createElement('iframe');
                frame.style.cssText = 'position:relative;display:block;width:100%;height:100%;border:0;background:transparent';
                if (name) frame.name = name;
                if (src) frame.src = src;

                var mine = { box: box, frame: frame, title: title, note: note, loads: 0, timer: null };

                frame.addEventListener('load', function () {
                    var blank = false, shown = '';
                    try {
                        blank = frame.contentWindow.location.href === 'about:blank';
                        shown = frame.contentDocument ? frame.contentDocument.title : '';
                    } catch (e) {}
                    if (blank) return;

                    mine.loads++;
                    clearTimeout(mine.timer);
                    note.style.display = 'none';
                    frame.style.background = '#fff';
                    if (shown) title.textContent = shown;
                });

                if (src) {
                    mine.timer = setTimeout(function () {
                        note.textContent = 'Still opening… A file that was downloaded instead is in your downloads — close this to go back.';
                    }, 6000);
                } else {
                    frame.style.background = '#fff';
                }

                back.onclick = function () {
                    // Back inside what was opened first; from its first page, back to the panel.
                    if (mine.loads > 1) {
                        try {
                            frame.contentWindow.history.back();
                            mine.loads -= 2;
                            return;
                        } catch (e) {}
                    }
                    closeViewer();
                };
                close.onclick = closeViewer;

                bar.appendChild(back);
                bar.appendChild(title);
                bar.appendChild(close);
                body.appendChild(note);
                body.appendChild(frame);
                box.appendChild(bar);
                box.appendChild(body);
                (document.body || document.documentElement).appendChild(box);

                open = mine;

                // A page written into the viewer (a print copy) closes it as it would its window.
                if (!src) {
                    try { frame.contentWindow.close = closeViewer; } catch (e) {}
                }

                return frame;
            }

            function labelOf(el) {
                var text = (el.getAttribute('title') || el.getAttribute('aria-label') || el.textContent || '')
                    .replace(/\s+/g, ' ').trim();
                return text.length > 80 ? '' : text;
            }

            /* Handled by the page itself (a click it cancels): left alone. */
            function cancelsClick(el) {
                for (var i = 0; i < el.attributes.length; i++) {
                    if (/click.*\.prevent/i.test(el.attributes[i].name)) return true;
                }
                return false;
            }

            document.addEventListener('click', function (e) {
                if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

                var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
                if (!a || String(a.target || '').toLowerCase() !== '_blank') return;
                if (a.hasAttribute('download') || cancelsClick(a)) return;

                var src = viewable(a.href);
                if (!src) return;

                e.preventDefault();
                showViewer(src, labelOf(a));
            }, true);

            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (!form || String(form.getAttribute('target') || '').toLowerCase() !== '_blank') return;
                if (!viewable(form.action || window.location.href)) return;

                var name = 'lms-viewer-' + Date.now();
                showViewer('', '', name);
                form.setAttribute('target', name);
                setTimeout(function () { form.setAttribute('target', '_blank'); }, 0);
            }, true);

            var nativeOpen = window.open;
            window.open = function (url, target, features) {
                var to = String(target == null ? '' : target).toLowerCase();
                var empty = url == null || url === '' || url === 'about:blank';
                var src = empty ? '' : viewable(String(url));

                if ((to !== '' && to !== '_blank') || (!empty && !src)) {
                    return nativeOpen.apply(window, arguments);
                }

                var frame = showViewer(src, '');
                return /noopener|noreferrer/i.test(String(features || '')) ? null : frame.contentWindow;
            };

            document.addEventListener('keydown', function (e) {
                if (open && (e.key === 'Escape' || e.key === 'Esc')) closeViewer();
            });

            // A page shown in the viewer asks to be closed (its own Close / Back button).
            window.addEventListener('message', function (e) {
                if (open && e.origin === window.location.origin && e.data && e.data.lmsViewer === 'close') closeViewer();
            });
        })();
    </script>
@endif
