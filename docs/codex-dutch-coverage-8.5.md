# Dutch coverage in Booster 8.5 (Free)

This is bounded Dutch coverage, not a complete translation of every module.

| Current first-party gettext keys | 5027 |
| --- | ---: |
| Individually drafted/reused and independently AI-reviewed Dutch keys | 1730 |
| Technically valid existing bundled translations retained, not linguistically re-reviewed | 522 |
| Total translated keys | 2252 |
| Selected keys held or deliberately falling back to English | 36 |
| Legacy entries with broken placeholders held in English | 2 |
| Other current keys outside this bounded production scope | 2737 |

The selected coverage spans core navigation/setup, the branded-document guide, PDF settings, checkout, shipping, payment, currency and other existing merchant controls. A translated shared label does not establish complete coverage of every screen using it. Edition-specific source keys and entitlements remain distinct.

Existing Dutch reuse comes from the previously bundled GlotPress catalogue (revision 2017-09-14, generator GlotPress/2.4.0-alpha). Its valid current entries outside the selected scope were retained with provenance in the PO. Obsolete keys absent from current first-party extraction were not carried into the active catalogue. The two old translations that lost `%s` (`Sales in last %s days` and `%s ago`) fall back to English rather than ship invalid formatting.

Each new or changed selected translation received a separate contextual AI review. No native-speaker or human linguistic approval is claimed. Source promises that could not be supported were held, not translated as fact. In particular, the legacy Elite cart-recovery recipe's 15%/20% subject promises differ from its 10% configured values; these are a separate pre-existing commercial-copy follow-up, not corrected by localization.

Community language packs and custom overrides retain priority. Site and administrator locale choices remain WordPress-controlled. Merchant-authored text, saved templates, order history, option keys, shortcodes and identifiers are not translated or migrated. The stock PDF layout may retain English labels. No runtime translation API, customer-data transfer or new telemetry is introduced.

## Reproduction and boundaries

Generate the reference POT with the official WP-CLI extractor from the exact committed first-party source, excluding vendor libraries, root tests and tools/i18n. Validate this PO against that POT with `tools/i18n/catalog.py`; compile independently with GNU `msgfmt --check --check-format`. The two compilers' complete gettext lookups must agree, including contexts and both Dutch plural branches.

Catalog generation and deterministic checks do not establish installed-plugin, email, browser or upgrade acceptance. Those are separate exact-package QA gates. Do not infer tax/legal compliance or full Dutch coverage from this catalogue.
