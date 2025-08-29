# Sales Notifications Lite - Developer Notes

## Overview (90 seconds)

Sales Notifications Lite is a simple social proof module that displays recent real purchase notifications on shop and single product pages. It follows the established Lite module patterns in Booster for WooCommerce.

## Data Flow

1. **Order Query**: Fetches orders with `processing` or `completed` status from last 72 hours using `wc_get_orders()`
2. **Item Building**: Extracts customer first initial, city, country, and product name with privacy-safe fallbacks
3. **Caching**: Stores built items in 5-minute site transient keyed by locale and page type
4. **Template**: Fixed format `"{first_initial} from {city}, {country} bought {product} — {time_ago}"`
5. **Display**: JavaScript rotates through items with fixed timing (5s delay, 5s display, 12s gap, max 5)

## Guards & Limitations

- **Page Restriction**: Only shop and single product pages (excludes cart, checkout, my-account, order-received)
- **User Restriction**: Hidden from `manage_woocommerce` users unless test mode enabled
- **Daily Cap**: 20 notifications per calendar day, reset at local midnight using `wp_timezone()`
- **Elite Coexistence**: Auto-disables when Elite Sales Notifications module is active
- **Empty Store**: Shows nothing if no eligible orders exist (no fake/seed data)

## Filters & Extensibility

- `wcj_sales_notifications_lite_items` - Filter notification items array before caching
- `wcj_sales_notifications_lite_rendering` - Action fired when notifications are rendered
- Future extension points preserved for Elite upgrade path

## How to Extend

This Lite version is intentionally limited. For advanced features (multiple positions, custom templates, colors, sounds, analytics, seed mode), users should upgrade to Booster Elite which provides the full Sales Notifications module with complete customization options.

## Performance

- 5-minute transient caching reduces database queries
- Vanilla JavaScript (no jQuery dependency)
- Combined JS+CSS under 10KB
- Accessibility compliant with ARIA live regions and keyboard support
