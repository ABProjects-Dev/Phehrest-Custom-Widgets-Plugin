(() => {
    'use strict';
    if (typeof pcwSmartCart === 'undefined') return;
    const widgets = new Set();
    let changing = false;
    let stateQueue = Promise.resolve();
    const messages = pcwSmartCart.messages;

    function formData(widget) {
        if (widget.dataset.variable !== '1') return { variation_id: '0', attributes: {} };
        const forms = [...document.querySelectorAll('form.variations_form')].filter(form => String(form.dataset.product_id) === widget.dataset.productId);
        if (forms.length !== 1) return null;
        const form = forms[0];
        const variation = form.querySelector('input[name="variation_id"]');
        if (!variation || !Number(variation.value)) return null;
        const attributes = {};
        for (const input of form.querySelectorAll('[name^="attribute_"]')) {
            if (!input.name || !input.value || ((input.type === 'radio' || input.type === 'checkbox') && !input.checked)) continue;
            attributes[input.name] = input.value;
        }
        if (!Object.keys(attributes).length) return null;
        return { variation_id: variation.value, attributes };
    }
    function request(action, widget, variation, extra = {}) {
        const body = new URLSearchParams({ action, product_id: widget.dataset.productId, variation_id: variation.variation_id, ...extra });
        Object.entries(variation.attributes).forEach(([key, value]) => body.set(`attributes[${key}]`, value));
        return fetch(pcwSmartCart.ajaxUrl, { method: 'POST', credentials: 'same-origin', cache: 'no-store', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, body })
            .catch(() => { throw new Error(messages.network); })
            .then(async response => {
                let json;
                try { json = await response.json(); } catch (_) { throw new Error(messages.network); }
                if (!response.ok || !json.success) throw new Error(json.data?.message || messages.network);
                return json.data;
            });
    }
    function setBusy(widget, busy) {
        widget.setAttribute('aria-busy', String(busy));
        widget.querySelectorAll('button').forEach(button => { button.disabled = busy; });
        if (busy) widget.querySelector('.pcw-cart__status').textContent = widget.dataset.loading;
    }
    function draw(widget, state) {
        const control = widget.querySelector('.pcw-cart__control');
        const add = widget.querySelector('.pcw-cart__add');
        const counter = widget.querySelector('.pcw-cart__counter');
        const down = widget.querySelector('.pcw-cart__down');
        const up = widget.querySelector('.pcw-cart__up');
        const status = widget.querySelector('.pcw-cart__status');
        widget.querySelector('.pcw-cart__retry').hidden = true;
        const count = Number(state.quantity);
        widget.classList.toggle('pcw-cart--one', count === 1);
        control.hidden = false;
        add.hidden = count !== 0;
        counter.hidden = count === 0;
        add.disabled = !state.purchasable || changing;
        up.disabled = !state.purchasable || (state.soldIndividually && count >= 1) || changing;
        down.disabled = changing;
        down.dataset.operation = count === 1 ? 'remove' : 'decrease';
        down.setAttribute('aria-label', count === 1 ? messages.remove : messages.decrease);
        widget.querySelector('.pcw-cart__quantity').textContent = String(count);
        widget.querySelector('.pcw-cart__quantity').setAttribute('aria-label', `${messages.quantity} ${count}`);
        status.textContent = !state.purchasable && !count ? 'این محصول در حال حاضر قابل خرید نیست.' : '';
        widget.hidden = !state.purchasable && !count && widget.dataset.outOfStock === 'hide';
    }
    async function sync(widget) {
        if (!widget.isConnected) { widgets.delete(widget); return; }
        const generation = ++widget.pcwGeneration;
        const variation = formData(widget);
        if (!variation) {
            widget.pcwState = null;
            widget.hidden = false;
            widget.querySelector('.pcw-cart__control').hidden = true;
            widget.querySelector('.pcw-cart__status').textContent = messages.choose;
            widget.querySelector('.pcw-cart__retry').hidden = false;
            return;
        }
        try {
            const pending = stateQueue.then(() => request('pcw_cart_state', widget, variation));
            stateQueue = pending.catch(() => {});
            const state = await pending;
            if (generation !== widget.pcwGeneration || changing) return;
            widget.pcwState = state;
            widget.pcwVariation = variation;
            draw(widget, state);
        } catch (error) {
            if (generation !== widget.pcwGeneration) return;
            widget.pcwState = null;
            widget.querySelector('.pcw-cart__control').hidden = true;
            widget.querySelector('.pcw-cart__status').textContent = error.message;
            widget.querySelector('.pcw-cart__retry').hidden = false;
        }
    }
    async function syncAll() { await Promise.all([...widgets].map(sync)); }
    async function change(widget, button) {
        if (changing || !widget.pcwState) return;
        const currentVariation = formData(widget);
        if (!currentVariation || JSON.stringify(currentVariation) !== JSON.stringify(widget.pcwVariation)) { await sync(widget); return; }
        changing = true;
        widgets.forEach(other => { other.pcwGeneration++; setBusy(other, true); });
        let errorMessage = '';
        try {
            const state = widget.pcwState;
            const result = await request('pcw_cart_change', widget, currentVariation, {
                operation: button.dataset.operation, key: state.key, quantity: String(state.quantity), nonce: state.nonce, token: state.token,
            });
            if (window.jQuery) {
                const body = window.jQuery(document.body);
                if (button.dataset.operation === 'add') body.trigger('added_to_cart', [result.fragments, result.cart_hash, window.jQuery(button)]);
                else if (button.dataset.operation === 'remove') body.trigger('removed_from_cart', [result.fragments, result.cart_hash, window.jQuery(button)]);
                if (button.dataset.operation === 'increase' || button.dataset.operation === 'decrease') body.trigger('wc_fragment_refresh');
            }
            document.dispatchEvent(new CustomEvent('pcw:cart-changed'));
        } catch (error) { errorMessage = error.message; }
        finally {
            changing = false;
            widgets.forEach(other => setBusy(other, false));
            await syncAll();
            if (errorMessage && widget.isConnected) { widget.querySelector('.pcw-cart__status').textContent = errorMessage; widget.querySelector('.pcw-cart__retry').hidden = false; }
        }
    }
    function init(root = document) {
        const found = root.matches?.('.pcw-cart') ? [root] : root.querySelectorAll('.pcw-cart');
        found.forEach(widget => {
            if (widgets.has(widget)) return;
            widgets.add(widget);
            widget.pcwGeneration = 0;
            widget.querySelector('.pcw-cart__retry').addEventListener('click', () => sync(widget));
            widget.addEventListener('click', event => {
                const button = event.target.closest('button[data-operation]');
                if (button && widget.contains(button)) change(widget, button);
            });
            sync(widget);
        });
    }
    document.addEventListener('DOMContentLoaded', () => init());
    if (document.readyState !== 'loading') init();
    document.addEventListener('change', event => { if (event.target.closest('form.variations_form')) syncAll(); });
    window.addEventListener('focus', () => { if (!changing) syncAll(); });
    if (window.jQuery) {
        window.jQuery(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed updated_wc_div found_variation reset_data hide_variation', () => { if (!changing) syncAll(); });
        window.jQuery(window).on('elementor/frontend/init', () => {
            if (window.elementorFrontend?.hooks) window.elementorFrontend.hooks.addAction('frontend/element_ready/phehrest_ajax_smart_add_to_cart.default', scope => init(scope[0]));
        });
    }
})();
