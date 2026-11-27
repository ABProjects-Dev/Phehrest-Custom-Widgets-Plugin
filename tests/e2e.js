// Real WordPress + Elementor + WooCommerce browser test. Run only against the disposable CI site.
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const base = process.env.PCW_TEST_URL || 'http://127.0.0.1:8080';
const fixture = JSON.parse(fs.readFileSync('/tmp/pcw-fixture.json', 'utf8'));
const output = path.resolve('test-results/screenshots');
fs.mkdirSync(output, { recursive: true });
(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'fa-IR' });
    const page = await context.newPage();
    page.on('pageerror', error => console.error('Browser page error:', error));
    try {
        await page.goto(`${base}/?page_id=${fixture.page}`, { waitUntil: 'domcontentloaded' });
        const widget = page.locator(`.pcw-cart[data-product-id="${fixture.product}"]`);
        const out = page.locator(`.pcw-cart[data-product-id="${fixture.out}"]`);
        await widget.locator('.pcw-cart__add').waitFor({ state: 'visible' });
        await out.locator('.pcw-cart__status').waitFor({ state: 'visible' });
        assert.equal(await page.locator('html').getAttribute('dir'), 'rtl', 'Persian site renders RTL');
        assert.equal(await out.locator('.pcw-cart__add').isDisabled(), true, 'out-of-stock is disabled');
        await page.screenshot({ path: `${output}/01-desktop-empty-rtl.png`, fullPage: true });
        await out.scrollIntoViewIfNeeded();
        await page.screenshot({ path: `${output}/02-out-of-stock.png`, fullPage: true });
        await widget.scrollIntoViewIfNeeded();
        await page.route('**/wp-admin/admin-ajax.php', async route => {
            const body = route.request().postData() || '';
            if (body.includes('action=pcw_cart_change')) await new Promise(resolve => setTimeout(resolve, 1500));
            await route.continue();
        });
        await widget.locator('.pcw-cart__add').click();
        await page.waitForFunction(product => document.querySelector(`.pcw-cart[data-product-id="${product}"]`).getAttribute('aria-busy') === 'true', fixture.product);
        await page.screenshot({ path: `${output}/03-loading.png`, fullPage: true });
        await page.waitForFunction(product => document.querySelector(`.pcw-cart[data-product-id="${product}"] .pcw-cart__quantity`)?.textContent === '1', fixture.product);
        await page.waitForFunction(product => document.querySelector(`.pcw-cart[data-product-id="${product}"]`).getAttribute('aria-busy') === 'false', fixture.product);
        await page.screenshot({ path: `${output}/04-one.png`, fullPage: true });
        await widget.locator('.pcw-cart__up').click();
        await page.waitForFunction(product => document.querySelector(`.pcw-cart[data-product-id="${product}"] .pcw-cart__quantity`)?.textContent === '2', fixture.product);
        await page.screenshot({ path: `${output}/05-two.png`, fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        await page.screenshot({ path: `${output}/06-mobile.png`, fullPage: true });
        await widget.locator('.pcw-cart__down').click();
        await page.waitForFunction(product => document.querySelector(`.pcw-cart[data-product-id="${product}"] .pcw-cart__quantity`)?.textContent === '1', fixture.product);
        await widget.locator('.pcw-cart__down').click();
        await widget.locator('.pcw-cart__add').waitFor({ state: 'visible' });
        await page.unroute('**/wp-admin/admin-ajax.php');
        await page.route('**/wp-admin/admin-ajax.php', async route => {
            if ((route.request().postData() || '').includes('action=pcw_cart_change')) await route.abort();
            else await route.continue();
        });
        await widget.locator('.pcw-cart__add').click();
        await widget.locator('.pcw-cart__retry').waitFor({ state: 'visible' });
        await page.screenshot({ path: `${output}/07-simulated-network-error.png`, fullPage: true });
        await page.unroute('**/wp-admin/admin-ajax.php');
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(`${base}/wp-login.php`, { waitUntil: 'domcontentloaded' });
        await page.locator('#user_login').fill('admin'); await page.locator('#user_pass').fill('test-only');
        await page.locator('#wp-submit').click(); await page.waitForURL(/wp-admin/);
        await page.goto(`${base}/wp-admin/post.php?post=${fixture.page}&action=elementor`, { waitUntil: 'domcontentloaded' });
        const editorFrame = page.frameLocator('#elementor-preview-iframe');
        await editorFrame.locator(`.pcw-cart[data-product-id="${fixture.product}"]`).waitFor({ timeout: 90000 });
        await page.screenshot({ path: `${output}/08-elementor-editor.png`, fullPage: true });
        console.log('PASS: Real Elementor frontend, RTL, cart transitions, mobile, loading, error, unavailable product and editor.');
    } catch (error) {
        await page.screenshot({ path: `${output}/failure.png`, fullPage: true }).catch(() => {});
        throw error;
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
