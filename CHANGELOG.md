# Changelog

## Unreleased

## 0.5.0 — 2026-11-27

- Add an experimental current-cart product query source for compatible Elementor Pro Posts and Loop widgets. Review cache and compatibility warnings in the guides.

## 0.4.0 — 2026-11-27

- Add per-widget error visibility, remove the shared design feature, and show collapsible widget cards in a dashboard carousel. Legacy shared-design values remain stored but no longer apply.

- Quietly handle confirmed cart-lock contention without optimistic quantity changes; preserve other errors and skip obsolete queued reads.

## 0.3.0 — 2026-11-27

- Center the quantity between edge-aligned icon buttons with 20px icons and 10px button padding; reserve space to prevent overlapping the quantity.
- Remove the add icon, widget animations, shadows and one/multiple-specific counter styling; add shared normal/hover icon-button and quantity controls. Review saved settings after updating.

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
