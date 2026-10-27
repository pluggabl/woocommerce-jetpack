# Cart-abandonment version guard

Run the dependency-free regression suite from the repository root:

```sh
php -n tests/cart-abandonment-version-guard.php
```

The suite loads the production cart-abandonment class, registers its actual
`plugins_loaded` callback, and exercises that callback with small WordPress and
module doubles. A subclass counts entry into table setup, so no SQL, WordPress
installation, external packages, or files written at runtime are required.

It covers an already-current schema with the module disabled and enabled,
repeated bootstraps, missing and older version markers, an unrelated global,
and per-site versus network option storage. The setup-call counter verifies the
guard's decision; it does not test table creation or schema migrations.
