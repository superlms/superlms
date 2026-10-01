{{-- A panel's login screen, inside its installed app.

     The app has an opening page of its own (App\Livewire\AppLock): it asks for
     a mailed code when the app has an account on this device, and sends the
     device here — with ?password=1 — when it has none, having noted that the
     login about to be made is the app's.

     So a login screen reached any other way inside the app (the sign-in ran
     out while the app was open, or Logout) hands over to that page first: the
     code screen comes up instead of the login when the app has an account, and
     a login made in the app always counts as the app's. In a browser tab the
     login screen stays as it is.

     A window sent back here straight after being handed over is left alone.

     Pass $panel: 'admin' | 'accounts' | 'superadmin'. --}}
@if (!request()->has('password') && !request()->hasHeader('X-Livewire'))
    <script>
        (function () {
            var installed = false;
            try {
                installed = window.navigator.standalone === true
                    || window.matchMedia('(display-mode: standalone)').matches
                    || window.matchMedia('(display-mode: minimal-ui)').matches
                    || window.matchMedia('(display-mode: window-controls-overlay)').matches;
            } catch (e) {}
            if (!installed) return;

            var to = @json(\App\Support\PanelApp::handover($panel));
            var url = to.url;
            try {
                var KEY = 'superlms-app-handover';
                if (Date.now() - Number(window.sessionStorage.getItem(KEY) || 0) < 5000) return;
                window.sessionStorage.setItem(KEY, String(Date.now()));

                // The school this window had open, when the app is a school's.
                if (to.prefix) {
                    for (var i = 0; i < window.sessionStorage.length; i++) {
                        var key = window.sessionStorage.key(i);
                        if (key.indexOf(to.prefix) === 0 && /^\d+$/.test(key.slice(to.prefix.length))) {
                            url = to.pattern.replace('__ORG__', key.slice(to.prefix.length));
                            break;
                        }
                    }
                }
            } catch (e) {}

            window.location.replace(url);
        })();
    </script>
@endif
