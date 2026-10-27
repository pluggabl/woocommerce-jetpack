<?php
/**
 * Standalone regression tests for the cart-abandonment schema-version guard.
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

define( 'ABSPATH', __DIR__ . '/' );
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

require dirname( __DIR__ ) . '/includes/class-wcj-cart-abandonment.php';

/** Count entry into schema setup without executing SQL or upgrade.php. */
class WCJ_Cart_Abandonment_Guard_Test extends WCJ_Cart_Abandonment {
	public $table_setup_calls = 0;

	public function wcj_cart_abandonment_required_tables() {
		++$this->table_setup_calls;
	}
}

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		throw new RuntimeException(
			$message . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true )
		);
	}
}

function fixture( $enabled, $options, $network_options ) {
	$GLOBALS['guard_test_enabled']         = $enabled;
	$GLOBALS['guard_test_options']         = $options;
	$GLOBALS['guard_test_network_options'] = $network_options;
	$GLOBALS['guard_test_actions']         = array();
	unset( $GLOBALS['wcj_db_version'] );
	return new WCJ_Cart_Abandonment_Guard_Test();
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
