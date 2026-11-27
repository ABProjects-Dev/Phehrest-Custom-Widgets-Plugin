(() => {
    'use strict';
    if (typeof pcwProductSearch === 'undefined') return;
    let timer = null;
    let generation = 0;
    document.addEventListener('input', event => {
        const field = event.target.closest('.pcw-product-search__input');
        if (!field) return;
        const root = field.closest('.pcw-product-search');
        const results = root.querySelector('.pcw-product-search__results');
        const term = field.value.trim();
        clearTimeout(timer); const current = ++generation;
        results.replaceChildren();
        if (term.length < 2 || term.length > 100) return;
        timer = setTimeout(async () => {
            try {
                const body = new URLSearchParams({ action: 'pcw_search_products', nonce: pcwProductSearch.nonce, term });
                const response = await fetch(pcwProductSearch.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, body });
                const json = await response.json();
                if (current !== generation || !root.isConnected) return;
                if (!response.ok || !json.success || !Array.isArray(json.data)) throw new Error();
                if (!json.data.length) { results.textContent = pcwProductSearch.empty; return; }
                for (const product of json.data) {
                    const button = document.createElement('button');
                    button.type = 'button'; button.textContent = `${product.name} (#${product.id})`;
                    button.addEventListener('click', () => {
                        const panel = root.closest('#elementor-panel') || document;
                        const input = panel.querySelector('.elementor-control-product_id input[data-setting="product_id"]');
                        if (!input) return;
                        const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
                        setter.call(input, String(product.id));
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        field.value = product.name; results.replaceChildren();
                    });
                    results.append(button);
                }
            } catch (_) { if (current === generation && root.isConnected) results.textContent = pcwProductSearch.error; }
        }, 250);
    });
})();
