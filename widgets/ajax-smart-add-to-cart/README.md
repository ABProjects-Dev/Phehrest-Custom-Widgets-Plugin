# AJAX Smart Add to Cart

Experimental Elementor widget for one WooCommerce cart line. Zero shows add; one shows remove, quantity and plus; higher quantities show minus, quantity and plus. WooCommerce remains the source of truth for the cart, prices and totals. The standard view-cart link is suppressed inside this widget only; other WooCommerce buttons retain their normal behavior.

Enable the widget in the plugin dashboard, add it to Elementor and choose the current product or an explicit product, by ID or editor search. Variable products require exactly one matching standard WooCommerce variation form on the page with all attributes selected. External/grouped products, ambiguous matching cart lines, fractional quantities and non-unit steps are not supported.

The Content setting “Show errors” is enabled by default for existing and new widgets. When disabled it suppresses this widget’s errors and notices for unavailable, invalid, unsupported or unselected products; loading and retry controls can still appear. It does not change validation or messages outside this widget. As before, temporary lock-contention messages remain silent even when errors are enabled.

State is fetched without caching; mutations require a WP nonce, a session-bound token and a random operation ID. The server validates product identity, cart key, quantities and WooCommerce validation filters. A bounded plugin-owned ledger records hashed operation identities and minimal results for 15 minutes. A MySQL advisory lock serializes this plugin's requests for the same session before WooCommerce loads its cart; it does not modify core tables or schema. An incomplete operation ID fails closed: inspect the cart before trying again.

**Limits:** the standard WooCommerce session handler and MySQL advisory locks are required; unsupported session handlers or unavailable locks cause widget mutations to fail closed. Other plugins do not participate in this lock. Independent database nodes, arbitrary third-party concurrent cart mutations and all infrastructure failures cannot be guaranteed. The default WooCommerce fragment events are used where available; theme-specific headers and mini carts may require additional integration. Do not publicly cache AJAX responses.

Confirmed session-lock contention is silent: the rejected mutation is not automatically retried or applied optimistically, and the widget refreshes from the server. If the refresh also stays busy, the last confirmed display is retained, controls are disabled and a retry button is shown; without an earlier confirmed state no quantity is guessed. Network, unavailable database locks and other errors are visible by default.

Initial rendering waits for an uncached WooCommerce session/cart state request rather than guessing the count. Reads in the same page are serialized, so multiple widgets, slow servers or networks and contention retries can add latency. Obsolete queued reads are skipped; cross-tab and infrastructure delays can still occur.

The CI runs isolated tests, disposable-site WooCommerce/non-invasive tests, and real Chromium Elementor tests with screenshot artifacts. See the root README for the exact matrix. Elementor Pro, custom themes, custom product extensions, exhaustive accessibility and multi-node environments remain untested; this is not a production-stability claim.

## Button and counter styling

The add button is text-only, with normal and hover typography, colors and border settings, padding and responsive minimum width. Use Elementor Advanced for the widget's outer margin. The single counter section offers common container settings, plus shared icon-button padding, size, borders and normal/hover colors; individual plus, minus and remove icons and their normal colors remain configurable. The quantity has its own typography, color, background, padding and radius. Icons default to 20px and button padding to 10px.

Decrease/remove is on the physical left, increase on the right, and the quantity is centered. The add button and counter share dimensions and reserve enough space for counter content, so short add text may result in a wider button. Extremely narrow containers or unusually large icon, text or padding settings require sufficient layout space.

Compatibility: saved add-icon, animation, shadow, and one/multiple-specific counter settings no longer apply. The removed shared design settings no longer affect widgets; old values remain stored until uninstall. Earlier explicit width/height also no longer apply; review the shared counter settings after updating. Old data is not deleted. Regenerate Elementor CSS and clear caches after updating to remove stale generated styles.
