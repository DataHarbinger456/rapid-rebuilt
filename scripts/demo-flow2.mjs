// Part 2 of S1 flow captures: case detail, upload, schedule, pay, tracker.
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

await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
await page.fill('input[name=email]', 'mike@example.com');
await page.fill('input[name=password]', 'password');
await page.click('button[type=submit]');
await page.waitForLoadState('networkidle');

// Case 41: pending_documents with rejected doc — detail + documents upload
await page.goto(`${BASE}/customer/cases/41`, { waitUntil: 'networkidle' });
await snap('05-case-detail-pending-docs');

await page.goto(`${BASE}/customer/cases/41/documents`, { waitUntil: 'networkidle' });
await snap('06-documents-upload');

// Case 42: scheduled — booking view; and grab the tracking URL from case page
await page.goto(`${BASE}/customer/cases/42`, { waitUntil: 'networkidle' });
const trackingUrl = await page.getAttribute('#copy-tracking-link', 'data-tracking-url');
console.log('tracking url:', trackingUrl);

// Scheduling page (use documents_complete case 13 which can schedule)
await page.goto(`${BASE}/customer/cases/13/schedule`, { waitUntil: 'networkidle' });
await snap('07-booking');

// Pay page (case 11 pending_payment) — external assets can hang networkidle
await page.goto(`${BASE}/customer/cases/11/pay`, { waitUntil: 'domcontentloaded' });
await page.waitForTimeout(1500);
await snap('08-payment');

// Tracker (public, signed URL, new context = logged out)
if (trackingUrl) {
  const pub = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const pubPage = await pub.newPage();
  await pubPage.goto(trackingUrl, { waitUntil: 'networkidle' });
  await pubPage.screenshot({ path: `${OUT}/09-tracker.png` });
  console.log('saved 09-tracker ->', pubPage.url());
}

await browser.close();
