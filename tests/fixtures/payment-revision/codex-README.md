# Retained payment-revision and screenshot checks

## Offline payment regression

Run in a fresh PHP7.3+64-bit CLI without WordPress bootstrap:

    php -n tests/codex-payment-revision-metadata.php PLUGIN_ROOT WOO_ROOT WP_ROOT free

Use plus or elite for that edition. Roots are caller supplied. The binder
checks exact source hashes before loading anything. It uses WooCommerce11.1.0
WC_Data, WC_Meta_Data and WC_DateTime plus pinned WP_List_Util/wp_list_pluck.
It creates only in-memory class views: the full plugin class with its sole
terminal constructor invocation removed, and an inert order shell containing
the exact two public Woo date methods. No wp-load, DB, downloads or native cell.
The original908 27 strict after assertions are retained byte-for-byte.
Its storage/order-item shell is deliberately inert, not full Woo persistence,
concurrency, queue, gateway or native acceptance. Existing metadata duplicates,
IDs, nested values, type/order, dirty unrelated metadata and test deletion
queue are checked without globally cleaning a caller.

No absolute operator path, environment configuration, native helper, journal,
cookie, credential or private evidence belongs in this directory.

## Screenshot fixture safety

Only use a disposable isolated local/development WordPress instance with
CODEX_QA_ISOLATED=1 and WCJ_ORDER_HEALTH_SCREENSHOT_FIXTURES=1.
Run through WP-CLI eval-file with exactly one create or delete argument.
There is no default action. The entire saved ID list, ownership markers and
refund-parent/cascade closure must validate before any filter/write/deletion.
Legacy unmarked refunds require a selected, marked parent. New refunds are
marked directly. The marker is the existing _wcj_order_health_screenshot_fixture
value 8.4. Unknown/stale/mixed IDs fail closed; do not manually repoint the
option at merchant records. This is not an atomic isolation guarantee against
another process changing records after preflight.

Run php -n tests/codex-order-health-screenshot-guards.php for offline fake
positive/negative guard checks. No WP/Woo bootstrap or database is used.
