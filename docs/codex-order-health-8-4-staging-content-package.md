# Booster 8.4 staging-site content package

## Staging-only boundary

All Booster.io changes are committed to the Booster.io repository and deployed only to Booster staging for review. Nothing in this package authorizes or performs a live-site deployment. Rony reviews the staging-to-live website handoff separately.

## Pages to update

1. New Order Health feature page.
2. New Order Health documentation page.
3. Existing `/free-vs-elite/` comparison page.
4. Existing `/buy-booster/` comparison/pricing page.
5. Feature and documentation navigation/menu placements.

## Tier truth used on every page

| Capability | Free | Plus | Elite |
| --- | --- | --- | --- |
| Order Health experience | Light | Light | Full |
| Core delayed-order reasons and aging | Yes | Yes | Yes |
| Advanced filters | No | No | Yes |
| Custom-status and partial-refund signals | No | No | Yes |
| Morning Store Briefing | No | No | Yes |
| Read-only Order Health Ability | No | No | Yes |
| Scan/display ceilings | 200 / 20 | 200 / 20 | 250 / 50 |

Plus maintains Free-level minimum parity for new features. Do not market Plus as the full experience or as the preferred destination for new customers. Elite is the only tier with full new-feature capabilities.

## Page metadata

- Suggested feature slug: `/features/order-health/`
- Suggested documentation slug: `/docs/order-health/`
- SEO title: `WooCommerce Order Health | Find Delayed Orders with Booster`
- Meta description: `Find WooCommerce orders that may need attention, understand deterministic flags, and review a safe next step with Booster Order Health.`
- Release timing copy: `Planned for September 2026` until the coordinated date is approved.

## Recommended feature-page order

1. Hero: find delayed orders before they become customer problems.
2. Elite screenshot and three-answer value strip.
3. Free/Plus Light versus Elite Full comparison.
4. Morning Store Briefing privacy explanation, clearly Elite-only.
5. HPOS, legacy, refund, status-transition, and bounded-query trust section.
6. Merchant-control boundary.
7. FAQ and Elite CTA.

## Comparison-page JSON requirements

The structured data that drives `/free-vs-elite/` and `/buy-booster/` must add Order Health in the appropriate comparison group. Values must be extracted/rendered as:

- Free: `Order Health Light — core delayed-order reasons, aging, and safe review guidance`.
- Elite: `Order Health Full — advanced filters, custom-status and partial-refund signals, Morning Store Briefing, and read-only Ability`.
- Plus, where displayed: the same Light capability as Free.
- Never use a generic checkmark that implies feature parity without the Light/Full distinction.

## Screenshot inventory

| Asset | Placement | Alt text |
| --- | --- | --- |
| `docs/screenshots/booster-free-8-4-order-health.png` | Free/Light context | Booster Free Order Health Light dashboard showing common delayed-order reasons, aging, and safe review guidance. |
| `docs/screenshots/booster-plus-8-4-order-health.png` | Plus/Light context | Booster Plus Order Health Light dashboard showing common delayed-order reasons, aging, and safe review guidance. |
| `docs/screenshots/booster-elite-8-4-order-health.png` | Feature hero and Elite context | Booster Elite Order Health dashboard showing Morning Store Briefing, filters, explainable reasons, and safe merchant actions. |

Only use fixture orders on staging. Do not expose customer names, addresses, emails, phone numbers, order notes, payment references, credentials, or live store URLs.

## Technical truth checklist

- Say `may need attention`, not `is broken`.
- Say `likely cause`, not a guaranteed root cause.
- Say `bounded scan`, not a complete real-time audit when `has_more` is true.
- Say `read-only guidance`, not automation.
- Keep Elite-only capability labels visible.
- Do not claim that Booster changes statuses, issues refunds, contacts customers, or makes AI decisions.
- Keep September 2026 timing non-specific until approved.

## Staging QA checklist

- Validate feature, documentation, `/free-vs-elite/`, and `/buy-booster/` pages in signed-out desktop and mobile views.
- Verify rendered comparison values match the underlying JSON and clearly distinguish Light from Full.
- Verify the feature and documentation menus link to the new pages.
- Verify each tier context uses the matching screenshot and alt text.
- Confirm visible copy and screenshot data are privacy-safe.
- Check CTA destinations and ensure no staging link is accidentally introduced into plugin release copy.
- Record the Booster.io PR SHA and staging validation evidence before Rony receives the website handoff.
