# Phehrest Custom Widgets

Independent, opt-in Elementor widgets. The current release is `0.5.0`; the included widget remains **Experimental**. Validate it on a staging site before production use.

## Setup

Install on a staging site with PHP 8.0+, WordPress 6.4+, Elementor 3.18+ and WooCommerce 8.5+. Activate the dependencies and plugin, then enable the widget under **ابزارک‌های فهرست**. Widgets are disabled by default. Add it to a product template or select a product in Elementor. The dashboard presents collapsible widget cards in a carousel; configure each widget’s appearance in Elementor.

See the [widget guide](widgets/ajax-smart-add-to-cart/README.md) and [Persian guide](README.fa.md).

## Current cart product source (experimental)

With WooCommerce and a compatible Elementor Pro widget, choose **Query → Source → Current Cart Products** in Posts, Loop Grid or Loop Carousel. For loops use a compatible post loop item that renders products; Elementor's product-specific loop/query implementations may not expose this source. The integration targets Elementor Pro's group-query controls and `elementor/query/query_args`; control IDs and behavior vary by version. It has not been tested against Elementor Pro itself, Products widgets, archives or third-party widgets. Test the exact setup on staging. It is independent of whether our cart widget is enabled. No Query ID is needed.

Only published parent products from the current WooCommerce cart are queried; multiple variations/lines of one product appear once. Empty or unavailable carts return no products. Existing query conditions, exclusions, ordering and pagination can reduce or reorder results; avoid conflicting custom Query IDs or other query-modifying extensions. When WooCommerce has not initialized the cart (including some editor or AJAX contexts), the result is empty. Reload the page after changing the cart: the list does not refresh automatically.

**Privacy and caching:** exclude every page containing this source from shared/full-page caches, CDN and reverse proxy caches, and disable Elementor element caching for the widget and any containing cached output. Otherwise one visitor can see another visitor's cart products. This plugin does not configure those caches; verify separately with two independent browser sessions, including guests. A cache hit can bypass PHP completely, including the cart filter.

## Architecture and isolation

PSR-4 classes use a bundled loader; manifests expose opt-in modules. Widget assets are Elementor dependencies. Plugin options are `pcw_enabled_widgets` and the bounded `pcw_idempotency` ledger. The removed shared design feature no longer reads or applies `pcw_design_system`; any legacy value remains intact until uninstall. Uninstall removes only these plugin-owned options. No core, theme or other plugin files, WooCommerce products, orders or schema are changed by plugin installation/removal. On multisite, uninstall currently removes options only for the site where it runs; network-wide cleanup is not implemented.

For concurrency and idempotency prerequisites and limits, read the widget guide. In particular, custom WooCommerce session handlers and infrastructure without MySQL advisory locks fail closed rather than silently modifying a cart.

## Tests

```sh
find . -name '*.php' -not -path './node_modules/*' -print0 | xargs -0 -n1 php -l
php widgets/ajax-smart-add-to-cart/tests/CartServiceTest.php
php tests/cart-query.php && php tests/isolation.php && php tests/idempotency.php && php tests/mutation-guard.php
npm ci && npm test
# These must only run against a disposable WP-CLI installation:
PCW_TEST_ENV=1 wp eval-file tests/integration.php
```

GitHub Actions runs the isolated tests on PHP 8.0, 8.2 and 8.3; WooCommerce smoke and deactivation/uninstall checks on the minimum WordPress 6.4 / Elementor 3.18 / WooCommerce 8.5 combination and current versions; and Chromium checks against a disposable real WordPress/Elementor/WooCommerce site. Screenshot artifacts contain real frontend/editor states; the network error is intentionally simulated. This matrix is not a guarantee for Elementor Pro, every theme, third-party extension, custom session handler or distributed database. Visual review and wider compatibility testing are still necessary before production use.
