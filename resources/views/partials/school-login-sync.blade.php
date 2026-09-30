{{-- Keeps admin tabs on the school the super-admin last logged into.

     The super-admin panel's "Login as school" (Schools) switches this browser's
     admin session to that school and notes it in localStorage. An admin tab
     still showing another school then moves to the new one by itself, instead
     of staying a page whose every click now lands on the other school.

     An installed admin app is left alone — it keeps the school it was
     installed for (see admin.launch). --}}
@once
    <script>
        (function () {
            var org = @json((string) Auth::user()->organization_id);
            try {
                if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) return;
            } catch (e) {}

            window.addEventListener('storage', function (e) {
                if (e.key !== 'superlms-school-login' || !e.newValue) return;
                var msg;
                try { msg = JSON.parse(e.newValue); } catch (x) { return; }
                if (msg && msg.url && String(msg.org) !== org) {
                    window.location.href = msg.url;
                }
            });
        })();
    </script>
@endonce
