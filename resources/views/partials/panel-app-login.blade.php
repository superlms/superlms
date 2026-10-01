{{-- A panel's login screen, inside its installed app.

     The app opens on a mailed code, not on email and password
     (App\Livewire\AppLock). When the sign-in runs out while the app is open,
     the panel still sends the window here — so, in the installed app only, and
     only when the app has an account on this device, the login screen hands
     over to the app's opening page. In a browser tab it stays the login screen.

     "Sign in with email and password instead" on that page comes back here with
     ?password=1, and logging out clears the app's account, so the login screen
     is always reachable. A window sent back here straight after being handed
     over is left alone.

     Pass $panel: 'admin' | 'accounts' | 'superadmin'. --}}
@php
    $lmsLaunches = request()->has('password') || request()->hasHeader('X-Livewire')
        ? []
        : \App\Support\PanelApp::launches($panel);
@endphp
@if ($lmsLaunches)
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

            var launches = @json($lmsLaunches);
            var launch = launches[0];
            try {
                var KEY = 'superlms-app-handover';
                if (Date.now() - Number(window.sessionStorage.getItem(KEY) || 0) < 5000) return;
                window.sessionStorage.setItem(KEY, String(Date.now()));

                // Several schools' apps on one device: the school this window had open.
                for (var i = 0; i < launches.length; i++) {
                    if (window.sessionStorage.getItem(launches[i].name) === '1') { launch = launches[i]; break; }
                }
            } catch (e) {}

            window.location.replace(launch.url);
        })();
    </script>
@endif
