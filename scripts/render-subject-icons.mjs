// Renders every built-in subject icon (App\Support\SubjectIcons) to a PNG in
// public/subject-icons, which the API hands to the mobile apps — an app's
// <Image> cannot draw SVG. The artwork comes straight from
// SubjectIcons::svgDocument(), so the PNGs match the web panel exactly.
//
// Run from the project root whenever an icon is added or changed. The renderer
// is not a project dependency; install it anywhere and point RESVG_FROM at it:
//   npm i --prefix /tmp/resvg @resvg/resvg-js@2
//   RESVG_FROM=/tmp/resvg/package.json node scripts/render-subject-icons.mjs
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';

const { Resvg } = createRequire(process.env.RESVG_FROM ?? import.meta.url)('@resvg/resvg-js');

// Three times the largest size the app shows (about 40pt), for sharp phones.
const SIZE = 144;
const OUT = 'public/subject-icons';

const php = `require 'vendor/autoload.php';
echo json_encode(array_map(
    fn ($key) => [$key, App\\Support\\SubjectIcons::svgDocument($key, ${SIZE})],
    App\\Support\\SubjectIcons::keys()
));`;

const icons = JSON.parse(execFileSync('php', ['-r', php], { encoding: 'utf8' }));

mkdirSync(OUT, { recursive: true });
for (const [key, svg] of icons) {
  const png = new Resvg(svg, {
    fitTo: { mode: 'width', value: SIZE },
    // The Hindi and Sanskrit tiles are letters; they need a Devanagari font.
    font: { loadSystemFonts: true },
  })
    .render()
    .asPng();
  // URLs carry hyphens ("social-science"); the icon keys carry spaces.
  writeFileSync(`${OUT}/${key.replace(/ /g, '-')}.png`, png);
  console.log(`${key} → ${OUT}/${key.replace(/ /g, '-')}.png`);
}
