/*
 * A dropdown bound with wire:model shows the option its property holds after
 * every Livewire update.
 *
 * When a list changes under a dropdown (another class picked, a section filled
 * in after a student is picked), Livewire patches its <option>s in place, row
 * by row, and the browser keeps the old row highlighted — so the box could
 * read one student while another was picked (or nobody). Pointing each box
 * back at its property after the update keeps what is shown and what is
 * picked the same. A value no longer in the list leaves the box blank rather
 * than on someone else.
 */

function modelOf(el) {
    for (const attr of el.attributes) {
        if (attr.name.startsWith('wire:model')) return attr.value;
    }
    return null;
}

function sync(root, wire) {
    root.querySelectorAll('select').forEach((el) => {
        if (el.multiple || el.closest('[wire\\:id]') !== root) return;   // a nested component's own
        const path = modelOf(el);
        if (!path) return;

        let value;
        try {
            value = wire.$get(path);
        } catch (_) {
            return;
        }
        if (value === undefined || typeof value === 'object' && value !== null) return;

        const want = value === null ? '' : String(value);
        if (el.value !== want) el.value = want;
    });
}

let hooked = false;
function hook() {
    if (hooked || !window.Livewire?.hook) return;
    hooked = true;
    window.Livewire.hook('morphed', ({ el, component }) => {
        if (el && component?.$wire) sync(el, component.$wire);
    });
}

document.addEventListener('livewire:init', hook);
hook();
