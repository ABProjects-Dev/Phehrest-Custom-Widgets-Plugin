# Phehrest Custom Widgets

An independent, opt-in Elementor widget plugin. **Experimental development build; not approved for production.** No prior plugin version or release existed in this repository. `0.1.0-dev` is a development identifier, not a release or compatibility certification.

## Requirements and setup

Provisional minimums: PHP 8.0, WordPress 6.4, Elementor 3.18, and WooCommerce 8.5. These are configuration gates, **not a tested compatibility matrix**. Install the plugin directory on a disposable staging site, activate Elementor and WooCommerce, activate this plugin, then enable the widget under **ابزارک‌های فهرست**. Widgets are disabled by default. Add the widget to a product template or enter a product ID in its Content controls.

See [the widget documentation](widgets/ajax-smart-add-to-cart/README.md) and [Persian guide](README.fa.md).

## Architecture and data

PSR-4 classes are loaded by a small bundled loader; widgets are discovered by `manifest.json` and expose a module class. Only enabled, dependency-compatible modules register their hooks. Widget assets are Elementor dependencies, not global frontend styles. The admin setting lives in `pcw_enabled_widgets`; uninstall removes that option only. No core, theme, other plugin files, WooCommerce data or schema are modified. On multisite, uninstall currently removes the setting only for the site on which uninstall runs; network-wide cleanup is not implemented.

The widget uses WooCommerce's cart, session and totals APIs. It never accepts client prices. State and session-bound CSRF tokens are fetched uncached; mutations additionally require a WordPress nonce. Guest access is limited to the current WooCommerce session. Do not cache `admin-ajax.php` responses.

## Tests

```sh
find . -name '*.php' -not -path './node_modules/*' -print0 | xargs -0 -n1 php -l
php widgets/ajax-smart-add-to-cart/tests/CartServiceTest.php
php tests/isolation.php
npm ci && npm test
# Real WooCommerce smoke test ONLY on a disposable WP-CLI site with dependencies activated:
PCW_TEST_ENV=1 wp eval-file tests/integration.php
```

The fake-cart and DOM tests are not integration or browser tests. The workflow provisions a disposable WooCommerce/Elementor site for a small smoke test; it is not a full compatibility or non-invasive test suite. Full multi-version, variable product, mini-cart/theme, deactivation/uninstall, visual and concurrency testing remains required before a PR or release.
