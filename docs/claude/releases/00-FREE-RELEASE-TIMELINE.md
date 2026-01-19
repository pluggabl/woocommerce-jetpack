# Booster Free Release Timeline (Since October 2025)

## Release Window Decision

**Date range**: October 25, 2025 - December 14, 2025 (present)

**How determined**: Used `git log --tags` with dates to find 7 releases since October 2025.

---

## Releases Summary

| Version | Date | Tag | Onboarding Impact (one-liner) |
|---------|------|-----|------------------------------|
| v7.3.2 | Oct 25 | `v7.3.2` | Foundation release, Onboarding class introduced |
| v7.4.0 | Oct 30 | `v7.4.0` | **MAJOR**: Guided onboarding modal with 4 goal tiles, 3-step progress bar |
| v7.5.0 | Nov 5 | `v7.5.0` | **MAJOR**: Blueprints feature - outcome-driven presets |
| v7.6.0 | Nov 17 | `v7.6.0` | Expanded onboarding map with 13 goals (B2B, INTL, Merchant packages) |
| v7.7.0 | Nov 26 | `v7.7.0` | Help text/tooltip framework via `enhance_settings_for_module()` |
| v7.8.0 | Dec 3 | `v7.8.0` | **MAJOR**: Quick Start presets on module settings pages |
| v7.9.0 | Dec 11 | `v7.9.0` | **MAJOR**: Upgrade Blocks (Lite vs Elite comparison panels) |

---

## Quick Reference: Where Changes Live

```
includes/admin/
├── class-booster-onboarding.php   # Main onboarding controller (v7.4.0+)
├── onboarding-map.php             # Goal definitions (v7.4.0+, expanded v7.6.0)
├── onboarding-blueprints.php      # Blueprint definitions (v7.5.0+)
├── wcj-quick-start-admin.php      # Quick Start UI renderer (v7.8.0+)
└── views/
    └── onboarding-modal.php       # Modal HTML template (v7.4.0+)

includes/
├── wcj-quick-start-presets.php    # Preset configurations (v7.8.0+)
├── class-wcj-upgrade-blocks.php   # Lite vs Elite blocks (v7.9.0+)
└── core/
    └── class-wcj-admin.php        # Settings enhancement (v7.7.0+)

assets/
├── css/admin/booster-onboarding.css  # Onboarding styles
└── js/admin/booster-onboarding.js    # Onboarding JavaScript
```

---

## Verification Commands

```bash
# List tags with dates
git log --tags --simplify-by-decoration --pretty="format:%ai %d %s" --since="2024-10-01"

# See what changed between versions
git diff v7.4.0..v7.5.0 --stat

# View commits for a specific release
git log v7.4.0..v7.5.0 --oneline
```
