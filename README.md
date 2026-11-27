# Phehrest Custom Widgets

Independent, opt-in Elementor widgets. The current release is `0.3.0`; the included widget remains **Experimental**. Validate it on a staging site before production use.

## Setup

Install on a staging site with PHP 8.0+, WordPress 6.4+, Elementor 3.18+ and WooCommerce 8.5+. Activate the dependencies and plugin, then enable the widget under **ابزارک‌های فهرست**. Widgets are disabled by default. Add it to a product template or select a product in Elementor. The dashboard's design defaults are scoped to plugin widgets and individual controls override them.

See the [widget guide](widgets/ajax-smart-add-to-cart/README.md) and [Persian guide](README.fa.md).

## Architecture and isolation

PSR-4 classes use a bundled loader; manifests expose opt-in modules. Widget assets are Elementor dependencies. Plugin options are `pcw_enabled_widgets`, `pcw_design_system` and the bounded `pcw_idempotency` ledger; uninstall removes only these. No core, theme or other plugin files, WooCommerce products, orders or schema are changed by plugin installation/removal. On multisite, uninstall currently removes options only for the site where it runs; network-wide cleanup is not implemented.

For concurrency and idempotency prerequisites and limits, read the widget guide. In particular, custom WooCommerce session handlers and infrastructure without MySQL advisory locks fail closed rather than silently modifying a cart.

## Tests

```sh
find . -name '*.php' -not -path './node_modules/*' -print0 | xargs -0 -n1 php -l
php widgets/ajax-smart-add-to-cart/tests/CartServiceTest.php
php tests/isolation.php && php tests/design-system.php && php tests/idempotency.php && php tests/mutation-guard.php
npm ci && npm test
# These must only run against a disposable WP-CLI installation:
PCW_TEST_ENV=1 wp eval-file tests/integration.php
```

GitHub Actions runs the isolated tests on PHP 8.0, 8.2 and 8.3; WooCommerce smoke and deactivation/uninstall checks on the minimum WordPress 6.4 / Elementor 3.18 / WooCommerce 8.5 combination and current versions; and Chromium checks against a disposable real WordPress/Elementor/WooCommerce site. Screenshot artifacts contain real frontend/editor states; the network error is intentionally simulated. This matrix is not a guarantee for Elementor Pro, every theme, third-party extension, custom session handler or distributed database. Visual review and wider compatibility testing are still necessary before production use.
