import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

/*
 * Older browsers — Chrome up to 110 (109 is the last one Windows 7 / 8 get),
 * and their like — cannot read Tailwind 4's oklch() colours or its
 * "in oklab" gradients. Every colour is a variable holding an oklch(), so
 * there the whole panel lost its colours: white tiles, black icons, black
 * borders.
 *
 * Nothing Tailwind writes is changed. After each rule that carries such a
 * value, the same rule is repeated inside an `@supports not (…)` block with
 * the colour as plain hex (or the gradient without its "in oklab"). A browser
 * that reads oklch skips the block and renders exactly as before; one that
 * does not gets the hex.
 */
const OKLCH = /oklch\(\s*([\d.]+)(%(?:25)?)?\s+([\d.]+)(%(?:25)?)?\s+([\d.]+)(?:deg)?\s*(?:\/\s*([\d.]+)(%(?:25)?)?\s*)?\)/g;

function oklchToSrgb(l, c, h, alpha, hash) {
    const rad = (h * Math.PI) / 180;
    const a = c * Math.cos(rad);
    const b = c * Math.sin(rad);

    const l3 = (l + 0.3963377774 * a + 0.2158037573 * b) ** 3;
    const m3 = (l - 0.1055613458 * a - 0.0638541728 * b) ** 3;
    const s3 = (l - 0.0894841775 * a - 1.291485548 * b) ** 3;

    const channels = [
        4.0767416621 * l3 - 3.3077115913 * m3 + 0.2309699292 * s3,
        -1.2684380046 * l3 + 2.6097574011 * m3 - 0.3413193965 * s3,
        -0.0041960863 * l3 - 0.7034186147 * m3 + 1.707614701 * s3,
    ].map((v) => {
        const gamma = v <= 0.0031308 ? 12.92 * v : 1.055 * v ** (1 / 2.4) - 0.055;
        return Math.round(Math.min(1, Math.max(0, gamma)) * 255);
    });

    if (alpha < 1) {
        return `rgba(${channels.join(',')},${+alpha.toFixed(3)})`;
    }
    return hash + channels.map((v) => v.toString(16).padStart(2, '0')).join('');
}

/** A value with every oklch() in it as sRGB — `#` is written %23 inside a data: URL. */
function withoutOklch(value) {
    const hash = value.includes('data:image/svg+xml') ? '%23' : '#';

    return value.replace(OKLCH, (_, l, lPct, c, cPct, h, alpha, alphaPct) =>
        oklchToSrgb(
            lPct ? l / 100 : +l,
            cPct ? (c / 100) * 0.4 : +c,
            +h,
            alpha === undefined ? 1 : alphaPct ? alpha / 100 : +alpha,
            hash,
        ),
    );
}

const NO_OKLCH = 'not (color:oklch(0 0 0))';
const NO_OKLAB_GRADIENT = 'not (background-image:linear-gradient(in lab,red,red))';

const oldBrowserColours = {
    postcssPlugin: 'old-browser-colours',
    Once(root, { AtRule }) {
        root.walkRules((rule) => {
            // Not inside @keyframes, and not inside an @supports an old browser
            // skips anyway (Tailwind's own color-mix blocks, or ours).
            for (let up = rule.parent; up && up.type !== 'root'; up = up.parent) {
                if (up.type === 'atrule' && (/keyframes$/.test(up.name) || up.name === 'supports')) {
                    return;
                }
            }

            const colours = [];
            const gradients = [];

            rule.each((decl) => {
                if (decl.type !== 'decl') {
                    return;
                }
                if (decl.value.includes('oklch(')) {
                    const value = withoutOklch(decl.value);
                    if (value !== decl.value) {
                        colours.push(decl.clone({ value }));
                    }
                } else if (decl.prop === '--tw-gradient-position' && /\sin\s+[a-z0-9-]+/.test(decl.value)) {
                    const value = decl.value.replace(/\s+in\s+[a-z0-9-]+(\s+(shorter|longer|increasing|decreasing)\s+hue)?/, '').trim();
                    if (value !== '') {
                        gradients.push(decl.clone({ value }));
                    }
                }
            });

            // Gradient first, so the colours' block ends up right after the rule.
            for (const [params, decls] of [[NO_OKLAB_GRADIENT, gradients], [NO_OKLCH, colours]]) {
                if (decls.length) {
                    const copy = rule.clone();
                    copy.removeAll();
                    copy.append(decls);
                    rule.after(new AtRule({ name: 'supports', params }).append(copy));
                }
            }
        });
    },
};

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    css: {
        postcss: {
            plugins: [oldBrowserColours],
        },
    },
});
