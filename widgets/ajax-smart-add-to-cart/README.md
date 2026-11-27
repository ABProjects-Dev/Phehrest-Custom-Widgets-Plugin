# AJAX Smart Add to Cart

Experimental Elementor widget for one WooCommerce cart line. Zero shows add; one shows remove, quantity and plus; higher quantities show minus, quantity and plus. WooCommerce remains the source of truth for the cart, prices and totals. The standard view-cart link is suppressed inside this widget only; other WooCommerce buttons retain their normal behavior.

Enable the widget in the plugin dashboard, add it to Elementor and choose the current product or an explicit product, by ID or editor search. Variable products require exactly one matching standard WooCommerce variation form on the page with all attributes selected. External/grouped products, ambiguous matching cart lines, fractional quantities and non-unit steps are not supported.

State is fetched without caching; mutations require a WP nonce, a session-bound token and a random operation ID. The server validates product identity, cart key, quantities and WooCommerce validation filters. A bounded plugin-owned ledger records hashed operation identities and minimal results for 15 minutes. A MySQL advisory lock serializes this plugin's requests for the same session before WooCommerce loads its cart; it does not modify core tables or schema. An incomplete operation ID fails closed: inspect the cart before trying again.

**Limits:** the standard WooCommerce session handler and MySQL advisory locks are required; unsupported session handlers or unavailable locks cause widget mutations to fail closed. Other plugins do not participate in this lock. Independent database nodes, arbitrary third-party concurrent cart mutations and all infrastructure failures cannot be guaranteed. The default WooCommerce fragment events are used where available; theme-specific headers and mini carts may require additional integration. Do not publicly cache AJAX responses.

The CI runs isolated tests, disposable-site WooCommerce/non-invasive tests, and real Chromium Elementor tests with screenshot artifacts. See the root README for the exact matrix. Elementor Pro, custom themes, custom product extensions, exhaustive accessibility and multi-node environments remain untested; this is not a production-stability claim.

## Button and counter styling

Choose the add icon's physical left/right position in Content, independently of page direction. Style has separate add-button and counter sections, with normal and hover button settings. The button sizes to its content and padding by default; a responsive minimum width is available. Use Elementor's standard Advanced margin for outer spacing. The counter occupies the button's dimensions, with the quantity centered between edge-aligned buttons; counter icons default to 20px with 10px of space around each icon inside its button. The icon size remains configurable;  for very short text or large counter icons/padding, set a suitable minimum width and padding so the controls fit.

Existing explicit width and height settings no longer apply. Existing shared typography, radius, icon size, gap, padding and shadow now apply to the add button only; set counter styling separately. Other existing color settings are retained where possible. Old data is not deleted. Regenerate Elementor CSS and clear site caches after updating if old styles persist.
