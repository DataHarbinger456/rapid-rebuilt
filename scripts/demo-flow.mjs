// Drives the S1 customer flow and captures screenshots into .gg/screenshots/s1/.
// Usage: node scripts/demo-flow.mjs [baseUrl]
import { chromium } from 'playwright';

const BASE = process.argv[2] ?? 'http://127.0.0.1:8090';
const OUT = '.gg/screenshots/s1';

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
const page = await context.newPage();

async function snap(name) {
  await page.screenshot({ path: `${OUT}/${name}.png` });
  console.log('saved', name, '->', page.url());
}

// Login as demo customer
await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
await page.fill('input[name=email]', 'mike@example.com');
await page.fill('input[name=password]', 'password');
await page.click('button[type=submit]');
await page.waitForLoadState('networkidle');
console.log('after login:', page.url());

// Dashboard
await page.goto(`${BASE}/customer`, { waitUntil: 'networkidle' });
await snap('02-dashboard');

// Case create wizard
await page.goto(`${BASE}/customer/cases/create`, { waitUntil: 'networkidle' });
await snap('03-case-create');

// Cases list — find the pending_documents demo case link
await page.goto(`${BASE}/customer/cases`, { waitUntil: 'networkidle' });
await snap('04-cases-list');

await browser.close();
