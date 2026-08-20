# Booster 8.4 staging-site content package

## Page metadata

- Suggested slug: `/booster-8-4-order-health/`
- SEO title: `Booster 8.4 Order Health | Find Delayed WooCommerce Orders`
- Meta description: `Find WooCommerce orders that may need attention, understand every deterministic flag, and review a safe next step with Booster 8.4 Order Health.`
- Release timing copy: `Planned for September 2026` until the final release date is approved.

## Recommended page order

1. Hero: Find delayed orders before they become customer problems.
2. Product screenshot with the approved alt text.
3. Three-answer strip: what needs attention, why it was flagged, safe next review.
4. Morning Store Briefing privacy explanation.
5. HPOS, legacy, refund, status-transition, and bounded-query trust section.
6. Merchant-control boundary.
7. FAQ and release CTA.

## Reusable release announcement

Booster 8.4 introduces Order Health: a read-only dashboard that helps store teams find delayed orders, understand deterministic payment and fulfillment signals, and choose a safe merchant-controlled review. Morning Store Briefing v1 adds aggregate compatibility and background-job context without customer details. The release also strengthens the permission boundary around Booster option-reading shortcodes.

## Screenshot inventory

| Asset | Placement | Alt text |
| --- | --- | --- |
| `docs/screenshots/booster-free-8-4-order-health.png` | Free product/release page | Booster Free Order Health dashboard showing a privacy-safe morning briefing, aging buckets, filters, explainable order reasons, and safe merchant actions. |
| `docs/screenshots/booster-elite-8-4-order-health.png` | Elite product/release page | Booster Elite Order Health dashboard showing a privacy-safe morning briefing, aging buckets, filters, explainable order reasons, and safe merchant actions. |

Only use fixture orders or a staging dataset in public screenshots. Do not expose customer names, addresses, emails, phone numbers, real order notes, payment references, or live store URLs.

## Technical truth checklist

- Say `may need attention`, not `is broken`.
- Say `likely cause`, not a guaranteed root cause.
- Say `bounded scan`, not a complete real-time audit when `has_more` is true.
- Say `read-only guidance`, not automation.
- Do not claim that Booster changes statuses, issues refunds, contacts customers, or makes AI decisions.
- Keep the September 2026 timing non-specific until the coordinated release date is approved.

## Staging QA checklist

- Verify Free and Elite pages use the matching screenshot and tier name.
- Confirm screenshot alt text and visible copy contain no customer data.
- Test desktop and mobile line wrapping for the dashboard screenshot and value blocks.
- Check CTA destinations in a signed-out browser.
- Verify documentation links point to the 8.4 technical guide.
- Confirm Plus copy is not published until Plus 8.3 is complete and the Plus 8.4 implementation is validated.

