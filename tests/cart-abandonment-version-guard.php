<?php
/**
 * Standalone regression tests for the cart-abandonment schema-version lifecycle.
 *
 * Run: php -n tests/cart-abandonment-version-guard.php
 * No WordPress installation, database, or external test dependencies are needed.
 */

error_reporting( E_ALL );
set_error_handler(
	function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

define( 'ABSPATH', __DIR__ . '/fixtures/wordpress/' );
define( 'WCJ_FREE_PLUGIN_FILE', dirname( __DIR__ ) . '/woocommerce-jetpack.php' );

$GLOBALS['guard_test_enabled'] = false;
$GLOBALS['guard_test_actions'] = array();

/** Minimal WordPress/module doubles; the production class is loaded below. */
class WCJ_Module {
	public $id;
	public $short_desc;
	public $desc;
	public $desc_pro;
	public $link_slug;

	public function __construct() {}
	public function add_tools( $tools ) {}
	public function is_enabled() {
		return $GLOBALS['guard_test_enabled'];
	}
}

function __( $text, $domain ) {
	return $text;
}

function register_activation_hook( $file, $callback ) {
	$GLOBALS['guard_test_activation'] = $callback;
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['guard_test_actions'][ $hook ][ $priority ][] = $callback;
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	add_action( $hook, $callback, $priority, $accepted_args );
}

function wp_next_scheduled( $hook ) {
	return 1;
}

function wcj_is_frontend() {
	return false;
}

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['guard_test_options'] )
		? $GLOBALS['guard_test_options'][ $name ] : $default;
}

function get_site_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['guard_test_network_options'] )
		? $GLOBALS['guard_test_network_options'][ $name ] : $default;
}

function update_option( $name, $value ) {
	$GLOBALS['guard_test_option_writes'][] = array( $name, $value );
	if ( $GLOBALS['guard_test_option_write_fails'] ) {
		return false;
	}
	$changed = get_option( $name ) !== $value;
	$GLOBALS['guard_test_options'][ $name ] = $value;
	return $changed;
}

function add_option( $name, $value ) {
	if ( array_key_exists( $name, $GLOBALS['guard_test_options'] ) ) {
		return false;
	}
	return update_option( $name, $value );
}

/** Database/delta doubles model successful, partial and failed schema changes. */
class WCJ_Cart_Abandonment_Test_Database {
	public $prefix = 'wp_';
	public $last_error = '';
	public $charset_calls = 0;

	public function get_charset_collate() {
		++$this->charset_calls;
		return 'DEFAULT CHARACTER SET utf8mb4';
	}

	public function get_var( $query ) {
		preg_match( "/show tables like '([^']+)'/i", $query, $matches );
		return isset( $GLOBALS['guard_test_tables'][ $matches[1] ] ) ? $matches[1] : null;
	}
}

function dbDelta( $queries, $execute = true ) {
	global $wpdb;
	$queries = (array) $queries;
	$GLOBALS['guard_test_delta_calls'][] = array( $execute, $queries, get_option( 'test_db_version' ) );
	$changes = array();
	$wpdb->last_error = '';
	foreach ( $queries as $query ) {
		preg_match( '/CREATE TABLE ([^ ]+)/', $query, $matches );
		$table = $matches[1];
		// Core's column-type parser requires one space after a column name.
		$unstable_spacing = preg_match( '/^\s*(?!PRIMARY\b)\w+ {2,}\w+/im', $query );
		if ( $unstable_spacing || ! isset( $GLOBALS['guard_test_tables'][ $table ] ) || $query !== $GLOBALS['guard_test_tables'][ $table ] ) {
			$changes[ $table ] = 'Created or updated ' . $table;
			if ( $execute && ! in_array( $table, $GLOBALS['guard_test_failed_tables'], true ) ) {
				$GLOBALS['guard_test_tables'][ $table ] = $query;
			}
		}
	}
	if ( ! $execute && $GLOBALS['guard_test_verification_error'] ) {
		$wpdb->last_error = 'Schema inspection failed';
		return array();
	}
	// Like core dbDelta, the return describes intended changes even on failure.
	return $changes;
}

require dirname( __DIR__ ) . '/includes/class-wcj-cart-abandonment.php';

/** Count entry into schema setup without executing SQL or upgrade.php. */
class WCJ_Cart_Abandonment_Guard_Test extends WCJ_Cart_Abandonment {
	public $table_setup_calls = 0;

	public function wcj_cart_abandonment_required_tables() {
		++$this->table_setup_calls;
	}
}

/** A future release must use the same version for the guard and marker write. */
class WCJ_Cart_Abandonment_Next_Version_Test extends WCJ_Cart_Abandonment {
	const DB_VERSION = '1.0.1';
}

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		throw new RuntimeException(
			$message . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true )
		);
	}
}

function fixture( $enabled, $options, $network_options, $class = 'WCJ_Cart_Abandonment_Guard_Test' ) {
	$GLOBALS['guard_test_enabled']            = $enabled;
	$GLOBALS['guard_test_options']            = $options;
	$GLOBALS['guard_test_network_options']    = $network_options;
	$GLOBALS['guard_test_actions']            = array();
	$GLOBALS['guard_test_tables']             = array();
	$GLOBALS['guard_test_failed_tables']      = array();
	$GLOBALS['guard_test_delta_calls']        = array();
	$GLOBALS['guard_test_option_writes']      = array();
	$GLOBALS['guard_test_option_write_fails'] = false;
	$GLOBALS['guard_test_verification_error'] = false;
	$GLOBALS['wpdb']                          = new WCJ_Cart_Abandonment_Test_Database();
	unset( $GLOBALS['wcj_db_version'] );
	return new $class();
}

function migration_fixture( $version = false, $class = 'WCJ_Cart_Abandonment' ) {
	$options = false === $version ? array() : array( 'test_db_version' => $version );
	return fixture( false, $options, array(), $class );
}

function seed_existing_tables() {
	$GLOBALS['guard_test_tables'] = array(
		'wp_wcj_cart_abandonment_data' => 'existing table definition',
		'wp_wcj_abandonment_email_history' => 'existing table definition',
	);
}

function assert_verified_migration( $version, $previous_marker ) {
	$calls = $GLOBALS['guard_test_delta_calls'];
	assert_same( 2, count( $calls ), 'Setup must apply then verify the schema' );
	assert_same( true, $calls[0][0], 'First delta pass must apply changes' );
	assert_same( false, $calls[1][0], 'Second delta pass must only compare' );
	assert_same( $calls[0][1], $calls[1][1], 'Verification must inspect the same definitions' );
	assert_same( 2, count( $calls[0][1] ), 'Both table definitions must be supplied' );
	assert_same( $previous_marker, $calls[0][2], 'Marker must not advance before applying changes' );
	assert_same( $previous_marker, $calls[1][2], 'Marker must not advance before verification' );
	assert_same( $version, get_option( 'test_db_version' ), 'Verified version must be persisted' );
}

function run_plugins_loaded() {
	$priorities = $GLOBALS['guard_test_actions']['plugins_loaded'];
	ksort( $priorities );
	foreach ( $priorities as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			call_user_func( $callback );
		}
	}
}

$tests = array(
	'current schema skips setup when module disabled' => function () {
		$module = fixture( false, array( 'test_db_version' => '1.0.0' ), array( 'test_db_version' => '1.0.0' ) );
		run_plugins_loaded();
		assert_same( 0, $module->table_setup_calls, 'Existing schema must not enter table setup' );
	},
	'current schema skips setup when module enabled' => function () {
		$module = fixture( true, array( 'test_db_version' => '1.0.0' ), array( 'test_db_version' => '1.0.0' ) );
		run_plugins_loaded();
		assert_same( 0, $module->table_setup_calls, 'Enabling tracking must not recheck existing tables' );
	},
	'current schema skips setup on repeated bootstraps' => function () {
		for ( $i = 0; $i < 3; ++$i ) {
			$module = fixture( false, array( 'test_db_version' => '1.0.0' ), array( 'test_db_version' => '1.0.0' ) );
			run_plugins_loaded();
			assert_same( 0, $module->table_setup_calls, 'Fresh bootstrap must not enter table setup' );
		}
	},
	'missing schema version still enters setup' => function () {
		$module = fixture( false, array(), array() );
		run_plugins_loaded();
		assert_same( 1, $module->table_setup_calls, 'Missing marker must retain the initialization path' );
		assert_same( array( $module, 'wcj_cart_abandonment_required_tables' ), $GLOBALS['guard_test_activation'], 'Activation hook must remain registered' );
	},
	'older schema version still enters setup' => function () {
		$module = fixture( false, array( 'test_db_version' => '0.9.0' ), array( 'test_db_version' => '0.9.0' ) );
		run_plugins_loaded();
		assert_same( 1, $module->table_setup_calls, 'Version mismatch must retain the setup path' );
	},
	'guard does not depend on an unrelated global' => function () {
		$module = fixture( false, array( 'test_db_version' => '1.0.0' ), array( 'test_db_version' => '1.0.0' ) );
		$GLOBALS['wcj_db_version'] = 'unrelated';
		run_plugins_loaded();
		assert_same( 0, $module->table_setup_calls, 'Expected schema version must not come from ambient globals' );
	},
	'per-site schema marker is independent of network options' => function () {
		$module = fixture( false, array( 'test_db_version' => '1.0.0' ), array() );
		run_plugins_loaded();
		assert_same( 0, $module->table_setup_calls, 'Guard must read the per-site store used by add_option' );
	},
	'fresh install records its version after both tables are verified' => function () {
		migration_fixture();
		assert_same( true, call_user_func( $GLOBALS['guard_test_activation'] ), 'Activation must install the schema' );
		assert_verified_migration( '1.0.0', false );
	},
	'missing marker with existing tables is repaired once' => function () {
		migration_fixture();
		seed_existing_tables();
		run_plugins_loaded();
		assert_verified_migration( '1.0.0', false );
		run_plugins_loaded();
		assert_same( 2, count( $GLOBALS['guard_test_delta_calls'] ), 'Next bootstrap must skip setup' );
	},
	'older marker updates existing tables and stops repeating' => function () {
		migration_fixture( '0.9.0' );
		seed_existing_tables();
		run_plugins_loaded();
		assert_verified_migration( '1.0.0', '0.9.0' );
		run_plugins_loaded();
		assert_same( 2, count( $GLOBALS['guard_test_delta_calls'] ), 'Successful upgrade must run once' );
	},
	'future version bump updates the marker and runs only once' => function () {
		migration_fixture( '1.0.0', 'WCJ_Cart_Abandonment_Next_Version_Test' );
		seed_existing_tables();
		run_plugins_loaded();
		assert_verified_migration( '1.0.1', '1.0.0' );
		run_plugins_loaded();
		assert_same( 2, count( $GLOBALS['guard_test_delta_calls'] ), 'Future guard must recognize its persisted version' );
	},
	'partial migration never publishes a success marker' => function () {
		foreach ( array( 'wp_wcj_cart_abandonment_data', 'wp_wcj_abandonment_email_history' ) as $failed_table ) {
			$module = migration_fixture( '0.9.0' );
			$GLOBALS['guard_test_failed_tables'] = array( $failed_table );
			assert_same( false, $module->wcj_cart_abandonment_required_tables(), 'Incomplete schema must fail verification' );
			assert_same( '0.9.0', get_option( 'test_db_version' ), 'Old version must survive either table failing' );
			assert_same( array(), $GLOBALS['guard_test_option_writes'], 'Partial success must never be marked complete' );
		}
	},
	'failed migration retries and advances only after success' => function () {
		migration_fixture( '0.9.0' );
		$GLOBALS['guard_test_failed_tables'] = array( 'wp_wcj_cart_abandonment_data' );
		run_plugins_loaded();
		assert_same( '0.9.0', get_option( 'test_db_version' ), 'Failed migration must retain its retry marker' );
		$GLOBALS['guard_test_failed_tables'] = array();
		run_plugins_loaded();
		assert_same( '1.0.0', get_option( 'test_db_version' ), 'Retry must eventually record success' );
		run_plugins_loaded();
		assert_same( 4, count( $GLOBALS['guard_test_delta_calls'] ), 'Successful retry must stop subsequent schema work' );
	},
	'schema inspection errors cannot be mistaken for success' => function () {
		$module = migration_fixture( '0.9.0' );
		$GLOBALS['guard_test_verification_error'] = true;
		assert_same( false, $module->wcj_cart_abandonment_required_tables(), 'Inspection error must fail closed' );
		assert_same( '0.9.0', get_option( 'test_db_version' ), 'Inspection error must preserve the marker' );
		assert_same( array(), $GLOBALS['guard_test_option_writes'], 'Empty delta result with an error is not success' );
	},
	'failed marker write remains retryable' => function () {
		$module = migration_fixture( '0.9.0' );
		$GLOBALS['guard_test_option_write_fails'] = true;
		assert_same( false, $module->wcj_cart_abandonment_required_tables(), 'Failed marker write must be reported' );
		assert_same( '0.9.0', get_option( 'test_db_version' ), 'Failed marker write must not advance state' );
		$GLOBALS['guard_test_option_write_fails'] = false;
		run_plugins_loaded();
		assert_same( '1.0.0', get_option( 'test_db_version' ), 'Later bootstrap must retry the write' );
	},
	'already-current direct setup treats an unchanged marker as success' => function () {
		$module = migration_fixture( '1.0.0' );
		assert_same( true, $module->wcj_cart_abandonment_required_tables(), 'update_option returning false for unchanged data is not failure' );
		assert_verified_migration( '1.0.0', '1.0.0' );
	},
	'newer schema is not downgraded by an older plugin build' => function () {
		$module = migration_fixture( '1.0.1' );
		run_plugins_loaded();
		assert_same( false, $module->wcj_cart_abandonment_required_tables(), 'Direct activation/update must also refuse a downgrade' );
		assert_same( array(), $GLOBALS['guard_test_delta_calls'], 'Newer schema must not receive older table definitions' );
		assert_same( 0, $GLOBALS['wpdb']->charset_calls, 'Downgrade must exit before schema inspection' );
		assert_same( '1.0.1', get_option( 'test_db_version' ), 'Newer marker must remain intact' );
	},
);

$failures = 0;
foreach ( $tests as $name => $test ) {
	try {
		$test();
		echo 'PASS: ' . $name . PHP_EOL;
	} catch ( Throwable $error ) {
		++$failures;
		fwrite( STDERR, 'FAIL: ' . $name . ': ' . $error->getMessage() . PHP_EOL );
	}
}

echo count( $tests ) . ' tests; ' . $failures . ' failures' . PHP_EOL;
exit( $failures ? 1 : 0 );
