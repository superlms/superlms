{{-- A class with a single section: the filter bars do not ask for it.

     Every filter bar's Section box carries data-sole-section. Once the class
     picked has only one section, that section is chosen by itself — set on
     the box and sent the way a pick by hand is (a "change"), so the page's own
     code runs exactly as before — and the box is hidden (with the "→" before
     it, or the labelled wrapper marked data-sole-section-wrap). A class with
     more sections, or none, shows the box as it always did.

     Only filter bars are marked: forms and panels that pick a class and a
     section (Mark Attendance, Upload Marks, Issue Report Cards, Generate,
     Add / Edit) are left as they are. --}}
<script>
    (function () {
        if (window.__lmsSoleSection) return;
        window.__lmsSoleSection = 1;

        function apply() {
            document.querySelectorAll('select[data-sole-section]').forEach(function (el) {
                var real = Array.prototype.filter.call(el.options, function (o) { return o.value !== ''; });
                var only = (real.length === 1 && !el.disabled) ? real[0] : null;

                var box = (el.parentElement && el.parentElement.hasAttribute('data-sole-section-wrap')) ? el.parentElement : el;
                var sep = box.previousElementSibling;
                if (!(sep && sep.tagName === 'SPAN' && sep.textContent.trim() === '→')) sep = null;

                if (only) {
                    box.style.display = 'none';
                    if (sep) sep.style.display = 'none';
                    if (el.value !== only.value) {
                        el.value = only.value;
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                } else {
                    if (box.style.display === 'none') box.style.display = '';
                    if (sep && sep.style.display === 'none') sep.style.display = '';
                }
            });
        }

        var hooked = false;
        function hook() {
            if (hooked || !window.Livewire || typeof window.Livewire.hook !== 'function') return;
            hooked = true;
            // Right after each update is put on the page — before it is painted,
            // so a box that is to be hidden never shows.
            window.Livewire.hook('morphed', function () { apply(); });
        }

        // Only once Livewire is listening, so the pick reaches the page's code
        // (a class already chosen on load — from the address — is handled too).
        if (window.Livewire) hook();
        document.addEventListener('livewire:init', hook);
        document.addEventListener('livewire:initialized', apply);
        document.addEventListener('livewire:navigated', apply);
    })();
</script>
