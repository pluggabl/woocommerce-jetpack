# Booster Free 8.4 product-page copy

## Hero

**Find delayed orders before they become customer problems**

Order Health gives your team one explainable view of orders that may need attention—without changing an order behind your back.

Primary CTA: **Explore Order Health**  
Secondary CTA: **See how flags work**

## Value blocks

### Know where to start

See delayed payment, fulfillment, configuration, and incomplete-workflow signals together instead of opening every order one by one.

### Understand every flag

Each result shows its current status, waiting time, a plain-language reason, and a safe review step. There is no opaque score and no AI-generated business decision.

### Keep performance predictable

Bounded WooCommerce queries prioritize the oldest active orders and tell you when more records remain. The same WooCommerce APIs work with HPOS and legacy order storage.

### Start the day with shared context

Morning Store Briefing v1 combines aggregate Order Health, Booster compatibility warnings, and Booster background-job status—without exposing customer details.

### Keep the merchant in control

Order Health is read-only. It does not change order status, issue refunds, contact customers, or automate a decision.

## Feature list

- WooCommerce > Order Health dashboard
- Status, likely-cause, and aging-bucket filters
- Deterministic reason codes and merchant-safe review guidance
- Partial-refund, full-refund, and current-status correctness
- HPOS and legacy-storage parity
- Fixed query and display ceilings for larger stores
- Privacy-safe Morning Store Briefing v1
- `booster/order-health-summary` read-only Ability for authorized store managers
- Stronger permission boundary for Booster option-reading shortcodes

## Screenshot

![Booster Free 8.4 Order Health dashboard](screenshots/booster-free-8-4-order-health.png)

Suggested alt text: `Booster Free Order Health dashboard showing a privacy-safe morning briefing, aging buckets, filters, explainable order reasons, and safe merchant actions.`

## FAQ

**Does Order Health change orders?**  
No. It is a read-only monitor and guidance surface.

**Does it send store or customer data to an AI service?**  
No. Booster 8.4 connects no AI provider. The public operational summary is aggregate-only.

**Will it work after enabling HPOS?**  
The maintained Order Health path uses WooCommerce order queries and CRUD objects and is tested with HPOS and legacy storage.

**Does the count always represent every active order on a very large store?**  
The scan is deliberately capped. The dashboard and Ability disclose the ceiling and whether more orders remain.

