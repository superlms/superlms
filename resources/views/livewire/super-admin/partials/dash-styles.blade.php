{{-- The Super Admin's dashboard look (Home, Analytics, School Data): white
     cards with soft rounded corners and a quiet shadow, a lift on the ones
     that link somewhere, and a deep indigo-to-ink card for the one figure a
     page leads with. Brand violet leads, ink and grey follow. --}}
<style>
    .sa-card {
        background: #fff; border: 1px solid #edf0f5; border-radius: 1rem;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .04), 0 12px 32px -20px rgba(16, 24, 40, .22);
    }
    a.sa-card { transition: transform .2s cubic-bezier(.2, .8, .2, 1), box-shadow .2s, border-color .2s; }
    a.sa-card:hover {
        transform: translateY(-2px); border-color: #e2e5ee;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .05), 0 18px 36px -18px rgba(16, 24, 40, .3);
    }
    .sa-card-dark {
        color: #fff; border-radius: 1rem;
        background: radial-gradient(120% 120% at 100% 0%, #3730a3 0%, #1e1b4b 38%, #0f172a 75%);
        box-shadow: 0 18px 40px -20px rgba(15, 23, 42, .7);
    }
    .sa-card-title { font-size: .9375rem; line-height: 1.35; font-weight: 600; color: #111827; letter-spacing: -.01em; }
    .sa-card-sub { font-size: .75rem; color: #9ca3af; margin-top: .125rem; }
    .sa-chip {
        display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap;
        font-size: .75rem; font-weight: 500; padding: .25rem .625rem; border-radius: 9999px;
        background: #f5f3ff; color: #6d28d9;
    }
    .sa-dot { width: .5rem; height: .5rem; border-radius: 9999px; display: inline-block; flex-shrink: 0; }

    /* A KPI card's figure: a size down on a narrow card, so it always fits whole. */
    .sa-kpi { container-type: inline-size; }
    .sa-kpi-value { font-size: 26px; line-height: 1; }
    @container (max-width: 14rem) { .sa-kpi-value { font-size: 22px; } }

    /* A donut and its legend: side by side when the card is wide enough for
       both, the legend under the donut when it is not. */
    .sa-donut { container-type: inline-size; width: 100%; }
    .sa-donut-row { display: flex; flex-direction: column; align-items: center; gap: 1.25rem; }
    .sa-donut-row > ul { width: 100%; min-width: 0; }
    @container (min-width: 20rem) {
        .sa-donut-row { flex-direction: row; }
        .sa-donut-row > ul { flex: 1 1 0%; width: auto; }
    }
</style>
