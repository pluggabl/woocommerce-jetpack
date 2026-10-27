# Cart-abandonment version guard

Run the dependency-free regression suite from the repository root:

```sh
php -n tests/cart-abandonment-version-guard.php
```

The suite loads the production cart-abandonment class and exercises its actual
`plugins_loaded` and activation callbacks with small WordPress/module doubles.
Guard tests count entry into setup. Migration tests execute the production
setup method against database and `dbDelta()` doubles, so no SQL, WordPress
installation, external packages, or files written at runtime are required.

It covers an already-current schema with the module disabled and enabled,
repeated bootstraps, missing and older version markers, an unrelated global,
and per-site versus network option storage. Lifecycle tests cover fresh setup,
existing tables with missing/older markers, a future `1.0.1` version bump,
partial failure and retry, schema-inspection errors, failed marker writes,
unchanged marker writes, and refusing to downgrade a newer schema.

The migration double reports intended changes even when an operation fails,
matching the important distinction in core `dbDelta()`. Tests verify that the
marker is written only after a comparison-only pass finds no remaining changes,
and that a successful migration is skipped on later bootstraps. These tests
exercise migration control flow; they do not replace integration tests against
WordPress's real SQL/schema comparison on supported database servers.
