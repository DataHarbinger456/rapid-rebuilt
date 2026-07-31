// Part 3: retake fixed pages, perform a real upload, try payment, staff review (desktop).
import { chromium } from 'playwright';
import { writeFileSync } from 'node:fs';

const BASE = process.argv[2] ?? 'http://127.0.0.1:8090';
const OUT = '.gg/screenshots/s1';

const browser = await chromium.launch();

async function login(page, email) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name=email]', email);
  await page.fill('input[name=password]', 'password');
  await page.click('button[type=submit]');
  await page.waitForLoadState('domcontentloaded');
}

// --- Customer (mobile) ---
const ctx = await browser.newContext({ viewport: { width: 390, height: 844 } });
const page = await ctx.newPage();
await login(page, 'mike@example.com');

// Documents page + real upload
await page.goto(`${BASE}/customer/cases/41/documents`, { waitUntil: 'domcontentloaded' });
await page.screenshot({ path: `${OUT}/06-documents-upload.png` });

writeFileSync('/tmp/demo-title.pdf', '%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n');
await page.selectOption('select[name=document_type]', 'salvage_title');
await page.setInputFiles('input[name=file]', '/tmp/demo-title.pdf');
await page.click('form button[type=submit]');
await page.waitForLoadState('domcontentloaded');
console.log('after upload:', page.url());
await page.screenshot({ path: `${OUT}/06b-after-upload.png` });

// Booking page (case 13 documents_complete, policy now allows)
await page.goto(`${BASE}/customer/cases/13/schedule`, { waitUntil: 'domcontentloaded' });
await page.waitForTimeout(1000);
await page.screenshot({ path: `${OUT}/07-booking.png` });
console.log('booking:', page.url());

// Payment: submit the pay form on case 11 (placeholder Stripe key -> graceful error expected)
await page.goto(`${BASE}/customer/cases/11/pay`, { waitUntil: 'domcontentloaded' });
const payForm = page.locator('form#payment-form');
if (await payForm.count()) {
  await payForm.evaluate(f => f.submit());
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(800);
  console.log('after pay submit:', page.url());
  await page.screenshot({ path: `${OUT}/08b-payment-submit.png` }).catch(e => console.log('snap failed:', e.message));
}
await ctx.close();

// --- Staff review (desktop) ---
const staffCtx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
const staff = await staffCtx.newPage();
await login(staff, 'staff@rapidrebuilt.com');
console.log('staff at:', staff.url());
await staff.goto(`${BASE}/admin/cases/41`, { waitUntil: 'domcontentloaded' }).catch(() => {});
await staff.waitForTimeout(800);
await staff.screenshot({ path: `${OUT}/10-staff-review.png` });
console.log('staff review:', staff.url());

await browser.close();
