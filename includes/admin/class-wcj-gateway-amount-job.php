<?php
/** One method's native cart-amount bounds. No activation, cart evaluation or payment mutation.
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-job-store.php';
require_once __DIR__ . '/payment-choice-groups.php';
final class WCJ_Gateway_Amount_Job {
	const ID = 'payment_gateways_min_max';
	const MODULE = 'wcj_payment_gateways_min_max_enabled';
	const RECEIPT = 'booster_gateway_amount_job_receipt';
	const OPS = array( 'load', 'review', 'save', 'undo' );
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register' ), 99 );
		foreach ( self::OPS as $op ) { add_action( 'wp_ajax_wcj_gateway_amount_' . $op, array( $this, 'handle' ) ); }
	}
	public static function url() { return admin_url( 'admin.php?page=wcj-gateway-amount-job' ); }
	public static function native_url() { return WCJ_Payment_Choice_Groups::native_url( self::ID, 'wcj_payment_gateways_min_max_payment_gatways_tab' ); }
	public static function elite() { return defined( 'WCJ_PLUGIN_FILE' ) && 'booster-elite-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ); }
	private static function fail( $text, $code = 422 ) { throw new RuntimeException( $text, $code ); }
	private static function authorize() {
		if ( ! WCJ_Task_Finder::allowed( self::ID ) ) { self::fail( 'Ask an administrator for access to payment amount settings.', 403 ); }
	}
	public function register() {
		if ( WCJ_Task_Finder::allowed( self::ID ) ) { add_submenu_page( 'woocommerce', 'Limit one payment method by cart amount', 'Payment method amount limits', 'manage_woocommerce', 'wcj-gateway-amount-job', array( $this, 'render' ) ); }
	}
	/** Registered inventory only. Never call is_available or get_available_payment_gateways here. */
	public static function gateways() {
		$out = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $id => $gateway ) {
			if ( is_string( $id ) && preg_match( '/^[a-z0-9_-]{1,64}$/D', $id ) ) {
				$out[ $id ] = array( 'title' => wp_strip_all_tags( (string) wcj_get_payment_gateway_admin_title( $gateway, $id ) ), 'enabled' => $gateway->enabled, 'class' => get_class( $gateway ) );
			}
		}
		ksort( $out ); return $out;
	}
	private static function gateway( $g ) {
		if ( ! is_string( $g ) || ! preg_match( '/^[a-z0-9_-]{1,64}$/D', $g ) || ! isset( self::gateways()[ $g ] ) ) { self::fail( 'This payment method is not registered. Keep your draft and use full settings.', 409 ); }
		if ( ! self::elite() && 'bacs' !== $g ) { self::fail( 'This shortcut supports bank transfer in Free and Plus. Plus retains its existing native settings for other methods.', 403 ); }
	}
	/** Fixed-width decimal key: no floating point, rounding, exponent or locale conversion. */
	public static function decimal( $v ) {
		if ( ! is_string( $v ) || ! preg_match( '/^(0|[1-9][0-9]{0,8})(?:\.([0-9]{1,6}))?$/D', $v, $m ) ) { return null; }
		$key = str_pad( $m[1], 9, '0', STR_PAD_LEFT ) . str_pad( isset( $m[2] ) ? $m[2] : '', 6, '0' );
		return '000000000000000' === $key ? null : $key;
	}
	public static function no_limit( $raw ) { return null === $raw || '0' === $raw; }
	public static function supported( $raw ) { return self::no_limit( $raw ) || null !== self::decimal( $raw ); }
	public static function equivalent( $a, $b ) { return self::no_limit( $a ) && self::no_limit( $b ) || null !== self::decimal( $a ) && self::decimal( $a ) === self::decimal( $b ); }
	public static function narrowing( $before, $after ) {
		return ! self::no_limit( $after['min'] ) && ( self::no_limit( $before['min'] ) || strcmp( self::decimal( $after['min'] ), self::decimal( $before['min'] ) ) > 0 )
			|| ! self::no_limit( $after['max'] ) && ( self::no_limit( $before['max'] ) || strcmp( self::decimal( $after['max'] ), self::decimal( $before['max'] ) ) < 0 );
	}
	private static function key( $g, $side ) { return 'wcj_payment_gateways_' . $side . '_' . $g; }
	/** Only options interpreted or written by this job participate in filter admission.
	 * The broader raw snapshot remains the conservative stale-context guard.
	 */
	private static function filter_keys( $g, $roles ) {
		global $wpdb;
		$keys = array( self::MODULE, self::key( $g, 'min' ), self::key( $g, 'max' ), 'wcj_payment_gateways_min_max_include_tax', 'wcj_payment_gateways_min_max_exclude_shipping', 'wcj_payment_gateways_min_max_comp_mc', 'wcj_multicurrency_enabled', 'wcj_modules_by_user_roles_enabled', 'woocommerce_currency', 'woocommerce_' . $g . '_settings', $wpdb->prefix . 'user_roles', self::RECEIPT );
		foreach ( $roles as $role ) {
			$keys[] = 'wcj_modules_by_user_roles_incl_' . $role;
			$keys[] = 'wcj_modules_by_user_roles_excl_' . $role;
		}
		sort( $keys ); return array_values( array_unique( $keys ) );
	}
	/** Qualify the observed Woo HPOS global filter without invoking any callback.
	 * Only the pinned method body, real installed Woo file and exact disjoint constants
	 * establish its early no-op for our keys. Unknown or changed callbacks fail closed.
	 */
	private static function global_update_filter( $g ) {
		global $wp_filter;
		if ( ! has_filter( 'pre_update_option' ) ) { return array( 'safe' => true, 'binding' => 'absent' ); }
		$unknown = array( 'safe' => false, 'binding' => 'unqualified' );
		if ( ! isset( $wp_filter['pre_update_option'] ) || ! $wp_filter['pre_update_option'] instanceof WP_Hook || ! is_array( $wp_filter['pre_update_option']->callbacks ) || ! defined( 'WC_ABSPATH' ) ) { return $unknown; }
		$class = 'Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController';
		$sync = 'Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer';
		$bindings = array();
		try {
			foreach ( $wp_filter['pre_update_option']->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $entry ) {
					$f = $entry['function'] ?? null;
					if ( 999 !== (int) $priority || 3 !== ( $entry['accepted_args'] ?? null ) || ! is_array( $f ) || 2 !== count( $f ) || ! isset( $f[0], $f[1] ) || ! is_object( $f[0] ) || get_class( $f[0] ) !== $class || 'process_pre_update_option' !== $f[1] || ! class_exists( $sync, false ) ) { return $unknown; }
					$method = new ReflectionMethod( $f[0], $f[1] );
					$file = realpath( WC_ABSPATH . 'src/Internal/DataStores/Orders/CustomOrdersTableController.php' );
					if ( false === $file || realpath( $method->getFileName() ) !== $file || $method->getDeclaringClass()->getName() !== $class || ! $method->isPublic() || $method->isStatic() || 3 !== $method->getNumberOfParameters() || ! is_readable( $file ) ) { return $unknown; }
					$lines = file( $file, FILE_IGNORE_NEW_LINES );
					if ( ! is_array( $lines ) ) { return $unknown; }
					$body = implode( "\n", array_slice( $lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1 ) );
					$fingerprint = hash( 'sha256', $body );
					if ( '45c08141fc7a2f7977c41f668cd71923bff54bce974f4793c71fe67c4ea4235a' !== $fingerprint || ! defined( $class . '::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION' ) || ! defined( $sync . '::ORDERS_DATA_SYNC_ENABLED_OPTION' ) ) { return $unknown; }
					$usage = constant( $class . '::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION' ); $sync_option = constant( $sync . '::ORDERS_DATA_SYNC_ENABLED_OPTION' );
					if ( 'woocommerce_custom_orders_table_enabled' !== $usage || 'woocommerce_custom_orders_table_data_sync_enabled' !== $sync_option || array_intersect( array( $usage, $sync_option ), array( self::key( $g, 'min' ), self::key( $g, 'max' ), self::RECEIPT ) ) ) { return $unknown; }
					$bindings[] = array( $class, $f[1], 999, 3, $fingerprint, $usage, $sync_option );
				}
			}
		} catch ( Throwable $e ) { return $unknown; }
		return $bindings ? array( 'safe' => true, 'binding' => hash( 'sha256', serialize( $bindings ) ) ) : $unknown;
	}
	/** Full relevant configuration and absence/autoload preimages, never sent to the browser. */
	private static function context( $g, $lock = false ) {
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT option_name,option_value,autoload FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name IN ('woocommerce_currency','active_plugins',%s) ORDER BY option_name LIMIT 20001", $wpdb->esc_like( 'wcj_' ) . '%', $wpdb->esc_like( 'woocommerce_' ) . '%', self::RECEIPT );
		$rows = $wpdb->get_results( $sql . ( $lock ? ' FOR UPDATE' : '' ), ARRAY_A );
		if ( $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 20000 ) { self::fail( 'The current settings could not be read safely. Keep the draft and use full settings.', 409 ); }
		$out = array(); foreach ( $rows as $r ) { $out[ $r['option_name'] ] = array( 'raw' => (string) $r['option_value'], 'autoload' => (string) $r['autoload'] ); }
		$user = wp_get_current_user();
		$role_rows = $wpdb->get_results( $wpdb->prepare( "SELECT umeta_id,meta_key,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key IN (%s,%s) ORDER BY umeta_id" . ( $lock ? ' FOR UPDATE' : '' ), get_current_user_id(), $wpdb->prefix . 'capabilities', $wpdb->prefix . 'user_level' ), ARRAY_A );
		if ( $wpdb->last_error || ! is_array( $role_rows ) ) { self::fail( 'Current role storage could not be verified.', 409 ); }
		$role_option = $wpdb->get_row( $wpdb->prepare( "SELECT option_name,option_value,autoload FROM {$wpdb->options} WHERE option_name=%s" . ( $lock ? ' FOR UPDATE' : '' ), $wpdb->prefix . 'user_roles' ), ARRAY_A );
		if ( $wpdb->last_error ) { self::fail( 'Role definitions could not be verified.', 409 ); }
		if ( $role_option ) { $out[ $role_option['option_name'] ] = array( 'raw' => (string) $role_option['option_value'], 'autoload' => (string) $role_option['autoload'] ); }
		ksort( $out );
		$filters = array();
		$filter_keys = self::filter_keys( $g, $user->roles );
		foreach ( $filter_keys as $key ) { foreach ( array( '', 'option_', 'pre_option_', 'default_option_', 'pre_update_option_' ) as $prefix ) { if ( has_filter( $prefix . $key ) ) { $filters[] = $prefix . $key; } } }
		foreach ( array( 'pre_option', 'pre_update_option', 'woocommerce_currency', 'woocommerce_currencies', 'woocommerce_gateway_' . $g . '_settings' ) as $hook ) { if ( has_filter( $hook ) ) { $filters[] = $hook; } }
		sort( $filters );
		return array( 'global_update_filter' => self::global_update_filter( $g ), 'filters' => $filters, 'rows' => $out, 'gateways' => self::gateways(), 'edition' => defined( 'WCJ_PLUGIN_FILE' ) ? basename( WCJ_PLUGIN_FILE ) : '', 'role_rows' => $role_rows, 'roles' => $user->roles, 'caps' => $user->allcaps, 'allowlist' => function_exists( 'wcj_get_enabled_module_allowlist' ) ? wcj_get_enabled_module_allowlist() : null );
	}
	private static function raw( $c, $key, $default = null ) { return isset( $c['rows'][ $key ] ) ? $c['rows'][ $key ]['raw'] : $default; }
	private static function bounds( $g, $c ) { return array( 'min' => self::raw( $c, self::key( $g, 'min' ) ), 'max' => self::raw( $c, self::key( $g, 'max' ) ) ); }
	private static function hash( $g, $c, $receipt = true ) {
		if ( ! $receipt ) { unset( $c['rows'][ self::RECEIPT ] ); }
		return hash_hmac( 'sha256', serialize( array( get_current_user_id(), $g, $c ) ), wp_salt( 'nonce' ) );
	}
	private static function record( $c ) {
		$raw = self::raw( $c, self::RECEIPT );
		$r = is_string( $raw ) && strlen( $raw ) < 12000 && is_serialized( $raw ) ? unserialize( $raw, array( 'allowed_classes' => false ) ) : null;
		if ( ! is_array( $r ) || ! isset( $r['signature'] ) || ! is_string( $r['signature'] ) ) { return null; }
		$signature = $r['signature']; unset( $r['signature'] );
		return hash_equals( hash_hmac( 'sha256', serialize( $r ), wp_salt( 'nonce' ) ), $signature ) ? $r : null;
	}
	private static function storage_reason() {
		global $wpdb;
		if ( wp_using_ext_object_cache() ) { return 'Persistent object caching needs full settings. This shortcut cannot safely save here.'; }
		foreach ( array( $wpdb->options, $wpdb->usermeta ) as $table ) {
			$row = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A );
			if ( ! $row || 'InnoDB' !== $row['Engine'] ) { return 'This shortcut requires transactional option and role storage. Use full settings.'; }
		}
		return '';
	}
	private static function refusal( $g, $c ) {
		$core = WCJ_Job_Store::core();
		if ( empty( $c['roles'] ) || empty( $c['role_rows'] ) ) { return 'The current role configuration could not be qualified. Use full settings.'; }
		if ( ! $core || ! isset( $core->all_modules[ self::ID ] ) ) { return 'The native amount module is unavailable. Use full settings.'; }
		foreach ( array( self::MODULE, 'wcj_payment_gateways_min_max_include_tax', 'wcj_payment_gateways_min_max_exclude_shipping', 'wcj_payment_gateways_min_max_comp_mc', 'wcj_multicurrency_enabled', 'wcj_modules_by_user_roles_enabled' ) as $key ) {
			if ( ! in_array( self::raw( $c, $key, 'no' ), array( 'yes', 'no' ), true ) ) { return 'A saved module or amount-basis flag is nonstandard. It is preserved; use full settings.'; }
		}
		if ( 'no' !== self::raw( $c, 'wcj_payment_gateways_min_max_comp_mc', 'no' ) ) { return 'Multicurrency compatibility is configured on. That does not prove conversion currently runs; this shortcut cannot safely interpret converted thresholds. Use full settings.'; }
		if ( 'yes' === self::raw( $c, 'wcj_multicurrency_enabled', 'no' ) || 'yes' === self::raw( $c, 'wcj_modules_by_user_roles_enabled', 'no' ) ) { return 'Dynamic currency or module-by-role configuration needs full settings. No threshold or activation was changed.'; }
		$currency = self::raw( $c, 'woocommerce_currency', '' );
		if ( ! preg_match( '/^[A-Z]{3}$/D', $currency ) || ! array_key_exists( $currency, get_woocommerce_currencies() ) ) { return 'The store currency is unsupported or missing. Use full settings.'; }
		$keys = self::filter_keys( $g, $c['roles'] );
		foreach ( $keys as $key ) {
			if ( has_filter( $key ) || has_filter( 'option_' . $key ) || has_filter( 'pre_option_' . $key ) || has_filter( 'default_option_' . $key ) || has_filter( 'pre_update_option_' . $key ) ) { return 'An option filter customizes this configuration. Use full settings; the filtered engine is unchanged.'; }
		}
		foreach ( array( 'pre_option', 'woocommerce_currency', 'woocommerce_currencies', 'woocommerce_gateway_' . $g . '_settings' ) as $hook ) { if ( has_filter( $hook ) ) { return 'A currency or settings integration customizes the native values. Use full settings.'; } }
		if ( ! self::global_update_filter( $g )['safe'] ) { return 'An unqualified global settings filter needs full settings. No callback was invoked or removed by this task.'; }
		if ( ! in_array( $c['gateways'][ $g ]['enabled'], array( 'yes', 'no' ), true ) ) { return 'The WooCommerce method enabled flag is nonstandard. Use its native settings.'; }
		$settings = self::raw( $c, 'woocommerce_' . $g . '_settings' );
		if ( null !== $settings ) {
			$settings = is_serialized( $settings ) ? unserialize( $settings, array( 'allowed_classes' => false ) ) : null;
			if ( ! is_array( $settings ) || isset( $settings['enabled'] ) && ( ! in_array( $settings['enabled'], array( 'yes', 'no' ), true ) || $settings['enabled'] !== $c['gateways'][ $g ]['enabled'] ) ) { return 'The saved WooCommerce method configuration is unsupported or changed. Keep your draft and use its native settings.'; }
		}
		$b = self::bounds( $g, $c );
		foreach ( $b as $raw ) { if ( ! self::supported( $raw ) ) { return 'An existing bound is blank, decimal zero or another unsupported raw value. It remains unchanged. Use full settings.'; } }
		if ( ! self::no_limit( $b['min'] ) && ! self::no_limit( $b['max'] ) && strcmp( self::decimal( $b['min'] ), self::decimal( $b['max'] ) ) > 0 ) { return 'The inherited minimum exceeds the maximum. It remains unchanged; review it in full settings.'; }
		return self::storage_reason();
	}
	private static function target( $g, $c, $draft, $undo ) {
		$reason = self::refusal( $g, $c ); if ( $reason ) { self::fail( $reason ); }
		if ( $undo ) {
			$r = self::record( $c );
			if ( ! is_array( $r ) || 'applied' !== ( $r['status'] ?? '' ) || get_current_user_id() !== ( $r['user'] ?? 0 ) || $g !== ( $r['gateway'] ?? '' ) || ! isset( $r['after_guard'], $r['before'], $r['after'] ) || ! is_string( $r['after_guard'] ) || ! hash_equals( self::hash( $g, $c, false ), $r['after_guard'] ) || ! self::bounds_shape( $r['before'] ) || $r['after'] !== self::bounds( $g, $c ) ) { self::fail( 'Undo no longer matches your saved change. It will not overwrite newer settings. Discard and load current values.', 409 ); }
			return $r['before'];
		}
		if ( ! is_array( $draft ) || count( $draft ) !== 2 || ! array_key_exists( 'min', $draft ) || ! array_key_exists( 'max', $draft ) ) { self::fail( 'Choose No limit or a positive amount for each bound.' ); }
		$before = self::bounds( $g, $c ); $target = array();
		foreach ( array( 'min', 'max' ) as $side ) {
			$v = $draft[ $side ];
			if ( ! is_string( $v ) || ( '0' !== $v && null === self::decimal( $v ) ) ) { self::fail( 'Use No limit, or a positive amount up to 999999999.999999 with at most six decimals. Do not use decimal zero, signs, commas or exponents.' ); }
			$target[ $side ] = self::equivalent( $before[ $side ], $v ) ? $before[ $side ] : $v;
		}
		if ( ! self::no_limit( $target['min'] ) && ! self::no_limit( $target['max'] ) && strcmp( self::decimal( $target['min'] ), self::decimal( $target['max'] ) ) > 0 ) { self::fail( 'Minimum must not exceed maximum. Nothing was changed.' ); }
		return $target;
	}
	private static function bounds_shape( $b ) { return is_array( $b ) && count( $b ) === 2 && array_key_exists( 'min', $b ) && array_key_exists( 'max', $b ) && self::supported( $b['min'] ) && self::supported( $b['max'] ); }
	public static function label( $v ) { return null === $v ? 'No limit (not stored)' : ( '0' === $v ? 'No limit (0)' : ( '' === $v ? 'Blank (native-only)' : $v ) ); }
	private static function lines( $before, $after ) {
		$out = array(); foreach ( array( 'min' => 'Minimum', 'max' => 'Maximum' ) as $key => $label ) { if ( $before[ $key ] !== $after[ $key ] ) { $out[] = $label . ': ' . self::label( $before[ $key ] ) . ' -> ' . self::label( $after[ $key ] ); } } return $out;
	}
	private static function basis( $c ) {
		$tax = self::raw( $c, 'wcj_payment_gateways_min_max_include_tax', 'no' ); $shipping = self::raw( $c, 'wcj_payment_gateways_min_max_exclude_shipping', 'no' );
		if ( ! in_array( $tax, array( 'yes', 'no' ), true ) || ! in_array( $shipping, array( 'yes', 'no' ), true ) ) { return 'Unsupported native amount-basis flags. Use full settings; this shortcut does not interpret them.'; }
		return 'Cart contents total' . ( 'yes' === $tax ? ' + total tax' : ' (tax excluded)' ) . ( 'yes' === $shipping ? ' (shipping excluded)' : ' + shipping total' ) . '. This is not the final order or payable total.';
	}
	private static function status( $g, $c ) {
		$module = self::raw( $c, self::MODULE, 'no' ); $method = $c['gateways'][ $g ]['enabled'];
		$module_text = 'yes' === $module ? 'Amount module on.' : ( 'no' === $module ? 'Amount module off: this configuration is not active through this module.' : 'Amount module state is unsupported.' );
		$method_text = 'yes' === $method ? 'WooCommerce method enabled.' : ( 'no' === $method ? 'WooCommerce method off: this save does not enable it.' : 'WooCommerce method state is unsupported.' );
		return $module_text . ' ' . $method_text;
	}
	private static function consequence( $g, $c, $target ) {
		$active = 'yes' === self::raw( $c, self::MODULE, 'no' ) && 'yes' === $c['gateways'][ $g ]['enabled'];
		$conditions = array();
		if ( ! self::no_limit( $target['min'] ) ) { $conditions[] = 'below ' . $target['min']; }
		if ( ! self::no_limit( $target['max'] ) ) { $conditions[] = 'above ' . $target['max']; }
		return self::status( $g, $c ) . ' ' . ( $conditions ? ( $active ? 'This amount rule can remove this method ' : 'If the module and method are enabled, this amount rule can remove this method ' ) . implode( ' or ', $conditions ) . ' in ' . self::raw( $c, 'woocommerce_currency', 'the store currency' ) . '. At an exact threshold this rule does not remove it.' : 'Neither amount bound removes this method. Other restrictions remain unchanged.' ) . ' Other rules and integrations still apply. If no other method is available, shoppers may be unable to complete checkout. This is saved configuration, not availability or payment-processing proof.';
	}
	private static function signature( $g, $guard, $target, $undo, $expires ) { return hash_hmac( 'sha256', serialize( array( get_current_user_id(), $g, $guard, $target, $undo, $expires ) ), wp_salt( 'nonce' ) ); }
	public static function review( $g, $guard, $draft, $undo = false ) {
		self::authorize(); self::gateway( $g ); $c = self::context( $g );
		if ( ! hash_equals( self::hash( $g, $c ), $guard ) ) { self::fail( 'Settings changed. Your draft is parked. Discard and load current values before saving.', 409 ); }
		$target = self::target( $g, $c, $draft, $undo ); $before = self::bounds( $g, $c );
		if ( $target === $before ) { self::fail( 'No amounts changed. Nothing to save.' ); }
		$expires = time() + 300;
		return array( 'narrowing' => self::narrowing( $before, $target ), 'expires' => $expires, 'token' => self::signature( $g, $guard, $target, $undo, $expires ), 'summary' => self::consequence( $g, $c, $target ), 'changes' => self::lines( $before, $target ) );
	}
	public static function load( $g ) {
		self::authorize(); self::gateway( $g ); $c = self::context( $g ); return self::view( $g, $c );
	}
	private static function view( $g, $c ) {
		$b = self::bounds( $g, $c ); $r = self::record( $c ); $receipt = null;
		if ( is_array( $r ) && $g === ( $r['gateway'] ?? '' ) && get_current_user_id() === ( $r['user'] ?? 0 ) && self::bounds_shape( $r['before'] ?? null ) && self::bounds_shape( $r['after'] ?? null ) && isset( $r['after_guard'], $r['summary'], $r['status'] ) ) {
			$receipt = array( 'status' => $r['status'], 'changes' => self::lines( $r['before'], $r['after'] ), 'summary' => $r['summary'], 'undo' => 'applied' === $r['status'] && hash_equals( self::hash( $g, $c, false ), $r['after_guard'] ), 'before' => $r['before'], 'current' => self::status( $g, $c ) );
		}
		return array( 'gateway' => $g, 'title' => $c['gateways'][ $g ]['title'], 'guard' => self::hash( $g, $c ), 'bounds' => $b, 'draft' => array( 'min' => self::no_limit( $b['min'] ) ? '0' : $b['min'], 'max' => self::no_limit( $b['max'] ) ? '0' : $b['max'] ), 'reason' => self::refusal( $g, $c ), 'status' => self::status( $g, $c ), 'summary' => self::consequence( $g, $c, $b ), 'currency' => self::raw( $c, 'woocommerce_currency', 'unknown' ), 'basis' => self::basis( $c ), 'conversion' => 'Multicurrency compatibility: ' . self::raw( $c, 'wcj_payment_gateways_min_max_comp_mc', 'no' ) . '; Multicurrency module: ' . self::raw( $c, 'wcj_multicurrency_enabled', 'no' ) . '. Configuration does not prove conversion currently runs.', 'custom' => 0 === strpos( $g, 'jetpack_custom_gateway' ), 'receipt' => $receipt, 'native' => self::native_url(), 'woo' => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $g ) );
	}
	/** CAS under transaction locks preserves existing autoload and exact absent preimages. */
	private static function write( $c, $key, $raw, $receipt = false ) {
		global $wpdb;
		$before = self::raw( $c, $key ); if ( $before === $raw ) { return; }
		if ( null === $before ) { $n = $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,%s)", $key, $raw, $receipt ? 'no' : 'yes' ) ); }
		elseif ( null === $raw ) { $n = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name=%s AND BINARY option_value=%s AND BINARY autoload=%s", $key, $before, $c['rows'][ $key ]['autoload'] ) ); }
		else { $n = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND BINARY option_value=%s AND BINARY autoload=%s", $raw, $key, $before, $c['rows'][ $key ]['autoload'] ) ); }
		if ( 1 !== $n ) { self::fail( 'A concurrent change or storage error stopped this transaction. No successful save is claimed.', 409 ); }
	}
	private static function clear_cache( $g ) {
		$core = WCJ_Job_Store::core();
		foreach ( array( self::key( $g, 'min' ), self::key( $g, 'max' ), self::RECEIPT ) as $key ) { wp_cache_delete( $key, 'options' ); if ( $core ) { unset( $core->options[ $key ] ); } }
		wp_cache_delete( 'alloptions', 'options' ); wp_cache_delete( 'notoptions', 'options' );
	}
	public static function mutate( $g, $guard, $draft, $undo = false, $review = array() ) {
		global $wpdb;
		self::authorize(); self::gateway( $g ); $reason = self::storage_reason(); if ( $reason ) { self::fail( $reason ); }
		$lock = 'wcj_amount_' . substr( hash( 'sha256', $wpdb->prefix . DB_NAME ), 0, 40 );
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,5)', $lock ) ) ) { self::fail( 'Another amount save is running. Keep your draft and try again.', 409 ); }
		$begun = false;
		try {
			if ( false === $wpdb->query( 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE' ) || false === $wpdb->query( 'START TRANSACTION' ) ) { self::fail( 'A safe transaction could not begin. Nothing was saved.', 500 ); } $begun = true;
			$c = self::context( $g, true ); self::authorize(); self::gateway( $g );
			if ( ! hash_equals( self::hash( $g, $c ), $guard ) ) { self::fail( 'Settings changed. Your draft is parked. Discard and load current values before saving.', 409 ); }
			$target = self::target( $g, $c, $draft, $undo ); $before = self::bounds( $g, $c );
			if ( $target === $before ) { self::fail( 'No amounts changed. Nothing to save.' ); }
			if ( self::narrowing( $before, $target ) ) {
				$expires = $review['expires'] ?? 0;
				if ( ! is_int( $expires ) || $expires < time() || $expires > time() + 300 || 'yes' !== ( $review['confirm'] ?? '' ) || ! is_string( $review['token'] ?? null ) || ! hash_equals( self::signature( $g, $guard, $target, $undo, $expires ), $review['token'] ) ) { self::fail( 'Review this exact restriction and explicitly confirm before saving.' ); }
			}
			foreach ( $target as $side => $raw ) { self::write( $c, self::key( $g, $side ), $raw ); }
			$after = self::context( $g, true );
			if ( self::bounds( $g, $after ) !== $target ) { self::fail( 'Readback did not match. The transaction will be rolled back.', 500 ); }
			$record = array( 'v' => 1, 'user' => get_current_user_id(), 'gateway' => $g, 'time' => time(), 'status' => $undo ? 'undone' : 'applied', 'before' => $before, 'after' => $target, 'after_guard' => self::hash( $g, $after, false ), 'summary' => self::consequence( $g, $after, $target ) );
			$record['signature'] = hash_hmac( 'sha256', serialize( $record ), wp_salt( 'nonce' ) );
			self::write( $c, self::RECEIPT, serialize( $record ), true );
			$check = self::context( $g, true );
			if ( self::raw( $check, self::RECEIPT ) !== serialize( $record ) || self::hash( $g, $check, false ) !== $record['after_guard'] ) { self::fail( 'Receipt readback failed. The transaction will be rolled back.', 500 ); }
			if ( false === $wpdb->query( 'COMMIT' ) ) { self::fail( 'Commit outcome is uncertain. Keep your draft and load current values before another change.', 500 ); } $begun = false;
		} catch ( Throwable $e ) { if ( $begun ) { $wpdb->query( 'ROLLBACK' ); } self::clear_cache( $g ); throw $e; }
		finally { $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); }
		self::clear_cache( $g ); $fresh = self::load( $g );
		if ( $fresh['bounds'] !== $target || ! $fresh['receipt'] || $fresh['receipt']['status'] !== $record['status'] ) { self::fail( 'Post-save state changed or could not be verified. Load current values; no current-state success is claimed.', 409 ); }
		return $fresh;
	}
	private static function json( $key ) {
		if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) || strlen( $_POST[ $key ] ) > 1500 ) { self::fail( 'Invalid amount request.' ); }
		$v = json_decode( wp_unslash( $_POST[ $key ] ), true, 5 ); if ( ! is_array( $v ) ) { self::fail( 'Invalid amount request.' ); } return $v;
	}
	public function handle() {
		try {
			self::authorize(); $op = isset( $_POST['op'] ) && is_string( $_POST['op'] ) ? $_POST['op'] : '';
			$allowed = 'load' === $op ? array( 'action', 'op', 'nonce', 'gateway' ) : array( 'action', 'op', 'nonce', 'gateway', 'guard', 'draft', 'undo', 'review' );
			if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! in_array( $op, self::OPS, true ) || array_diff( array_keys( $_POST ), $allowed ) || 'wcj_gateway_amount_' . $op !== ( $_POST['action'] ?? '' ) || ! check_ajax_referer( 'wcj_gateway_amount_' . $op, 'nonce', false ) ) { self::fail( 'This request is invalid or expired. Keep the draft and reload the page.', 403 ); }
			$g = isset( $_POST['gateway'] ) && is_string( $_POST['gateway'] ) ? wp_unslash( $_POST['gateway'] ) : '';
			if ( 'load' === $op ) { $result = self::load( $g ); }
			else {
				$guard = isset( $_POST['guard'] ) && is_string( $_POST['guard'] ) ? $_POST['guard'] : '';
				$draft = self::json( 'draft' ); $review = self::json( 'review' );
				if ( count( $draft ) !== 2 || ! isset( $draft['min'], $draft['max'] ) || ! is_string( $draft['min'] ) || ! is_string( $draft['max'] ) ) { self::fail( 'Invalid amount draft fields.' ); }
				if ( array_diff( array_keys( $review ), array( 'expires', 'token', 'confirm' ) ) || ! isset( $_POST['undo'] ) || ! in_array( $_POST['undo'], array( 'yes', 'no' ), true ) || ( 'save' === $op && 'no' !== $_POST['undo'] ) || ( 'undo' === $op && 'yes' !== $_POST['undo'] ) ) { self::fail( 'Invalid confirmation request.' ); }
				$undo = 'yes' === $_POST['undo'];
				$result = 'review' === $op ? self::review( $g, $guard, $draft, $undo ) : self::mutate( $g, $guard, $draft, $undo, $review );
			}
			nocache_headers(); wp_send_json_success( $result );
		} catch ( Throwable $e ) {
			$code = in_array( $e->getCode(), array( 403, 409, 422 ), true ) ? $e->getCode() : 500;
			nocache_headers(); wp_send_json_error( array( 'message' => 500 === $code ? 'The outcome could not be verified. Your draft is parked. Discard and load current values before another change.' : $e->getMessage(), 'kind' => 422 === $code ? 'validation' : 'parked' ), $code );
		}
	}
	public function render() {
		self::authorize();
		wp_enqueue_style( 'wcj-gateway-amount-job', wcj_plugin_url() . '/assets/css/admin/wcj-gateway-amount-job.css', array(), '1.0' );
		wp_enqueue_script( 'wcj-gateway-amount-job', wcj_plugin_url() . '/assets/js/admin/wcj-gateway-amount-job.js', array(), '1.0', true );
		$nonces = array(); foreach ( self::OPS as $op ) { $nonces[ $op ] = wp_create_nonce( 'wcj_gateway_amount_' . $op ); }
		wp_localize_script( 'wcj-gateway-amount-job', 'wcjGatewayAmount', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonces' => $nonces, 'storage' => 'wcj-amount-' . get_current_blog_id() . '-' . get_current_user_id() ) );
		include __DIR__ . '/views/gateway-amount-job.php';
	}
}
new WCJ_Gateway_Amount_Job();
