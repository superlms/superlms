{{-- Keeps admin tabs on the school the super-admin last logged into.

     The super-admin panel's "Login as school" (Schools) switches this browser's
     admin session to that school and notes it in localStorage. An admin tab
     still showing another school then moves to the new one by itself, instead
     of staying a page whose every click now lands on the other school.

     The note is written by the school's own page, the first one after the
     login (SchoolLoginController flashes it), so it is only ever written once
     the switch has really happened. A tab asleep when it was written — Chrome
     freezes background tabs, and a frozen tab never hears the storage event —
     looks again when it is shown, and follows if the login came after it was
     loaded.

     An installed admin app is left alone — it keeps the school it was
     installed for (see admin.launch). --}}
@once
    <script>
        (function () {
            var org = @json((string) Auth::user()->organization_id);
            var KEY = 'superlms-school-login';
            var loadedAt = Date.now();
            try {
                if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) return;
            } catch (e) {}

            @if (session('school-login'))
                // This page is the school just logged into: tell the other tabs.
                try {
                    var note = @json(session('school-login'));
                    note.at = loadedAt;
                    localStorage.setItem(KEY, JSON.stringify(note));
                } catch (e) {}
            @endif

            function follow(raw, onlyNewer) {
                if (!raw) return;
                var msg;
                try { msg = JSON.parse(raw); } catch (x) { return; }
                if (!msg || !msg.url || String(msg.org) === org) return;
                if (onlyNewer && !(Number(msg.at) > loadedAt)) return;
                window.location.href = msg.url;
            }

            window.addEventListener('storage', function (e) {
                if (e.key === KEY) follow(e.newValue, false);
            });

            // Back from the background (or from the back/forward cache): catch up
            // on a login that happened while this tab was not listening.
            function check() {
                if (document.visibilityState === 'hidden') return;
                try { follow(localStorage.getItem(KEY), true); } catch (e) {}
            }
            document.addEventListener('visibilitychange', check);
            window.addEventListener('pageshow', check);
            window.addEventListener('focus', check);
        })();
    </script>
@endonce
