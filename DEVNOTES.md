# Agent-Ready Product Answers (Lite) - Development Notes

## Overview (90 seconds)

This module adds basic FAQ functionality to WooCommerce products with structured data support for search engines.

### Key Features
- **Single FAQ per product**: Question + short answer (≤160 chars) + long answer
- **FAQPage JSON-LD schema**: Automatic structured data output for search engines
- **Frontend rendering**: Accessible FAQ section with details/summary elements
- **Admin meta box**: Simple interface for managing product FAQ
- **Upsell integration**: Promotes Elite version with advanced features

### Data Flow
1. Admin creates FAQ via product meta box (`_wcj_ara_faq_lite` meta key)
2. Frontend renders FAQ section on product pages
3. JSON-LD schema automatically outputs when FAQ exists
4. Elite version detection suppresses Lite rendering when both active

### Technical Implementation
- **Base class**: Extends `WCJ_Module` following existing patterns
- **Meta storage**: Uses `_wcj_ara_faq_lite` with array structure
- **Frontend hooks**: `woocommerce_after_single_product_summary` priority 25
- **JSON-LD output**: `wp_head` action with schema.org FAQPage format
- **Coexistence**: Checks for Elite module and shows admin notice if active

### Settings
- Module enable/disable toggle
- FAQ section title customization
- Position and priority controls
- Upgrade promotion panel

### Security
- Nonce verification for meta box saves
- Capability checks (`edit_post`)
- Input sanitization with `sanitize_text_field()` and `sanitize_textarea_field()`
- Character limits enforced (160 chars for short answer)

### Performance
- Minimal CSS/JS assets loaded only on product pages
- No external API calls or heavy processing
- Efficient meta queries for FAQ existence checks

### Accessibility
- Semantic HTML with `<details>/<summary>` elements
- Keyboard navigation support
- `prefers-reduced-motion` media query respect
- ARIA-compliant structure

### Upgrade Path
Clear upsell messaging to Elite version with unlimited FAQs, Facts Table, Answer Chips, REST API, Compare Matrix, and Bundles Assist.
