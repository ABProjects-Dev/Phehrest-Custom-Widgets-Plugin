# AJAX Smart Add to Cart

Experimental widget for a single WooCommerce cart line. Zero shows an add button; one shows remove, count and plus; higher quantities show minus, count and plus. It uses official WooCommerce cart APIs, not a separate cart.

Enable it in the plugin dashboard, add it to Elementor, and choose the current product or an explicit product ID. Variable products require exactly one matching standard WooCommerce variation form on the page with all attributes selected. External/grouped products are not supported. Multiple matching cart lines are rejected rather than merged. Fractional quantities and non-unit steps are not supported.

State is fetched without caching; mutations require a WP nonce and a session-bound token. The server validates product, cart key, quantities and WooCommerce validation filters. Standard fragment events are emitted when available, but theme-specific headers and mini carts are not guaranteed to refresh. Never cache the AJAX responses publicly.

Client-side operations are serialized per page. Cross-tab/server locking and server-side idempotency are **not implemented**; after network errors check the actual cart before retrying. Required integration, security, visual, Elementor Pro and non-invasive testing is incomplete. This is not production-ready. See the root README for test commands and the Persian guide for details.
