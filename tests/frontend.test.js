const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const { JSDOM } = require('jsdom');
const script = fs.readFileSync('widgets/ajax-smart-add-to-cart/assets/js/frontend.js', 'utf8');
const tick = () => new Promise(resolve => setTimeout(resolve, 30));
function fixture(fetch, setup) {
    const dom = new JSDOM(`<!doctype html><body><div class="pcw-cart" data-product-id="7" data-variable="0" data-loading="Loading" data-out-of-stock="message"><div class="pcw-cart__control" hidden><button class="pcw-cart__add" data-operation="add">Add</button><div class="pcw-cart__counter" hidden><button class="pcw-cart__down" data-operation="decrease">-</button><span class="pcw-cart__quantity"></span><button class="pcw-cart__up" data-operation="increase">+</button></div></div><p class="pcw-cart__status"></p><button class="pcw-cart__retry" hidden>Retry</button></div></body>`, { runScripts: 'outside-only', url: 'https://example.test/' });
    dom.window.pcwSmartCart = { ajaxUrl: '/ajax', messages: { network: 'Network error', choose: 'Choose', increase: 'Increase', decrease: 'Decrease', remove: 'Remove', quantity: 'Quantity' } };
    dom.window.fetch = fetch;
    if (setup) setup(dom.window);
    dom.window.eval(script);
    return dom;
}
test('zero, one, two and removal use server state; rapid clicks send one mutation', async () => {
    let quantity = 0, changes = 0;
    const dom = fixture(async (_, options) => {
        const params = options.body;
        if (params.get('action') === 'pcw_cart_change') {
            changes++;
            await tick();
            if (params.get('operation') === 'add' || params.get('operation') === 'increase') quantity++;
            else quantity--;
            return { ok: true, json: async () => ({ success: true, data: { fragments: {}, cart_hash: 'x' } }) };
        }
        return { ok: true, json: async () => ({ success: true, data: { quantity, key: quantity ? 'key' : '', nonce: 'n', token: 't', purchasable: true, soldIndividually: false } }) };
    });
    await tick();
    const doc = dom.window.document;
    const add = doc.querySelector('.pcw-cart__add');
    assert.equal(add.hidden, false);
    add.click(); add.click();
    await tick(); await tick(); await tick();
    assert.equal(changes, 1); assert.equal(doc.querySelector('.pcw-cart__quantity').textContent, '1');
    assert.equal(doc.querySelector('.pcw-cart__down').dataset.operation, 'remove');
    doc.querySelector('.pcw-cart__up').click(); await tick(); await tick(); await tick();
    assert.equal(doc.querySelector('.pcw-cart__quantity').textContent, '2');
    assert.equal(doc.querySelector('.pcw-cart__down').dataset.operation, 'decrease');
    doc.querySelector('.pcw-cart__down').click(); await tick(); await tick(); await tick();
    doc.querySelector('.pcw-cart__down').click(); await tick(); await tick(); await tick();
    assert.equal(add.hidden, false); assert.equal(quantity, 0);
    dom.window.close();
});
test('network failure shows retry and retry reloads authoritative state', async () => {
    let fail = true;
    const dom = fixture(async () => {
        if (fail) throw new Error('offline');
        return { ok: true, json: async () => ({ success: true, data: { quantity: 0, key: '', nonce: 'n', token: 't', purchasable: true } }) };
    });
    await tick();
    const doc = dom.window.document;
    assert.equal(doc.querySelector('.pcw-cart__retry').hidden, false);
    fail = false; doc.querySelector('.pcw-cart__retry').click(); await tick();
    assert.equal(doc.querySelector('.pcw-cart__retry').hidden, true);
    assert.equal(doc.querySelector('.pcw-cart__add').hidden, false);
    dom.window.close();
});

test('suppresses view-cart links only in this widget and retains WooCommerce event arguments', async () => {
    let quantity = 0;
    const observed = [];
    const fragments = { '.mini-cart': '<div>cart</div>' };
    const dom = fixture(async (_, options) => {
        const params = options.body;
        if (params.get('action') === 'pcw_cart_change') {
            quantity += params.get('operation') === 'add' ? 1 : -1;
            return { ok: true, json: async () => ({ success: true, data: { fragments, cart_hash: 'hash123' } }) };
        }
        return { ok: true, json: async () => ({ success: true, data: { quantity, key: quantity ? 'key' : '', nonce: 'n', token: 't', purchasable: true } }) };
    }, window => {
        const handlers = new Map();
        function jQuery(node) {
            return {
                node,
                on(names, handler) {
                    for (const name of names.split(' ')) {
                        if (!handlers.has(name)) handlers.set(name, []);
                        handlers.get(name).push(handler);
                    }
                    return this;
                },
                trigger(name, args = []) {
                    observed.push({ name, args });
                    for (const handler of handlers.get(name) || []) handler({ type: name }, ...args);
                    return this;
                },
            };
        }
        window.jQuery = jQuery;
        const other = window.document.createElement('a');
        other.className = 'added_to_cart wc-forward';
        other.textContent = 'Other view cart';
        other.id = 'other-view-cart';
        window.document.body.append(other);
        jQuery(window.document.body).on('added_to_cart', (_event, _fragments, _hash, button) => {
            const addLink = () => {
                const link = window.document.createElement('a');
                link.className = 'added_to_cart wc-forward';
                button.node.after(link);
            };
            addLink();
            // Also cover third-party handlers that insert the same link asynchronously.
            window.setTimeout(addLink, 0);
        });
    });
    await tick();
    const doc = dom.window.document;
    const button = doc.querySelector('.pcw-cart__add');
    button.click();
    await tick(); await tick();
    assert.equal(doc.querySelector('.pcw-cart__quantity').textContent, '1');
    assert.equal(doc.querySelectorAll('.pcw-cart__control > a.added_to_cart').length, 0);
    assert.ok(doc.querySelector('#other-view-cart'), 'links outside the widget remain untouched');
    const added = observed.find(event => event.name === 'added_to_cart');
    assert.equal(added.args[0], fragments);
    assert.equal(added.args[1], 'hash123');
    assert.equal(added.args[2].node, button, 'the original button remains available to WooCommerce listeners');
    doc.querySelector('.pcw-cart__down').click();
    await tick(); await tick();
    assert.ok(observed.some(event => event.name === 'removed_from_cart'));
    assert.ok(doc.querySelector('#other-view-cart'));
    dom.window.close();
});

test('busy change is silent, does not change quantity or emit a cart event, and refreshes state', async () => {
    let changes = 0, reads = 0, events = 0;
    const dom = fixture(async (_, options) => {
        if (options.body.get('action') === 'pcw_cart_change') {
            changes++;
            return { ok: false, status: 409, json: async () => ({ success: false, data: { code: 'pcw_cart_busy', message: 'Lock busy' } }) };
        }
        reads++;
        return { ok: true, json: async () => ({ success: true, data: { quantity: 2, key: 'key', nonce: 'n', token: 't', purchasable: true } }) };
    }, window => window.document.addEventListener('pcw:cart-changed', () => events++));
    await tick();
    const doc = dom.window.document;
    doc.querySelector('.pcw-cart__up').click();
    await tick(); await tick();
    assert.equal(changes, 1, 'a rejected mutation is not automatically retried');
    assert(reads >= 2, 'state is refreshed from server');
    assert.equal(events, 0);
    assert.equal(doc.querySelector('.pcw-cart__quantity').textContent, '2');
    assert.equal(doc.querySelector('.pcw-cart__status').textContent, '');
    assert.equal(doc.querySelector('.pcw-cart__retry').hidden, true);
    dom.window.close();
});

test('busy state read keeps confirmed display without error, disables stale actions, and can recover', async () => {
    let busy = false, reads = 0;
    const dom = fixture(async () => {
        reads++;
        if (busy) return { ok: false, status: 409, json: async () => ({ success: false, data: { code: 'pcw_cart_busy', message: 'Lock busy' } }) };
        return { ok: true, json: async () => ({ success: true, data: { quantity: 2, key: 'key', nonce: 'n', token: 't', purchasable: true } }) };
    }, window => {
        const original = window.setTimeout.bind(window);
        window.setTimeout = (callback, ms, ...args) => original(callback, ms >= 250 ? 0 : ms, ...args);
    });
    await tick();
    const doc = dom.window.document;
    busy = true; dom.window.dispatchEvent(new dom.window.Event('focus')); await tick(); await tick();
    assert.equal(reads, 5, 'busy read is tried at most four times');
    assert.equal(doc.querySelector('.pcw-cart__control').hidden, false);
    assert.equal(doc.querySelector('.pcw-cart__quantity').textContent, '2');
    assert.equal(doc.querySelector('.pcw-cart__up').disabled, true);
    assert.equal(doc.querySelector('.pcw-cart__status').textContent, '');
    assert.equal(doc.querySelector('.pcw-cart__retry').hidden, false);
    busy = false; doc.querySelector('.pcw-cart__retry').click(); await tick();
    assert.equal(doc.querySelector('.pcw-cart__up').disabled, false);
    assert.equal(doc.querySelector('.pcw-cart__retry').hidden, true);
    dom.window.close();
});

test('initial busy read does not invent a cart state and other 409 errors remain visible', async () => {
    let busy = true;
    const dom = fixture(async () => ({ ok: false, status: 409, json: async () => ({ success: false, data: { code: busy ? 'pcw_cart_busy' : 'other_error', message: busy ? 'Lock busy' : 'Other error' } }) }), window => {
        const original = window.setTimeout.bind(window);
        window.setTimeout = (callback, ms, ...args) => original(callback, ms >= 250 ? 0 : ms, ...args);
    });
    await tick(); await tick();
    const doc = dom.window.document;
    assert.equal(doc.querySelector('.pcw-cart__control').hidden, true);
    assert.equal(doc.querySelector('.pcw-cart__status').textContent, '');
    assert.equal(doc.querySelector('.pcw-cart__retry').hidden, false);
    busy = false; doc.querySelector('.pcw-cart__retry').click(); await tick();
    assert.equal(doc.querySelector('.pcw-cart__status').textContent, 'Other error');
    dom.window.close();
});

test('mutation waits for the active state read and obsolete queued reads are skipped', async () => {
    let reads = 0, changes = 0, release;
    const gate = new Promise(resolve => { release = resolve; });
    const dom = fixture(async (_, options) => {
        if (options.body.get('action') === 'pcw_cart_change') {
            changes++;
            return { ok: true, json: async () => ({ success: true, data: { fragments: {}, cart_hash: 'x' } }) };
        }
        reads++;
        if (reads === 2) await gate;
        return { ok: true, json: async () => ({ success: true, data: { quantity: 2, key: 'key', nonce: 'n', token: 't', purchasable: true } }) };
    });
    await tick();
    const doc = dom.window.document;
    dom.window.dispatchEvent(new dom.window.Event('focus')); await tick();
    dom.window.dispatchEvent(new dom.window.Event('focus'));
    doc.querySelector('.pcw-cart__up').click(); await tick();
    assert.equal(changes, 0, 'mutation does not race the active read');
    assert.equal(reads, 2);
    release(); await tick(); await tick();
    assert.equal(changes, 1);
    assert.equal(reads, 3, 'queued stale read skipped, then fresh post-mutation read sent');
    dom.window.close();
});

test('unavailable lock service is reported rather than silently treated as contention', async () => {
    const dom = fixture(async () => ({ ok: false, status: 503, json: async () => ({ success: false, data: { code: 'pcw_lock_unavailable', message: 'Lock unavailable' } }) }));
    await tick();
    const doc = dom.window.document;
    assert.equal(doc.querySelector('.pcw-cart__control').hidden, true);
    assert.equal(doc.querySelector('.pcw-cart__status').textContent, 'Lock unavailable');
    assert.equal(doc.querySelector('.pcw-cart__retry').hidden, false);
    dom.window.close();
});
