const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const { JSDOM } = require('jsdom');
const script = fs.readFileSync('widgets/ajax-smart-add-to-cart/assets/js/frontend.js', 'utf8');
const tick = () => new Promise(resolve => setTimeout(resolve, 30));
function fixture(fetch) {
    const dom = new JSDOM(`<!doctype html><body><div class="pcw-cart" data-product-id="7" data-variable="0" data-loading="Loading" data-out-of-stock="message"><div class="pcw-cart__control" hidden><button class="pcw-cart__add" data-operation="add">Add</button><div class="pcw-cart__counter" hidden><button class="pcw-cart__down" data-operation="decrease">-</button><span class="pcw-cart__quantity"></span><button class="pcw-cart__up" data-operation="increase">+</button></div></div><p class="pcw-cart__status"></p><button class="pcw-cart__retry" hidden>Retry</button></div></body>`, { runScripts: 'outside-only', url: 'https://example.test/' });
    dom.window.pcwSmartCart = { ajaxUrl: '/ajax', messages: { network: 'Network error', choose: 'Choose', increase: 'Increase', decrease: 'Decrease', remove: 'Remove', quantity: 'Quantity' } };
    dom.window.fetch = fetch;
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
