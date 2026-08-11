/**
 * Logs into the local admin and screenshots the Settings → Users page.
 * Pass --modal to open the "Add User" dialog before capturing.
 * Usage: node scripts/shot-admin-users.cjs [baseUrl] [outPath] [--modal]
 */
const { chromium } = require('playwright');

const args = process.argv.slice(2);
const OPEN_MODAL = args.includes('--modal');
const positional = args.filter((a) => !a.startsWith('--'));

const BASE = positional[0] || 'http://127.0.0.1:8001';
const OUT = positional[1] || '.gg/screenshots/admin-settings-users.png';
const EMAIL = process.env.ADMIN_EMAIL || 'admin@rapidrebuilt.com';
const PASSWORD = process.env.ADMIN_PASSWORD || 'password';

// This script auto-submits admin credentials, so refuse anything but localhost.
const { hostname } = new URL(BASE);
if (!['127.0.0.1', 'localhost', '::1'].includes(hostname)) {
  console.error(`Refusing to run against non-local host: ${hostname}`);
  process.exit(1);
}

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

  const errors = [];
  page.on('pageerror', (e) => errors.push(`pageerror: ${e.message}`));
  page.on('response', (r) => {
    if (r.status() >= 400) errors.push(`${r.status()} ${r.url()}`);
  });

  try {
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const resp = await page.goto(`${BASE}/admin/settings/users`, {
      waitUntil: 'networkidle',
    });

    console.log('final url:', page.url());
    console.log('status:', resp.status());
    console.log('rows:', await page.locator('table tbody tr').count());

    if (OPEN_MODAL) {
      await page.click('[data-bs-target="#createUserModal"]');
      await page.waitForSelector('#createUserModal.show', { timeout: 5000 });
      const opts = await page
        .locator('#createUserModal select[name="user_type"] option')
        .allTextContents();
      console.log('modal opened; user_type options:', opts.join(', '));
    }

    await page.screenshot({ path: OUT, fullPage: !OPEN_MODAL });
    console.log('saved:', OUT);

    if (errors.length) console.log('issues:\n  ' + errors.join('\n  '));
  } finally {
    await browser.close();
  }
})();
