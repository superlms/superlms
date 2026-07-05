{{-- Global live auto-refresh: re-renders every Livewire component on the page
     on a fixed interval so new data (enquiries, payments, notifications, lists…)
     appears without a manual reload. Uses Livewire's $refresh — public property
     state (open panels, typed inputs bound with wire:model) is preserved, and it
     pauses while the browser tab is hidden to save resources. --}}
@once
    <script>
        (function () {
            var INTERVAL_MS = 5000; // refresh every 5 seconds

            function refreshAll() {
                if (document.hidden || !window.Livewire) return;
                try {
                    window.Livewire.all().forEach(function (component) {
                        try {
                            if (component.$wire && typeof component.$wire.$refresh === 'function') {
                                component.$wire.$refresh();
                            } else if (typeof component.call === 'function') {
                                component.call('$refresh');
                            }
                        } catch (e) { /* ignore a single component failure */ }
                    });
                } catch (e) { /* ignore */ }
            }

            function start() {
                if (window.__superlmsAutoRefresh) return; // never start twice
                window.__superlmsAutoRefresh = setInterval(refreshAll, INTERVAL_MS);
            }

            if (window.Livewire) {
                start();
            } else {
                document.addEventListener('livewire:init', start);
            }
        })();
    </script>
@endonce
