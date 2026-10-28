# Booster 8.4 Order Health technical guide

## Purpose

Order Health is a read-only operational monitor for active WooCommerce orders. It helps an authorized store manager see which orders may need attention, how long they have waited, why each order was flagged, and what safe review can happen next. It does not update statuses, issue refunds, send customer messages, or make AI-generated business decisions.

## Product-tier boundary

| Capability | Free 8.4 | Plus 8.4 | Elite 8.4 |
| --- | --- | --- | --- |
| Branded Order Health dashboard | Light | Light | Full |
| Failed, pending-payment, processing, and on-hold signals | Yes | Yes | Yes |
| Waiting time, aging buckets, and safe review guidance | Yes | Yes | Yes |
| Scan/display ceilings | 200 / 20 | 200 / 20 | 250 / 50 |
| Status, likely-cause, and aging filters | No | No | Yes |
| Custom-status configuration signals | No | No | Yes |
| Partial-refund attention signal | No | No | Yes |
| Morning Store Briefing v1 | No | No | Yes |
| `booster/order-health-summary` Ability | Not registered | Not registered | Yes |

Plus maintains the same minimum new-feature parity as Free. Elite is the only tier with the complete Order Health experience.

## Deterministic rules

| Current order state | Threshold | Reason code | Tier |
| --- | ---: | --- | --- |
| Failed | Immediate | `payment_failed` | All |
| Pending payment | 2 hours | `payment_pending` | All |
| Processing | 2 days from payment, or creation if unavailable | `fulfillment_delayed` | All |
| On hold | 1 day from creation | `workflow_incomplete` | All |
| Registered non-terminal custom status | 3 days from creation | `configuration_review` | Elite |
| Partially refunded and still active | Immediate | `partial_refund_open` | Elite |

Completed, cancelled, refunded, checkout-draft, and fully refunded orders are excluded in every tier. Classification uses the current WooCommerce order object on every request, so a transition to a terminal state removes the order without a Booster migration or cache reset.

## Aging and filters

Every tier shows fixed aging buckets: under 1 day, 1–3 days, 4–7 days, and over 7 days. Elite store managers can filter by current status, likely cause, and age bucket. Free and Plus ignore filter parameters and keep the simple light list.

Processing time starts from `date_paid` when available; every other rule uses stable order creation time for HPOS and legacy parity.

## Bounded WooCommerce queries

`WCJ_Order_Health_Service` uses `wc_get_orders()` and `WC_Order` methods. It does not read WordPress or HPOS order tables directly.

- All tiers query four fixed core status families: pending, on-hold, processing, and failed.
- Elite also queries registered custom non-terminal statuses.
- Free and Plus admit at most 200 candidate orders and display at most 20 attention rows.
- Elite admits at most 250 candidate orders and displays at most 50 attention rows.
- `has_more` and dashboard notices disclose truncation.

## HPOS, legacy storage, refunds, and transitions

The release suite creates, classifies, filters, refunds, transitions, and removes fixture orders in both HPOS and legacy storage. A fully refunded or terminal order is excluded in every tier. Elite additionally marks active partially refunded orders for review. No diagnostic metadata is written to orders.

## Elite-only read-only Ability

On WordPress 6.9+, Elite registers `booster/order-health-summary` in the `booster-operations` category.

- Permission: `manage_woocommerce`, checked in the permission callback and immediately before execution.
- Input: `{ "scope": "summary" }`; extra properties are rejected by schema.
- Output: tier, generation time, storage mode, bounded-query details, aggregate age/cause/reason counts, oldest age, and Morning Store Briefing aggregates.
- Privacy boundary: no order IDs, order numbers, customer fields, addresses, emails, raw Booster options, credentials, action arguments, or job record IDs.
- Free and Plus do not register this Ability and direct execution returns `booster_elite_required`.

## Elite-only Morning Store Briefing v1

Elite combines aggregate Order Health, active Booster compatibility-warning count, and Booster-owned overdue/failed background-job diagnostics. It returns guidance codes, not business decisions, and contains no customer details. Free and Plus do not compute or render the briefing.

## Shortcode permission hardening

In every tier, both `[wcj_get_option]` and `[wcj_wp_option]` require `manage_woocommerce` before reading any option. The existing Booster-owned `wcj` option-name restriction remains. Guests and low-privilege roles receive an empty value; authorized shop managers and administrators retain Booster-option access. Non-Booster options remain denied even to authorized roles.

## Validation commands

The committed WP-CLI checks are:

```text
wp eval-file tests/integration/codex-8-4-order-health.php
wp eval-file tests/integration/codex-8-4-option-shortcode-security.php
```

Release evidence must identify the committed SHA, exact ZIP SHA-256, WordPress/WooCommerce/PHP versions, storage mode, install path, upgrade path, browser checks, and log/console results for Free, Plus, and Elite.
