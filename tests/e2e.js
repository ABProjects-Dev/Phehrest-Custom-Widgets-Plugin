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
    const diagnostics = [];
    page.on('pageerror', error => { console.error('Browser page error:', error); diagnostics.push(`error:${error.message}`); });
    page.on('request', request => { if (request.url().includes('admin-ajax.php')) diagnostics.push(`request:${request.postData() || ''}`); });
    page.on('response', response => { if (response.url().includes('admin-ajax.php')) diagnostics.push(`response:${response.status()}`); });
    page.on('requestfailed', request => { if (request.url().includes('admin-ajax.php')) diagnostics.push(`failed:${request.failure()?.errorText}`); });
    try {
        await page.goto(`${base}/?page_id=${fixture.page}`, { waitUntil: 'domcontentloaded' });
        const widget = page.locator(`.pcw-cart[data-product-id="${fixture.product}"]`);
        const out = page.locator(`.pcw-cart[data-product-id="${fixture.out}"]`);
        try { await widget.locator('.pcw-cart__add').waitFor({ state: 'visible', timeout: 30000 }); }
        catch (error) {
            const info = await page.evaluate(() => ({ ready: document.readyState, config: typeof pcwSmartCart, scripts: [...document.scripts].map(s => s.src).filter(s => s.includes('phehrest')) }));
            throw new Error(`Initial widget not ready: ${await widget.innerText()} | info ${JSON.stringify(info)} | events ${JSON.stringify(diagnostics)}`);
        }
        await out.locator('.pcw-cart__status').waitFor({ state: 'visible' });
        // Elementor's saved responsive controls produce CSS, rather than only inline overrides.
        assert.equal(await out.locator('.pcw-cart__add').evaluate(el => getComputedStyle(el).minWidth), '180px');
        assert(await out.locator('.pcw-cart__add').evaluate(el => el.classList.contains('pcw-cart__add--icon-left')), 'saved physical icon position renders');
        await page.setViewportSize({ width: 390, height: 844 });
        assert.equal(await out.locator('.pcw-cart__add').evaluate(el => getComputedStyle(el).minWidth), '140px');
        await page.setViewportSize({ width: 1440, height: 900 });
        // Exercise the layout independently of cart requests, with the actual Elementor markup/CSS.
        // The invisible add button remains the size anchor in both counter states.
        for (const viewport of [{ width: 1440, height: 900 }, { width: 390, height: 844 }]) {
            await page.setViewportSize(viewport);
            for (const direction of ['rtl', 'ltr']) {
                for (const text of ['خرید', 'افزودن این محصول فوق العاده طولانی به سبد خرید برای آزمایش واکنش گرایی']) {
                    for (const position of ['right', 'left']) {
                        const result = await widget.evaluate((root, config) => {
                            document.documentElement.dir = config.direction;
                            const add = root.querySelector('.pcw-cart__add');
                            const label = add.querySelector('.pcw-cart__label');
                            const icon = add.querySelector('.pcw-cart__icon');
                            const counter = root.querySelector('.pcw-cart__counter');
                            label.textContent = config.text;
                            add.classList.toggle('pcw-cart__add--icon-right', config.position === 'right');
                            add.classList.toggle('pcw-cart__add--icon-left', config.position === 'left');
                            root.style.setProperty('--pcw-add-min-width', '130px');
                            root.style.setProperty('--pcw-add-icon-size', '22px');
                            const before = add.getBoundingClientRect();
                            const labelRect = label.getBoundingClientRect();
                            const iconRect = icon.getBoundingClientRect();
                            add.hidden = true;
                            counter.hidden = false;
                            root.classList.add('pcw-cart--one');
                            const anchorHidden = getComputedStyle(add).visibility === 'hidden';
                            const one = counter.getBoundingClientRect();
                            root.classList.remove('pcw-cart--one');
                            const multiple = counter.getBoundingClientRect();
                            add.hidden = false;
                            counter.hidden = true;
                            return { width: before.width, height: before.height, iconX: iconRect.x, labelX: labelRect.x, one: { width: one.width, height: one.height }, multiple: { width: multiple.width, height: multiple.height }, anchorHidden };
                        }, { direction, text, position });
                        assert(result.width >= 129, `minimum width applies: ${JSON.stringify(result)}`);
                        assert(result.anchorHidden, 'size anchor is visually and accessibly hidden');
                        assert(position === 'right' ? result.iconX > result.labelX : result.iconX < result.labelX, `physical icon position ${position} in ${direction}`);
                        for (const state of [result.one, result.multiple]) {
                            assert(Math.abs(result.width - state.width) < 0.1 && Math.abs(result.height - state.height) < 0.1, `counter matches add button at ${viewport.width}px: ${JSON.stringify(result)}`);
                        }
                    }
                }
            }
        }
        await widget.evaluate(root => {
            root.querySelector('.pcw-cart__label').textContent = 'افزودن به سبد خرید';
            root.querySelector('.pcw-cart__add').classList.replace('pcw-cart__add--icon-left', 'pcw-cart__add--icon-right');
            root.style.removeProperty('--pcw-add-min-width');
            root.style.removeProperty('--pcw-add-icon-size');
            document.documentElement.dir = 'rtl';
        });
        await page.setViewportSize({ width: 1440, height: 900 });
        assert.equal(await page.locator('html').getAttribute('dir'), 'rtl', 'Persian site renders RTL');
        assert.equal(await out.locator('.pcw-cart__add').isDisabled(), true, 'out-of-stock is disabled');
        await page.screenshot({ path: `${output}/01-desktop-empty-rtl.png`, fullPage: true });
        await page.evaluate(() => { document.documentElement.setAttribute('dir', 'ltr'); });
        await page.screenshot({ path: `${output}/01b-desktop-ltr-simulated.png`, fullPage: true });
        await page.evaluate(() => { document.documentElement.setAttribute('dir', 'rtl'); });
        await out.scrollIntoViewIfNeeded();
        await page.screenshot({ path: `${output}/02-out-of-stock.png`, fullPage: true });
        await widget.scrollIntoViewIfNeeded();
        const initialBox = await widget.locator('.pcw-cart__add').boundingBox();
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
        await page.locator('.widget_shopping_cart_content').getByText('محصول آزمایشی فهرست', { exact: false }).first().waitFor();
        assert.equal(await widget.locator('a.added_to_cart').count(), 0, 'widget does not display WooCommerce view-cart link');
        const oneBox = await widget.locator('.pcw-cart__counter').boundingBox();
        assert(Math.abs(initialBox.width - oneBox.width) < 0.1 && Math.abs(initialBox.height - oneBox.height) < 0.1, 'actual cart state one retains button size');
        await page.screenshot({ path: `${output}/04-one.png`, fullPage: true });
        await widget.locator('.pcw-cart__up').click();
        await page.waitForFunction(product => document.querySelector(`.pcw-cart[data-product-id="${product}"] .pcw-cart__quantity`)?.textContent === '2', fixture.product);
        const twoBox = await widget.locator('.pcw-cart__counter').boundingBox();
        assert(Math.abs(initialBox.width - twoBox.width) < 0.1 && Math.abs(initialBox.height - twoBox.height) < 0.1, 'actual cart state two retains button size');
        await page.screenshot({ path: `${output}/05-two.png`, fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        await page.screenshot({ path: `${output}/06-mobile.png`, fullPage: true });
        await page.setViewportSize({ width: 768, height: 1024 });
        await page.screenshot({ path: `${output}/06b-tablet.png`, fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        await widget.locator('.pcw-cart__down').click();
        await page.waitForFunction(product => document.querySelector(`.pcw-cart[data-product-id="${product}"] .pcw-cart__quantity`)?.textContent === '1', fixture.product);
        await widget.locator('.pcw-cart__down').click();
        await widget.locator('.pcw-cart__add').waitFor({ state: 'visible' });
        await page.locator('.widget_shopping_cart_content').getByText('محصول آزمایشی فهرست', { exact: false }).first().waitFor({ state: 'hidden' });
        assert.equal(await widget.locator('a.added_to_cart').count(), 0, 'widget does not leave a stale view-cart link after removal');
        // A standard WooCommerce button keeps its original behavior outside the widget.
        await page.locator('.pcw-test-standard .add_to_cart_button').click();
        await page.locator('.pcw-test-standard a.added_to_cart').waitFor({ state: 'visible' });
        await page.waitForFunction(product => document.querySelector(`.pcw-cart[data-product-id="${product}"] .pcw-cart__quantity`)?.textContent === '1', fixture.product);
        assert.equal(await widget.locator('a.added_to_cart').count(), 0, 'standard button event does not add a link inside widget');
        await widget.locator('.pcw-cart__down').click();
        await widget.locator('.pcw-cart__add').waitFor({ state: 'visible' });
        assert.equal(await page.locator('.pcw-test-standard a.added_to_cart').count(), 1, 'other button view-cart link is preserved');
        await page.unroute('**/wp-admin/admin-ajax.php');
        await page.route('**/wp-admin/admin-ajax.php', async route => {
            if ((route.request().postData() || '').includes('action=pcw_cart_change')) await route.abort();
            else await route.continue();
        });
        await widget.locator('.pcw-cart__add').click();
        await widget.locator('.pcw-cart__retry').waitFor({ state: 'visible' });
        await page.screenshot({ path: `${output}/07-simulated-network-error.png`, fullPage: true });
        await page.unroute('**/wp-admin/admin-ajax.php');
        // Invalid CSRF credentials cannot alter a guest cart.
        const csrf = await page.evaluate(async productId => {
            const body = new URLSearchParams({ action: 'pcw_cart_change', product_id: productId, variation_id: '0', operation: 'add', quantity: '0', key: '', nonce: 'invalid', token: 'invalid', request_id: 'b'.repeat(32) });
            const response = await fetch('/wp-admin/admin-ajax.php', { method: 'POST', credentials: 'same-origin', body });
            return response.status;
        }, String(fixture.product));
        assert.equal(csrf, 403, 'invalid CSRF credentials are rejected');
        // Two identical mutations from separate concurrent HTTP requests must never double-add.
        const concurrent = await page.evaluate(async productId => {
            async function post(values) {
                const response = await fetch('/wp-admin/admin-ajax.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, body: new URLSearchParams(values) });
                return { status: response.status, body: await response.json() };
            }
            const state = await post({ action: 'pcw_cart_state', product_id: productId, variation_id: '0' });
            if (!state.body.success) throw new Error('Unable to read cart before concurrent requests');
            const data = state.body.data;
            const payload = { action: 'pcw_cart_change', product_id: productId, variation_id: '0', operation: 'add', key: data.key, quantity: String(data.quantity), nonce: data.nonce, token: data.token, request_id: 'a'.repeat(32) };
            const attempts = await Promise.all([post(payload), post(payload)]);
            const replay = await post(payload);
            const latest = await post({ action: 'pcw_cart_state', product_id: productId, variation_id: '0' });
            return { attempts, replay, latest };
        }, String(fixture.product));
        assert(concurrent.attempts.some(x => x.body.success), 'one concurrent request succeeds');
        assert.equal(concurrent.replay.body.data.replayed, true, 'identical retry replays without mutation');
        assert.equal(concurrent.latest.body.data.quantity, 1, 'concurrent requests do not duplicate product');
        const cleanup = await page.evaluate(async ({ productId, state }) => {
            const body = new URLSearchParams({ action: 'pcw_cart_change', product_id: productId, variation_id: '0', operation: 'remove', key: state.key, quantity: String(state.quantity), nonce: state.nonce, token: state.token, request_id: 'c'.repeat(32) });
            const response = await fetch('/wp-admin/admin-ajax.php', { method: 'POST', credentials: 'same-origin', body });
            return response.json();
        }, { productId: String(fixture.product), state: concurrent.latest.body.data });
        assert.equal(cleanup.success, true, 'cleanup concurrent test cart');
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(`${base}/wp-login.php`, { waitUntil: 'domcontentloaded' });
        await page.locator('#user_login').fill('admin'); await page.locator('#user_pass').fill('test-only');
        await page.locator('#wp-submit').click(); await page.waitForURL(/wp-admin/);
        await page.goto(`${base}/wp-admin/post.php?post=${fixture.page}&action=elementor`, { waitUntil: 'domcontentloaded' });
        const editorFrame = page.frameLocator('#elementor-preview-iframe');
        await editorFrame.locator(`.pcw-cart[data-product-id="${fixture.product}"]`).waitFor({ timeout: 90000 });
        const element = editorFrame.locator('.elementor-element-pcwmain1');
        await element.hover();
        const editHandle = element.locator('.elementor-editor-element-edit');
        if (await editHandle.count()) await editHandle.first().dispatchEvent('click');
        else await element.click();
        const search = page.locator('.pcw-product-search__input');
        try { await search.waitFor({ state: 'visible', timeout: 15000 }); }
        catch (error) {
            const details = await page.locator('#elementor-panel').innerText().catch(() => 'no panel');
            throw new Error(`Product search not visible; panel: ${details.slice(0, 1500)}`);
        }
        await search.fill('محصول');
        const searchResult = page.locator('.pcw-product-search__results button').filter({ hasText: 'محصول آزمایشی فهرست' });
        await searchResult.waitFor({ state: 'visible' });
        await searchResult.click();
        assert.equal(await page.locator('.elementor-control-product_id input[data-setting="product_id"]').inputValue(), String(fixture.product), 'product search selects the product');
        await page.screenshot({ path: `${output}/08-elementor-editor.png`, fullPage: true });
        await page.goto(`${base}/wp-admin/admin.php?page=pcw-widgets`, { waitUntil: 'domcontentloaded' });
        await page.locator('input[name="design[primary]"]').fill('#123456');
        await page.locator('.pcw-admin__design input[type="submit"]').click();
        await page.waitForURL(/updated=1/);
        await page.screenshot({ path: `${output}/09-design-settings.png`, fullPage: true });
        await page.goto(`${base}/?page_id=${fixture.page}`, { waitUntil: 'domcontentloaded' });
        const add = page.locator(`.pcw-cart[data-product-id="${fixture.product}"] .pcw-cart__add`);
        await add.waitFor({ state: 'visible' });
        assert.equal(await add.evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(18, 52, 86)', 'design defaults apply inside widget');
        console.log('PASS: Real Elementor frontend, RTL, cart transitions, mobile, loading, error, unavailable product and editor.');
    } catch (error) {
        await page.screenshot({ path: `${output}/failure.png`, fullPage: true }).catch(() => {});
        throw error;
    } finally { await browser.close(); }
})().catch(error => { console.error(error); console.log('::error title=PCW browser test::' + String(error.stack || error).replace(/%/g, '%25').replace(/\r/g, '%0D').replace(/\n/g, '%0A')); process.exitCode = 1; });
