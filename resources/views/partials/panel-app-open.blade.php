{{-- Says "this panel is open" for as long as this page is.

     An installed panel app asks for a mailed code when it is opened — but not
     while the panel is already open in some window or tab (App\Livewire\AppLock).
     Each open page of a panel holds a shared browser lock named after the panel
     (a school's admin panel: after the school). Nothing ever waits on the lock;
     it is only there to be seen. The browser drops it the moment the page is
     closed, so there is nothing to clean up and nothing to go stale.

     The note in sessionStorage covers a shortcut that reuses this very window:
     by the time the app's opening page looks, this page — and its lock — is
     gone, but the window still says the panel was open in it. --}}
@php($lmsOpenName = \App\Support\PanelApp::openNameFor(Auth::user()))
@if ($lmsOpenName)
    <script>
        (function () {
            var name = @json($lmsOpenName);
            if (window.__lmsPanelOpen === name) return;
            window.__lmsPanelOpen = name;

            try { window.sessionStorage.setItem(name, '1'); } catch (e) {}

            try {
                if (navigator.locks && navigator.locks.request) {
                    navigator.locks.request(name, { mode: 'shared' }, function () {
                        return new Promise(function () {}); // held until the page goes
                    });
                }
            } catch (e) {}
        })();
    </script>
@endif
