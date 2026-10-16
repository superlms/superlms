/*
 * Every date box reads day, month, year — "16 Oct 2026" — whatever language
 * the browser is set to (Chrome on an English-US machine shows mm/dd/yyyy).
 *
 * The box stays a native <input type="date"> (or datetime-local): its value
 * (Y-m-d), wire:model, min/max and calendar are untouched. Its own text is
 * hidden and the date is written over it from data-display (app.css). A box
 * with data-native-date is left as the browser draws it. Browsers without the
 * WebKit date parts (Firefox) keep their own format, which follows the
 * computer's.
 */

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const SELECTOR = 'input[type="date"]:not([data-native-date]), input[type="datetime-local"]:not([data-native-date])';

function label(input) {
    const m = /^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/.exec(input.value || '');
    if (!m || !MONTHS[+m[2] - 1]) {
        return '';
    }

    let text = `${m[3]} ${MONTHS[+m[2] - 1]} ${m[1]}`;
    if (input.type === 'datetime-local' && m[4] !== undefined) {
        const hour = +m[4];
        text += `, ${String(hour % 12 || 12).padStart(2, '0')}:${m[5]} ${hour >= 12 ? 'PM' : 'AM'}`;
    }

    return text;
}

function refresh(input) {
    const text = label(input);
    if (input.getAttribute('data-display') !== text) {
        input.setAttribute('data-display', text);
    }
    const empty = input.type === 'date' ? 'dd mmm yyyy' : 'dd mmm yyyy, --:--';
    if (input.getAttribute('data-empty') !== empty) {
        input.setAttribute('data-empty', empty);
    }
}

function refreshAll() {
    document.querySelectorAll(SELECTOR).forEach(refresh);
}

// Many changes at once (a Livewire update) refresh once, just after them.
let queued = false;
function schedule() {
    if (queued) return;
    queued = true;
    setTimeout(() => {
        queued = false;
        refreshAll();
    }, 0);
}

const isDateBox = (el) => el instanceof HTMLInputElement && el.matches(SELECTOR);

// Picked or typed.
['input', 'change'].forEach((type) => document.addEventListener(type, (e) => {
    if (isDateBox(e.target)) refresh(e.target);
}, true));

// The date text is drawn over the box, so a click anywhere on it opens the calendar.
document.addEventListener('click', (e) => {
    const input = e.target;
    if (isDateBox(input) && !input.disabled && !input.readOnly && typeof input.showPicker === 'function') {
        try { input.showPicker(); } catch (_) { /* not allowed here — the icon still opens it */ }
    }
});

// Boxes Livewire or Alpine add or redraw (a morph takes data-display away
// again), and values Livewire sets after a request.
new MutationObserver((records) => {
    for (const r of records) {
        if (r.type === 'attributes' ? isDateBox(r.target) : r.addedNodes.length) {
            schedule();
            return;
        }
    }
}).observe(document.documentElement, { subtree: true, childList: true, attributes: true, attributeFilter: ['data-display', 'value', 'type'] });

let hooked = false;
function hookLivewire() {
    if (hooked || !window.Livewire?.hook) return;
    hooked = true;
    window.Livewire.hook('commit', ({ succeed }) => succeed(() => schedule()));
}
document.addEventListener('livewire:init', hookLivewire);
document.addEventListener('livewire:navigated', schedule);
document.addEventListener('DOMContentLoaded', () => {
    hookLivewire();
    refreshAll();
});
hookLivewire();
refreshAll();
