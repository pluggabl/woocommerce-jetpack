# Booster 8.4 Order Health technical guide

## Purpose

Order Health is a read-only operational monitor for active WooCommerce orders. It helps an authorized store manager answer:

- Which orders may need attention?
- How long have they been waiting?
- Why was each order flagged?
- Is the likely cause payment, fulfillment, configuration, or an incomplete workflow?
- What review can the merchant safely perform next?

It does not update order statuses, issue refunds, send customer messages, or make AI-generated business decisions.

## Deterministic rules

| Current order state | Threshold | Reason code | Likely cause | Safe merchant review |
| --- | ---: | --- | --- | --- |
| Failed | Immediate | `payment_failed` | Payment | Verify the payment result and order notes before asking for a retry. |
| Pending payment | 2 hours | `payment_pending` | Payment | Confirm the gateway result and order notes before changing status. |
| Processing | 2 days from payment, or creation when payment time is unavailable | `fulfillment_delayed` | Fulfillment | Review fulfillment progress, shipment details, and order notes. |
| On hold | 1 day from creation | `workflow_incomplete` | Incomplete workflow | Review the hold reason before choosing a merchant-controlled next step. |
| Registered non-terminal custom status | 3 days from creation | `configuration_review` | Configuration | Review the workflow or integration that owns the status. |
| Partially refunded and still active | Immediate | `partial_refund_open` | Existing cause, or incomplete workflow | Confirm the remaining fulfillment and refund intent. |

Completed, cancelled, refunded, checkout-draft, and fully refunded orders are excluded. Classification is recalculated from the current WooCommerce order object on every request, so a transition into a terminal state removes the order from the monitor without a Booster migration or cache reset.

## Aging buckets and filters

The dashboard exposes fixed buckets: under 1 day, 1–3 days, 4–7 days, and over 7 days. Store managers can filter rows by current status, likely cause, and age bucket. Processing time starts from `date_paid` when available; every other rule uses the stable order creation time so HPOS and legacy storage produce the same result.

## Bounded order queries

`WCJ_Order_Health_Service` uses only `wc_get_orders()` and `WC_Order` methods. It never reads `wp_posts`, `wp_postmeta`, HPOS tables, or customer tables directly.

- Four fixed core status families are queried: pending, on-hold, processing, and failed.
- Registered custom non-terminal statuses are queried as one additional family.
- Each family reads no more than 51 orders and retains at most the 50 oldest.
- At most 250 order objects can enter one scan.
- The dashboard renders at most 50 matching rows.
- `has_more` and the dashboard notices disclose when a family or result set was truncated.

These bounds are fixed public constants and are included in the Ability result so consumers do not mistake a capped scan for an unbounded store total.

## HPOS and legacy parity

All reads use WooCommerce order queries and CRUD objects. The same integration suite creates, classifies, filters, refunds, transitions, and removes fixture orders once with HPOS enabled and once with legacy storage enabled. No storage-specific SQL is used.

## Refund and status-transition behavior

- A partially refunded order remains visible when its workflow is active and receives `partial_refund_open` in addition to any status-based reason.
- A fully refunded order is excluded even if an unusual integration leaves its stored status active.
- A completed, cancelled, or refunded current status is always excluded.
- No diagnostic metadata is written to orders. Waiting time comes from existing WooCommerce timestamps.

## Read-only Ability

WordPress 6.9+ registers `booster/order-health-summary` in the `booster-operations` category.

- Permission: `manage_woocommerce`, checked both by the Ability permission callback and immediately before execution.
- Input: `{ "scope": "summary" }`; extra properties are rejected by the schema.
- Output: tier, generation time, storage mode, bounded-query details, age/cause/reason counts, oldest age, and Morning Store Briefing aggregates.
- Privacy boundary: no order IDs, order numbers, customer fields, addresses, emails, raw Booster options, credentials, action arguments, or job record IDs.
- Annotations: read-only, non-destructive, and idempotent.

## Morning Store Briefing v1

The dashboard and Order Health Ability combine three aggregate views:

1. Order Health attention count and truncation state.
2. Count of active Booster module compatibility warnings.
3. Count and diagnostic codes for overdue or failed Booster-owned background jobs.

The briefing returns guidance codes such as `review-order-health`; it does not choose or perform a business action.

## Shortcode permission hardening

Both `[wcj_get_option]` and `[wcj_wp_option]` now require `manage_woocommerce` before reading any option. The existing Booster-owned option-name restriction remains in place. Guests, customers, subscribers, contributors, authors, and editors without store-management permission receive an empty value; shop managers and administrators retain authorized Booster-option access.

## Validation commands

The committed WP-CLI checks are:

```text
wp eval-file tests/integration/codex-8-4-order-health.php
wp eval-file tests/integration/codex-8-4-option-shortcode-security.php
```

The local release matrix covers PHP 7.2, 8.2, and 8.4 syntax; WordPress 7.1 RC1; WooCommerce 11.0.0; HPOS; legacy storage; Free; and Elite.

