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
        })();
    </script>
@endif
