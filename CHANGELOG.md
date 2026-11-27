# Changelog

## Unreleased

- Prevent the standard WooCommerce view-cart link from appearing inside the smart cart widget, without changing other buttons.

## 0.1.0 — 2026-11-27

- Initial release of the independent opt-in Elementor/WooCommerce widget architecture and dashboard.
- Added the AJAX Smart Add to Cart widget. **The widget remains Experimental**; it is not a production-stability claim.
- Added scoped design defaults, per-state controls and authenticated product search.
- Added bounded operation replay protection and per-session MySQL advisory locking for this plugin's own requests; unsupported session handlers fail closed.
- Added isolated tests, minimum/current dependency smoke tests, non-invasive deactivation/uninstall checks and real-browser screenshot artifacts.
- See the widget documentation for compatibility, concurrency and infrastructure limitations.
