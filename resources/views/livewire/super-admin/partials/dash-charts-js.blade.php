{{-- The Chart.js charts of the Super Admin's Home and Analytics, kept drawn
     through every Livewire update.

     Each canvas sits in a wire:ignore box with a wire:key, so a re-render (the
     panel's 5-second auto-refresh, a filter) never touches it: Livewire's
     morph used to strip the size Chart.js gives the canvas, which left the
     chart blank until the page was reloaded. The figures come from a hidden
     element outside those boxes, which every re-render does refresh; after
     each one the charts read it again and update in place (no flash, nothing
     re-created). A chart whose canvas has gone is destroyed.

     A page names its charts once:
         saCharts.define('home', { canvasId: payload => chartConfig, … })
     with its figures in <div data-sa-charts="home" data-payload="{json}">. --}}
<script>
    window.saCharts = window.saCharts || (function () {
        var pages = {};   // page name → { canvas id → build(payload) }
        var live = {};    // canvas id → { chart, sig, type }
        var queued = false;

        function payloadOf(name) {
            var el = document.querySelector('[data-sa-charts="' + name + '"]');
            if (!el) return null;
            try { return JSON.parse(el.getAttribute('data-payload') || 'null'); } catch (e) { return null; }
        }

        function drop(id) {
            if (!live[id]) return;
            try { live[id].chart.destroy(); } catch (e) {}
            delete live[id];
        }

        function build(fn, p) {
            try { return fn(p); } catch (e) { if (window.console) console.error(e); return null; }
        }

        function draw(id, canvas, cfg) {
            // What the chart shows; scriptable colours (functions) drop out.
            var sig = JSON.stringify([cfg.type, cfg.data]);
            var cur = live[id];
            if (cur && cur.chart.canvas === canvas && cur.type === cfg.type) {
                if (cur.sig !== sig) {
                    cur.chart.data = cfg.data;
                    cur.chart.options = cfg.options || {};
                    cur.chart.update();
                    cur.sig = sig;
                }
                return;
            }
            drop(id);
            var other = Chart.getChart(canvas);
            if (other) other.destroy();
            try {
                live[id] = { chart: new Chart(canvas, cfg), sig: sig, type: cfg.type };
            } catch (e) {
                if (window.console) console.error(e);
            }
        }

        function render() {
            queued = false;
            if (typeof Chart === 'undefined') return;
            Object.keys(pages).forEach(function (name) {
                var p = payloadOf(name);
                Object.keys(pages[name]).forEach(function (id) {
                    var canvas = document.getElementById(id);
                    var cfg = p && canvas ? build(pages[name][id], p) : null;
                    if (cfg) draw(id, canvas, cfg); else drop(id);
                });
            });
            Object.keys(live).forEach(function (id) {
                if (!live[id].chart.canvas || !live[id].chart.canvas.isConnected) drop(id);
            });
        }

        // One pass for however many updates land together. A timer, not the
        // next animation frame: a tab in the background gets no frames, and
        // its charts should be ready when it is looked at again.
        function schedule() {
            if (queued) return;
            queued = true;
            setTimeout(render, 30);
        }

        function hook() {
            if (window.__saChartsHooked || !window.Livewire || typeof window.Livewire.hook !== 'function') return;
            window.__saChartsHooked = true;
            window.Livewire.hook('morphed', schedule);
        }

        document.addEventListener('livewire:init', hook);
        document.addEventListener('livewire:navigated', schedule);
        document.addEventListener('DOMContentLoaded', schedule);
        window.addEventListener('load', schedule);
        hook();

        return {
            define: function (name, charts) { pages[name] = charts; schedule(); },
            render: schedule,
        };
    })();
</script>
