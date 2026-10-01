{{-- Says "this panel is open" for as long as this page is — and where: in a
     window of the installed app, or in a browser tab.

     An installed panel app asks for a mailed code when it is opened — but not
     while a window of the app is already open (App\Livewire\AppLock). Each
     open page of a panel holds a shared browser lock named after the panel (a
     school's admin panel: after the school), one name in an app window and
     another in a browser tab. Nothing ever waits on the lock; it is only there
     to be seen. The browser drops it the moment the page is closed, so there
     is nothing to clean up and nothing to go stale.

     Only an app window counts as the app being open. A browser tab on the
     panel does not let the app in without its code; its lock only tells the
     app that the browser is using the sign-in, so the app leaves it signed in.

     Installing the app (or "Open in app") turns this very tab into the app's
     window without loading anything. That is the app being opened, so the page
     goes to the app's opening page there and then — the login the first time,
     the code after — instead of staying inside on the browser's sign-in.

     The note in sessionStorage covers a shortcut that reuses the app's own
     window: by the time the app's opening page looks, this page — and its lock
     — is gone, but the window still says the panel was open in it. --}}
@php($lmsPanel = \App\Support\PanelApp::panelOf(Auth::user()))
@if ($lmsPanel)
    <script>
        (function () {
            var names = @json(\App\Support\PanelApp::openNames(...$lmsPanel));
            var gate = @json(\App\Support\PanelApp::gate(...$lmsPanel));
            if (window.__lmsPanelOpen && window.__lmsPanelOpen.name === names.app) return;

            var MODES = ['standalone', 'minimal-ui', 'window-controls-overlay'];

            function inApp() {
                try {
                    if (window.navigator.standalone === true) return true;
                    for (var i = 0; i < MODES.length; i++) {
                        if (window.matchMedia('(display-mode: ' + MODES[i] + ')').matches) return true;
                    }
                } catch (e) {}
                return false;
            }

            var held = null;

            function hold(app) {
                var name = app ? names.app : names.tab;
                if (held && held.name === name) return;
                if (held) held.drop();

                var mine = {
                    name: name,
                    dropped: false,
                    release: null,
                    drop: function () { this.dropped = true; if (this.release) this.release(); }
                };
                held = mine;

                if (app) {
                    try { window.sessionStorage.setItem(names.app, '1'); } catch (e) {}
                }

                try {
                    if (navigator.locks && navigator.locks.request) {
                        navigator.locks.request(name, { mode: 'shared' }, function () {
                            // Held until the page goes, or it changes sides.
                            return new Promise(function (release) {
                                if (mine.dropped) release(); else mine.release = release;
                            });
                        });
                    }
                } catch (e) {}
            }

            var wasApp = inApp();
            hold(wasApp);

            function modeChanged() {
                var isApp = inApp();
                if (isApp === wasApp) return;
                wasApp = isApp;

                // A browser tab just became the app's window: the app is being
                // opened — through its opening page, like every other time.
                if (isApp) {
                    if (held) held.drop();
                    window.location.replace(gate);
                    return;
                }
                hold(false);
            }

            window.__lmsPanelOpen = { name: names.app, modeChanged: modeChanged };

            try {
                MODES.forEach(function (mode) {
                    var query = window.matchMedia('(display-mode: ' + mode + ')');
                    if (query.addEventListener) query.addEventListener('change', modeChanged);
                    else if (query.addListener) query.addListener(modeChanged);
                });
            } catch (e) {}
        })();
    </script>
@endif
