{{-- Keyboard moves through the panel's fields, on every page of every panel.

     - Enter goes to the next field: in a form, a slide-in or a filter bar, the
       next box in reading order; in a list of rows (a table's rows, or the
       repeated rows of a Livewire list such as Upload Marks or Mark
       Attendance) the same column of the next row. Shift+Enter goes back.
     - ← / → go to the field beside it in the same row of such a list — from a
       text box only once the cursor is at its start / end, so editing text is
       untouched.
     - On the last field of a form Enter does what it always did (submits it).

     Left alone, as before: textareas (Enter is a new line), buttons and links,
     radio groups, date boxes (their arrows step through the date), any box
     that handles a key itself (wire:keydown / x-on:keydown / @keydown — chat,
     Super Assist, the searches that run on Enter), anything outside the page
     (the top bar's search), and anything inside [data-kb-nav="off"]. Nothing
     here talks to the server: it only moves the focus, so what a field saves
     and when is unchanged. --}}
<script>
    (function () {
        if (window.__lmsKbNav) return;
        window.__lmsKbNav = 1;

        var FIELD = 'input:not([type=hidden]):not([type=button]):not([type=submit]):not([type=reset]):not([type=image]):not([type=file]),'
            + 'select,textarea,trix-editor,[contenteditable="true"]';
        var NO_CARET = { date: 1, time: 1, 'datetime-local': 1, month: 1, week: 1, email: 1 };
        var CARET = { text: 1, search: 1, tel: 1, url: 1, password: 1 };

        function visible(el) {
            if (el.disabled || el.readOnly || el.getAttribute('tabindex') === '-1') return false;
            if (el.closest('[inert],[aria-hidden="true"]')) return false;
            return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
        }

        function fieldsIn(root) {
            return Array.prototype.filter.call(root.querySelectorAll(FIELD), visible);
        }

        // A box (or one of the few elements round it) that handles keys itself.
        function ownsKeys(el) {
            var n = el, depth = 0;
            while (n && n.nodeType === 1 && depth < 4) {
                if (n.getAttribute('data-kb-nav') === 'off') return true;
                for (var i = 0; i < n.attributes.length; i++) {
                    var name = n.attributes[i].name.toLowerCase();
                    if (/^(x-on:|@|wire:)key(down|up|press)/.test(name)
                        && !/\.(escape|esc)\b/.test(name) && !/\.(window|document)\b/.test(name)) {
                        return true;
                    }
                }
                n = n.parentElement;
                depth++;
            }
            return !!el.closest('[data-kb-nav="off"]');
        }

        function scopeOf(el) {
            return el.closest('form') || el.closest('.fixed') || document.getElementById('main-scroll') || document.body;
        }

        // A list row: a table body's row, or a repeated Livewire row — an element
        // whose wire:key follows the same pattern as its siblings' (mark-u-12,
        // mark-u-13 …), so two different keyed blocks side by side are not one.
        function keyPattern(n) {
            var k = n.getAttribute('wire:key');
            return k === null ? null : k.replace(/\d+/g, '#');
        }
        function rowOf(el, scope) {
            var tr = el.closest('tr');
            if (tr && scope.contains(tr) && tr.parentElement && tr.parentElement.tagName === 'TBODY') return tr;
            var n = el.parentElement;
            while (n && n !== scope && n !== document.body) {
                var pat = keyPattern(n);
                if (pat !== null && n.parentElement) {
                    var same = Array.prototype.filter.call(n.parentElement.children, function (c) { return keyPattern(c) === pat; });
                    if (same.length > 1) return n;
                }
                n = n.parentElement;
            }
            return null;
        }
        function sameRows(row) {
            if (row.tagName === 'TR') {
                return Array.prototype.filter.call(row.parentElement.children, function (c) { return c.tagName === 'TR'; });
            }
            var pat = keyPattern(row);
            return Array.prototype.filter.call(row.parentElement.children, function (c) { return keyPattern(c) === pat; });
        }

        function go(target) {
            if (!target) return false;
            target.focus();
            if (target.tagName === 'INPUT' && (CARET[target.type] || target.type === 'number')) {
                try { target.select(); } catch (e) {}
            }
            return true;
        }

        // The next / previous field in reading order within the scope, skipping
        // the rest of a radio group.
        function stepIn(scope, el, dir) {
            var all = fieldsIn(scope);
            var i = all.indexOf(el);
            if (i < 0) return null;
            for (var j = i + dir; j >= 0 && j < all.length; j += dir) {
                var f = all[j];
                if (el.type === 'radio' && f.type === 'radio' && f.name && f.name === el.name) continue;
                return f;
            }
            return null;
        }

        // The same column of the next / previous row; past the last row, the
        // first field after the list (before the first, the one before it).
        function stepRow(scope, row, el, dir) {
            var cols = fieldsIn(row);
            var col = cols.indexOf(el);
            var rows = sameRows(row);
            for (var r = rows.indexOf(row) + dir; r >= 0 && r < rows.length; r += dir) {
                var f = fieldsIn(rows[r]);
                if (f.length) return f[Math.min(col, f.length - 1)];
            }
            var edge = dir > 0 ? cols[cols.length - 1] : cols[0];
            var all = fieldsIn(scope), i = all.indexOf(edge);
            var inRows = function (f) { return rows.some(function (r) { return r.contains(f); }); };
            for (var j = i + dir; j >= 0 && j < all.length; j += dir) {
                if (!inRows(all[j])) return all[j];
            }
            return null;
        }

        function atEdge(el, key) {
            if (el.tagName === 'SELECT') return true;
            if (el.tagName !== 'INPUT') return false;
            var t = el.type;
            if (t === 'checkbox' || t === 'number' || t === 'range' || t === 'color') return true;
            if (t === 'radio' || NO_CARET[t] || !CARET[t]) return false;
            var s, e;
            try { s = el.selectionStart; e = el.selectionEnd; } catch (x) { return false; }
            if (s === null || s !== e) return false;
            return key === 'ArrowLeft' ? s === 0 : e === el.value.length;
        }

        document.addEventListener('keydown', function (e) {
            if (e.defaultPrevented || e.isComposing || e.ctrlKey || e.metaKey || e.altKey) return;
            var el = e.target;
            if (!el || el.nodeType !== 1 || !el.matches || !el.matches('input,select')) return;
            if (!el.matches(FIELD)) return;
            // Only the page's own fields — not the top bar's search.
            if (!el.closest('#main-scroll') && !el.closest('.fixed')) return;
            if (el.closest('.lms-navbar')) return;
            if (el.getAttribute('role') === 'combobox' || el.hasAttribute('list') || el.getAttribute('aria-autocomplete')) return;
            if (ownsKeys(el)) return;

            var scope = scopeOf(el);
            var row = rowOf(el, scope);

            if (e.key === 'Enter') {
                var dir = e.shiftKey ? -1 : 1;
                var target = row ? stepRow(scope, row, el, dir) : stepIn(scope, el, dir);
                // The last field of a form: leave Enter to submit it, as before.
                if (!target) return;
                e.preventDefault();
                go(target);
                return;
            }

            if ((e.key === 'ArrowLeft' || e.key === 'ArrowRight') && !e.shiftKey && row) {
                if (!atEdge(el, e.key)) return;
                var cols = fieldsIn(row);
                var next = cols[cols.indexOf(el) + (e.key === 'ArrowRight' ? 1 : -1)];
                if (!next) return;
                e.preventDefault();
                go(next);
            }
        });
    })();
</script>
