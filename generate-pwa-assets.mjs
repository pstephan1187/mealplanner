/**
 * Generate PWA icons and iOS splash screens using Playwright for accurate SVG rendering.
 *
 * Usage: node generate-pwa-assets.mjs
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { chromium } from 'playwright';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const publicDir = path.join(__dirname, 'public');

// Blush-pink theme colors
const bgColor = 'hsl(350, 70%, 96%)';
const iconBg = 'hsl(350, 80%, 45%)';
const iconStroke = 'hsl(0, 0%, 100%)';

// Chef hat icon paths (from favicon.svg, 24x24 viewbox)
const iconPaths = `
  <path d="M17 21a1 1 0 0 0 1-1v-5.35c0-.457.316-.844.727-1.041a4 4 0 0 0-2.134-7.589 5 5 0 0 0-9.186 0 4 4 0 0 0-2.134 7.588c.411.198.727.585.727 1.041V20a1 1 0 0 0 1 1Z"/>
  <path d="M6 17h12"/>
`;

function makeIconSvg(size) {
    const viewbox = 24;
    const padding = size * 0.15;
    const scale = (size - padding * 2) / viewbox;
    const rounding = Math.round(size * 0.2);

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
  <rect width="${size}" height="${size}" rx="${rounding}" fill="${iconBg}"/>
  <g transform="translate(${padding} ${padding}) scale(${scale})" fill="none" stroke="${iconStroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${iconPaths}</g>
</svg>`;
}

function makeSplashSvg(w, h) {
    const iconSize = 200;
    const iPadding = iconSize * 0.15;
    const iScale = (iconSize - iPadding * 2) / 24;
    const iRounding = Math.round(iconSize * 0.2);
    const iconX = (w - iconSize) / 2;
    const iconY = (h - iconSize) / 2 - h * 0.05;

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
  <rect width="${w}" height="${h}" fill="${bgColor}"/>
  <g transform="translate(${iconX} ${iconY})">
    <rect width="${iconSize}" height="${iconSize}" rx="${iRounding}" fill="${iconBg}"/>
    <g transform="translate(${iPadding} ${iPadding}) scale(${iScale})" fill="none" stroke="${iconStroke}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${iconPaths}</g>
  </g>
</svg>`;
}

// Collect all assets to generate
const assets = [];

// App icons: 180 (apple-touch-icon), 192, 512
for (const size of [180, 192, 512]) {
    const filename = size === 180 ? 'apple-touch-icon.png' : `icons/icon-${size}x${size}.png`;
    assets.push({ svg: makeIconSvg(size), w: size, h: size, filename });
}

// iOS splash screens
const splashScreens = [
    { width: 440, height: 956, ratio: 3, name: 'iphone-16-pro-max' },
    { width: 402, height: 874, ratio: 3, name: 'iphone-16-pro' },
    { width: 430, height: 932, ratio: 3, name: 'iphone-16-plus' },
    { width: 393, height: 852, ratio: 3, name: 'iphone-16' },
    { width: 428, height: 926, ratio: 3, name: 'iphone-14-plus' },
    { width: 390, height: 844, ratio: 3, name: 'iphone-14' },
    { width: 375, height: 812, ratio: 3, name: 'iphone-13-mini' },
    { width: 414, height: 896, ratio: 3, name: 'iphone-11-pro-max' },
    { width: 414, height: 896, ratio: 2, name: 'iphone-11' },
    { width: 375, height: 812, ratio: 3, name: 'iphone-x' },
    { width: 414, height: 736, ratio: 3, name: 'iphone-8-plus' },
    { width: 375, height: 667, ratio: 2, name: 'iphone-se' },
];

for (const s of splashScreens) {
    const pw = s.width * s.ratio;
    const ph = s.height * s.ratio;
    assets.push({ svg: makeSplashSvg(pw, ph), w: pw, h: ph, filename: `splash/${s.name}-portrait.png` });
    assets.push({ svg: makeSplashSvg(ph, pw), w: ph, h: pw, filename: `splash/${s.name}-landscape.png` });
}

// Build HTML with all SVGs as inline data-url images
const html = `<!DOCTYPE html><html><head><style>body{margin:0;padding:0;background:transparent}.asset{display:inline-block;overflow:hidden}</style></head><body>
${assets.map((a, i) => `<div class="asset" id="a${i}" style="width:${a.w}px;height:${a.h}px"><img src="data:image/svg+xml,${encodeURIComponent(a.svg)}" width="${a.w}" height="${a.h}"/></div>`).join('\n')}
</body></html>`;

// Ensure output directories exist
fs.mkdirSync(path.join(publicDir, 'icons'), { recursive: true });
fs.mkdirSync(path.join(publicDir, 'splash'), { recursive: true });

// Launch browser, render, screenshot each element
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1400, height: 3000 } });
await page.setContent(html, { waitUntil: 'networkidle' });

for (let i = 0; i < assets.length; i++) {
    const asset = assets[i];
    const outPath = path.join(publicDir, asset.filename);
    await page.locator(`#a${i}`).screenshot({ path: outPath });
    console.log(`✓ ${asset.filename} (${asset.w}x${asset.h})`);
}

await browser.close();

// Clean up temp files
for (const f of ['_pwa-generator.html', '_pwa-manifest.json']) {
    const p = path.join(publicDir, f);
    if (fs.existsSync(p)) fs.unlinkSync(p);
}

console.log(`\nDone! Generated ${assets.length} PWA assets.`);
