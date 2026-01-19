# Claude Code Guide - Booster for WooCommerce

This is the **FREE** version of Booster for WooCommerce (plugin slug: `woocommerce-jetpack`).

## Quick Start

- **Main plugin file:** `woocommerce-jetpack.php`
- **Modules:** 100+ modules in `includes/class-wcj-*.php`
- **Settings:** `includes/settings/wcj-settings-{module-id}.php`
- **Option prefix:** `wcj_` (e.g., `wcj_call_for_price_enabled`)

## Documentation

See `/docs/claude/` for detailed documentation:

| File | Contents |
|------|----------|
| [00-REPO-OVERVIEW.md](docs/claude/00-REPO-OVERVIEW.md) | Repo identity, folder structure, key paths |
| [01-BOOT-SEQUENCE.md](docs/claude/01-BOOT-SEQUENCE.md) | How the plugin loads, Mermaid diagram |
| [02-MODULES-SYSTEM.md](docs/claude/02-MODULES-SYSTEM.md) | Module architecture, how to add modules |
| [03-SETTINGS-SYSTEM.md](docs/claude/03-SETTINGS-SYSTEM.md) | Settings UI, storage, validation |
| [04-GATING-LICENSING.md](docs/claude/04-GATING-LICENSING.md) | Free vs Elite gating, upgrade blocks |
| [05-DEV-RECIPES.md](docs/claude/05-DEV-RECIPES.md) | How-to guides for common tasks |
| [06-SEARCH-CHEATSHEET.md](docs/claude/06-SEARCH-CHEATSHEET.md) | Quick search terms and file locations |

### Release & Onboarding Documentation (December 2025)

See `/docs/claude/releases/` for onboarding-focused analysis:

| File | Contents |
|------|----------|
| [00-FREE-RELEASE-TIMELINE.md](docs/claude/releases/00-FREE-RELEASE-TIMELINE.md) | 7 releases since Oct 2025 with dates, tags, impact summary |
| [01-FREE-RELEASE-BY-RELEASE.md](docs/claude/releases/01-FREE-RELEASE-BY-RELEASE.md) | Per-release onboarding changes, files, functions |
| [02-FREE-ONBOARDING-JOURNEY-NOW.md](docs/claude/releases/02-FREE-ONBOARDING-JOURNEY-NOW.md) | Step-by-step user journey, what improved |
| [03-FREE-ONBOARDING-BUILDING-BLOCKS.md](docs/claude/releases/03-FREE-ONBOARDING-BUILDING-BLOCKS.md) | Reusable systems inventory, how to extend |
| [04-FREE-NEXT-ONBOARDING-PLAN.md](docs/claude/releases/04-FREE-NEXT-ONBOARDING-PLAN.md) | Prioritized roadmap, acceptance criteria |
| [05-FREE-SEARCH-CHEATSHEET.md](docs/claude/releases/05-FREE-SEARCH-CHEATSHEET.md) | Onboarding-specific search terms and patterns |

## Key Patterns

1. **Module enabled check:** `wcj_is_module_enabled( 'module_id' )`
2. **Option retrieval:** `wcj_get_option( 'wcj_option_name', 'default' )`
3. **Premium gating:** `apply_filters( 'booster_option', $free, $premium )`
4. **Settings gating:** `apply_filters( 'booster_message', '', 'readonly' )`

## Entry Points

- Boot: `woocommerce-jetpack.php` -> `includes/core/wcj-loader.php`
- Modules: `includes/core/wcj-modules.php`
- Admin: `includes/core/class-wcj-admin.php`
- Categories: `includes/admin/wcj-modules-cats.php`
