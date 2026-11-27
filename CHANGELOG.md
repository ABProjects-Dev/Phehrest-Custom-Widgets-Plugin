# Changelog

## Unreleased

- Center the cart quantity between edge-aligned icon buttons, with 20px icons and 10px icon margins by default.

## 0.2.0 — 2026-11-27

- Place decrease/remove on the physical left and increase on the physical right in both RTL and LTR layouts.

- Separate add-button and counter styling, add normal/hover controls, responsive minimum width and physical icon positioning; replace explicit width/height with content sizing. See widget documentation for saved-style compatibility.

- Prevent the standard WooCommerce view-cart link from appearing inside the smart cart widget, without changing other buttons.

## 0.1.0 — 2026-11-27

- Initial release of the independent opt-in Elementor/WooCommerce widget architecture and dashboard.
- Added the AJAX Smart Add to Cart widget. **The widget remains Experimental**; it is not a production-stability claim.
- Added scoped design defaults, per-state controls and authenticated product search.
- Added bounded operation replay protection and per-session MySQL advisory locking for this plugin's own requests; unsupported session handlers fail closed.
- Added isolated tests, minimum/current dependency smoke tests, non-invasive deactivation/uninstall checks and real-browser screenshot artifacts.
- See the widget documentation for compatibility, concurrency and infrastructure limitations.
