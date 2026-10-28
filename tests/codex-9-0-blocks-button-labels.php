<?php
/**
 * Offline whole-class Blocks button label regression fixture.
 *
 * Usage: php -n fixture.php plugin-root pinned-Woo-reference-root baseline-module
 * No WordPress boot, database, native browser or payment execution occurs.
 *
 * @package Booster_For_WooCommerce/tests
 */

define( 'ABSPATH', __DIR__ . '/' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required offline WordPress constant.
define( 'WCJ_PLUGIN_FILE', $argv[1] . '/booster-test.php' );

$GLOBALS['wcj_codex_hooks']   = array();
$GLOBALS['wcj_codex_options'] = array();
$GLOBALS['wcj_codex_scripts'] = array();
$GLOBALS['wcj_codex_checks']  = array();
$GLOBALS['wcj_codex_role']    = 'customer';
$GLOBALS['wcj_codex_wc']      = null;

/**
 * Translate without loading external resources.
 *
 * @param mixed $text Synthetic fixture argument.
 * @param mixed $domain Synthetic fixture argument.
 * @return mixed
 */
function __( $text, $domain = '' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Exact offline translation API signature, no catalogue loading.
	return $text;
}

/**
 * Record the actual class hooks.
 *
 * @param mixed $name Synthetic fixture argument.
 * @param mixed $callback Synthetic fixture argument.
 * @param mixed $priority Synthetic fixture argument.
 * @param mixed $accepted_args Synthetic fixture argument.
 * @return mixed
 */
function add_filter( $name, $callback, $priority = 10, $accepted_args = 1 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WordPress/Woo API double.
	$GLOBALS['wcj_codex_hooks'][ $name ][ $priority ][] = array( $callback, $accepted_args );
}

/**
 * Record an action with the same fixture hook runner.
 *
 * @param mixed $name Synthetic fixture argument.
 * @param mixed $callback Synthetic fixture argument.
 * @param mixed $priority Synthetic fixture argument.
 * @param mixed $accepted_args Synthetic fixture argument.
 * @return mixed
 */
function add_action( $name, $callback, $priority = 10, $accepted_args = 1 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WordPress/Woo API double.
	add_filter( $name, $callback, $priority, $accepted_args );
}

/**
 * Run filters in priority order.
 *
 * @param mixed $name Synthetic fixture argument.
 * @param mixed $value Synthetic fixture argument.
 * @param mixed ...$args Synthetic fixture argument.
 * @return mixed
 */
function apply_filters( $name, $value, ...$args ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WordPress/Woo API double.
	$hooks = $GLOBALS['wcj_codex_hooks'][ $name ] ?? array();
	ksort( $hooks );
	foreach ( $hooks as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			$value = call_user_func_array( $entry[0], array_slice( array_merge( array( $value ), $args ), 0, $entry[1] ) );
		}
	}
	return $value;
}

/**
 * Run the real registry's registration action.
 *
 * @param mixed $name Synthetic fixture argument.
 * @param mixed ...$args Synthetic fixture argument.
 * @return mixed
 */
function do_action( $name, ...$args ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WordPress/Woo API double.
	$hooks = $GLOBALS['wcj_codex_hooks'][ $name ] ?? array();
	ksort( $hooks );
	foreach ( $hooks as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			call_user_func_array( $entry[0], array_slice( $args, 0, $entry[1] ) );
		}
	}
}

/**
 * Keep runtime allowlist reads in the pre-plugins-loaded branch.
 *
 * @param mixed $name Synthetic fixture argument.
 * @return mixed
 */
function did_action( $name ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Exact API signature, this fixture stays before plugins_loaded.
	return 0;
}

/**
 * Read a typed synthetic option.
 *
 * @param mixed $name Synthetic fixture argument.
 * @param mixed $fallback Synthetic fixture argument.
 * @return mixed
 */
function wcj_get_option( $name, $fallback = false ) {
	return array_key_exists( $name, $GLOBALS['wcj_codex_options'] ) ? $GLOBALS['wcj_codex_options'][ $name ] : $fallback;
}

/** Supply the role to the actual Booster role helper. */
function wp_get_current_user() {
	return (object) array( 'roles' => array( $GLOBALS['wcj_codex_role'] ) );
}

/**
 * Supply a valid sanitized synthetic key.
 *
 * @param mixed $key Synthetic fixture argument.
 * @return mixed
 */
function sanitize_key( $key ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WordPress/Woo API double.
	return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key ) );
}

/** Identify the synthetic non-REST request. */
function rest_get_url_prefix() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WordPress API double.
	return 'wp-json';
}

/**
 * Return the synthetic site URL.
 *
 * @param mixed $path Synthetic fixture argument.
 * @return mixed
 */
function site_url( $path = '' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WordPress/Woo API double.
	return 'https://example.test/' . $path;
}

/**
 * Return the synthetic current page URL.
 *
 * @param mixed $args Synthetic fixture argument.
 * @return mixed
 */
function add_query_arg( $args ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Exact API signature, fixture supplies one fixed current URL.
	return 'https://example.test/my-account/';
}

/**
 * Parse synthetic URLs with the native parser.
 *
 * @param mixed $url Synthetic fixture argument.
 * @return mixed
 */
function wp_parse_url( $url ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WordPress/Woo API double.
	return parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Native parser inside the offline API double.
}

/** Supply the synthetic Woo singleton. */
function WC() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- Exact offline Woo singleton API double.
	return $GLOBALS['wcj_codex_wc'];
}

/**
 * Construct the frontend URL without transporting data.
 *
 * @param mixed $path Synthetic fixture argument.
 * @param mixed $file Synthetic fixture argument.
 * @return mixed
 */
function plugins_url( $path, $file ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Exact API signature, all synthetic tiers share the fixed plugin root.
	return 'https://example.test/wp-content/plugins/booster-test/' . $path;
}

/**
 * Record script registration without enqueuing or executing it.
 *
 * @param mixed $handle Synthetic fixture argument.
 * @param mixed $url Synthetic fixture argument.
 * @param mixed $dependencies Synthetic fixture argument.
 * @param mixed $version Synthetic fixture argument.
 * @param mixed $footer Synthetic fixture argument.
 * @return mixed
 */
function wp_register_script( $handle, $url, $dependencies, $version, $footer ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WordPress/Woo API double.
	$GLOBALS['wcj_codex_scripts'][ $handle ] = array( $url, $dependencies, $version, $footer );
}

/**
 * Encode typed synthetic data without booting WordPress.
 *
 * @param mixed $value Fixture value.
 * @param int   $flags JSON flags.
 * @return string|false
 */
function wp_json_encode( $value, $flags = 0 ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Exact offline WordPress API double.
	return json_encode( $value, $flags ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Native serializer inside the offline API double.
}

/**
 * Record a typed assertion without short-circuiting the suite.
 *
 * @param mixed $name Synthetic fixture argument.
 * @param mixed $actual Synthetic fixture argument.
 * @param mixed $expected Synthetic fixture argument.
 * @return mixed
 */
function wcj_codex_check( $name, $actual, $expected ) {
	$GLOBALS['wcj_codex_checks'][] = array(
		'name'     => $name,
		'actual'   => $actual,
		'expected' => $expected,
		'pass'     => $actual === $expected,
	);
}

/**
 * Reset synthetic state and load the real module constructor.
 *
 * @param mixed $enabled Synthetic fixture argument.
 * @param mixed $override Synthetic fixture argument.
 * @param mixed $excluded Synthetic fixture argument.
 * @return mixed
 */
function wcj_codex_module( $enabled, $override = 'no', $excluded = false ) {
	$GLOBALS['wcj_codex_hooks']   = array();
	$GLOBALS['wcj_codex_options'] = array(
		'wcj_more_button_labels_enabled'           => $enabled ? 'yes' : 'no',
		'wcj_checkout_place_order_button_override' => $override,
		'wcj_modules_by_user_roles_enabled'        => $excluded ? 'yes' : 'no',
		'wcj_modules_by_user_roles_excl_customer'  => $excluded ? array( 'more_button_labels' ) : array(),
	);
	unset( $GLOBALS['wcj_modules_by_user_roles_data'] );
	return new WCJ_More_Button_Labels();
}

/**
 * Compare an existing method's complete bytes against the frozen baseline.
 *
 * @param mixed $path Synthetic fixture argument.
 * @param mixed $method Synthetic fixture argument.
 * @return mixed
 */
function wcj_codex_method_bytes( $path, $method ) {
	$source = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Offline byte-exact local baseline comparison.
	$start  = strpos( $source, "\t\tpublic function " . $method );
	$end    = strpos( $source, "\n\t\t}", $start );
	return substr( $source, $start, $end + strlen( "\n\t\t}" ) - $start );
}

/**
 * Run the complete actual module and pinned Woo registry fixture.
 *
 * @param string $root Plugin Git working folder.
 * @param string $upstream Pinned Woo reference folder.
 * @param string $baseline Frozen original module.
 * @return void
 */
function wcj_codex_run( $root, $upstream, $baseline ) {
	require $root . '/includes/functions/wcj-functions-booster-core.php';
	require $root . '/includes/classes/class-wcj-module.php';
	$module = require $root . '/includes/class-wcj-more-button-labels.php';

	foreach ( array( 'override_order_button_text', 'set_order_button_text' ) as $method ) {
		wcj_codex_check( 'classic method bytes ' . $method, wcj_codex_method_bytes( $root . '/includes/class-wcj-more-button-labels.php', $method ), wcj_codex_method_bytes( $baseline, $method ) );
	}

	foreach ( array( array( false, false ), array( true, true ) ) as $disabled ) {
		$disabled_module = wcj_codex_module( $disabled[0], 'yes', $disabled[1] );
		wcj_codex_check( 'disabled/excluded Blocks hook ' . wp_json_encode( $disabled ), isset( $GLOBALS['wcj_codex_hooks']['woocommerce_blocks_checkout_block_registration'] ), false );
		wcj_codex_check( 'disabled/excluded Classic filter ' . wp_json_encode( $disabled ), isset( $GLOBALS['wcj_codex_hooks']['woocommerce_order_button_text'] ), false );
		wcj_codex_check( 'disabled/excluded Classic clear hook ' . wp_json_encode( $disabled ), isset( $GLOBALS['wcj_codex_hooks']['init'][ PHP_INT_MAX ] ), true );
		// The parent module already registers reset_settings at PHP_INT_MAX, not gateway clearing.
		$init_callbacks = array_column( $GLOBALS['wcj_codex_hooks']['init'][ PHP_INT_MAX ], 0 );
		wcj_codex_check( 'disabled/excluded does not clear gateways ' . wp_json_encode( $disabled ), in_array( array( $disabled_module, 'override_order_button_text' ), $init_callbacks, true ), false );
	}

	foreach ( array( 'no', 'yes' ) as $override ) {
		$module = wcj_codex_module( true, $override );
		wcj_codex_check( 'enabled Blocks registration hook ' . $override, isset( $GLOBALS['wcj_codex_hooks']['woocommerce_blocks_checkout_block_registration'][10] ), true );
		wcj_codex_check( 'Classic filter exact priority ' . $override, $GLOBALS['wcj_codex_hooks']['woocommerce_order_button_text'][ PHP_INT_MAX ][0], array( array( $module, 'set_order_button_text' ), 1 ) );
		$init_callbacks = array_column( $GLOBALS['wcj_codex_hooks']['init'][ PHP_INT_MAX ], 0 );
		wcj_codex_check( 'Classic clear exact override policy ' . $override, in_array( array( $module, 'override_order_button_text' ), $init_callbacks, true ), 'yes' === $override );
		foreach ( array( '', '0', 'Place my order', '支付 <script> & " ✓', null, false, 0 ) as $text ) {
			$GLOBALS['wcj_codex_options']['wcj_checkout_place_order_button_text'] = $text;
			wcj_codex_check( 'Classic typed output ' . $override . ' ' . wp_json_encode( $text ), $module->set_order_button_text( 'Merchant label' ), '' !== $text ? $text : 'Merchant label' );
		}
	}

	$GLOBALS['wcj_codex_wc'] = new class() {
		/**
		 * Synthetic gateway container.
		 *
		 * @var object
		 */
		public $gateways;
		/** Construct two labelled synthetic gateways. */
		public function __construct() {
			$this->gateways = (object) array( 'payment_gateways' => array( (object) array( 'order_button_text' => 'Gateway A' ), (object) array( 'order_button_text' => 'Gateway B' ) ) );
		}
		/** Return the synthetic gateway container. */
		public function payment_gateways() {
			return $this->gateways;
		}
	};
	$module->override_order_button_text();
	wcj_codex_check( 'Classic clears every PHP gateway even for empty text', array_column( WC()->gateways->payment_gateways, 'order_button_text' ), array( '', '' ) );

	wcj_codex_check( 'Blocks callback exists', method_exists( $module, 'register_blocks_integration' ), true );
	if ( method_exists( $module, 'register_blocks_integration' ) ) {
		$module->register_blocks_integration( new stdClass() );
		wcj_codex_check( 'missing Woo interface does not load integration class', class_exists( 'WCJ_More_Button_Labels_Blocks', false ), false );
		require $upstream . '/src/Blocks/Integrations/IntegrationInterface.php';
		require $upstream . '/src/Blocks/Integrations/IntegrationRegistry.php';
		$registry = new Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry();
		$registry->initialize( 'checkout_block' );
		wcj_codex_check( 'real registry integration registered', $registry->is_registered( 'wcj_more_button_labels' ), true );
		wcj_codex_check( 'frontend-only handle', $registry->get_all_registered_script_handles(), array( 'wcj-more-button-labels-blocks' ) );
		wcj_codex_check( 'no editor script', $registry->get_all_registered_editor_script_handles(), array() );
		wcj_codex_check( 'exact dependency registration', $GLOBALS['wcj_codex_scripts']['wcj-more-button-labels-blocks'] ?? null, array( 'https://example.test/wp-content/plugins/booster-test/includes/js/wcj-more-button-labels-blocks.js', array( 'wc-blocks-checkout', 'wc-settings', 'wc-blocks-data-store', 'wc-blocks-registry', 'wp-data' ), '9.0.0', true ) );
		foreach ( array( 'no', 'yes' ) as $override ) {
			$GLOBALS['wcj_codex_options']['wcj_checkout_place_order_button_override'] = $override;
			foreach ( array( '', '0', '支付 <script> & " ✓', null, false, 0, array( 'bad' ) ) as $text ) {
				$GLOBALS['wcj_codex_options']['wcj_checkout_place_order_button_text'] = $text;
				$data = $registry->get_all_registered_script_data();
				wcj_codex_check(
					'real settings data ' . $override . ' ' . wp_json_encode( $text ),
					$data,
					array(
						'wcj_more_button_labels_data' => array(
							'text'     => is_string( $text ) ? $text : '',
							'override' => 'yes' === $override,
						),
					)
				);
				wcj_codex_check( 'settings safe JSON round trip ' . $override . ' ' . wp_json_encode( $text ), json_decode( wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ), true ), $data );
			}
		}
	}

	$failures = array_values(
		array_filter(
			$GLOBALS['wcj_codex_checks'],
			static function ( $check ) {
				return ! $check['pass'];
			}
		)
	);
	echo wp_json_encode(
		array(
			'assertions' => count( $GLOBALS['wcj_codex_checks'] ),
			'failures'   => count( $failures ),
			'checks'     => $GLOBALS['wcj_codex_checks'],
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	) . PHP_EOL;
	exit( empty( $failures ) ? 0 : 1 );
}

wcj_codex_run( $argv[1], $argv[2], $argv[3] );
