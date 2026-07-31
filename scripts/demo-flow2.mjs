// Part 2 of S1 flow captures: case detail, upload, schedule, pay, tracker.
import { chromium } from 'playwright';
import { CASE_PENDING_DOCS, CASE_SCHEDULED, CASE_DOCS_COMPLETE, CASE_PENDING_PAYMENT } from './demo-ids.mjs';

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

// pending_documents case with rejected doc — detail + documents upload
await page.goto(`${BASE}/customer/cases/${CASE_PENDING_DOCS}`, { waitUntil: 'networkidle' });
await snap('05-case-detail-pending-docs');

await page.goto(`${BASE}/customer/cases/${CASE_PENDING_DOCS}/documents`, { waitUntil: 'networkidle' });
await snap('06-documents-upload');

// scheduled case — booking view; and grab the tracking URL from case page
await page.goto(`${BASE}/customer/cases/${CASE_SCHEDULED}`, { waitUntil: 'networkidle' });
const trackingUrl = await page.getAttribute('#copy-tracking-link', 'data-tracking-url');
console.log('tracking url:', trackingUrl);

// Scheduling page (documents_complete case can schedule)
await page.goto(`${BASE}/customer/cases/${CASE_DOCS_COMPLETE}/schedule`, { waitUntil: 'networkidle' });
await snap('07-booking');

// Pay page (pending_payment case) — external assets can hang networkidle
await page.goto(`${BASE}/customer/cases/${CASE_PENDING_PAYMENT}/pay`, { waitUntil: 'domcontentloaded' });
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
