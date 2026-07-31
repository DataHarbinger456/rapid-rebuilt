// Screenshot helper for the S1 demo flow (screenshot tool can't see local playwright).
// Usage: node scripts/snap.mjs <url> <out.png> [width] [height] [storageStatePath]
import { chromium } from 'playwright';

const [url, out, w = '390', h = '844', statePath] = process.argv.slice(2);

const browser = await chromium.launch();
const context = await browser.newContext({
  viewport: { width: parseInt(w, 10), height: parseInt(h, 10) },
  ...(statePath ? { storageState: statePath } : {}),
});
const page = await context.newPage();
await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
await page.screenshot({ path: out });
console.log('saved', out, '->', page.url());
await browser.close();
