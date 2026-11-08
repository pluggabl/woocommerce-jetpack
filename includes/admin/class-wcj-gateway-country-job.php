<?php
/**
 * One payment method's country rule. Native evaluation, scoped raw writes and conditional group recovery.
 * @package Booster_For_WooCommerce/admin
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/class-wcj-job-store.php';
if ( ! class_exists( 'WCJ_Gateway_Country_Job' ) ) :
class WCJ_Gateway_Country_Job {

	const MODULE = 'wcj_payment_gateways_by_country_enabled';
	const ID = 'payment_gateways_by_country';
	const RECEIPT = 'booster_gateway_country_job_receipt';
	const LOCK = 'booster_gateway_country_job_lock';
	const OPS = array( 'load', 'preview', 'apply', 'recover', 'resolve' );
	const LOCATION = array( 'countries_include_', 'countries_exclude_', 'states_include_', 'states_exclude_', 'postcodes_include_', 'postcodes_exclude_' );
	const OTHERS = array(
		'payment_gateways_per_category' => array( 'wcj_gateways_per_category_', 'wcj_gateways_per_category_excl_', 'wcj_gateways_per_products_', 'wcj_gateways_per_products_excl_' ),
		'payment_gateways_by_user_role' => array( 'wcj_gateways_user_roles_include_', 'wcj_gateways_user_roles_exclude_' ),
		'payment_gateways_by_currency' => array( 'wcj_gateways_by_currency_allowed_', 'wcj_gateways_by_currency_denied_' ),
		'payment_gateways_min_max' => array( 'wcj_payment_gateways_min_', 'wcj_payment_gateways_max_' ),
		'payment_gateways_by_shipping' => array( 'wcj_gateways_by_shipping_enable_', 'wcj_gateways_by_shipping_enable_instance_' ),
	);

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
		foreach ( self::OPS as $op ) { add_action( 'wp_ajax_wcj_gateway_country_job_' . $op, array( $this, 'handle' ) ); }
	}
	public function register_page() {
		if ( ! WCJ_Task_Finder::allowed( 'payment_gateways_by_country' ) ) { return; }
		add_submenu_page( 'woocommerce', 'Payment method by country', 'Payment method by country', 'manage_woocommerce', 'wcj-gateway-country-job', array( $this, 'render' ) );
	}
	public static function native_url() { return admin_url( wcj_admin_tab_url() . '&wcj-cat=payment_gateways&section=payment_gateways_by_country' ); }
	private static function authorize() {
		if ( ! WCJ_Task_Finder::allowed( 'payment_gateways_by_country' ) ) { throw new RuntimeException( 'You need permission to manage WooCommerce.' ); }
	}
	private static function elite() { return defined( 'WCJ_PLUGIN_FILE' ) && 'booster-elite-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ); }
	private static function gateways() {
		$out = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $id => $gateway ) {
			if ( is_string( $id ) && preg_match( '/^[a-z0-9_\-]{1,64}$/D', $id ) ) {
				$out[ $id ] = array( 'title' => wp_strip_all_tags( (string) wcj_get_payment_gateway_admin_title( $gateway, $id ) ), 'enabled' => 'yes' === $gateway->enabled, 'class' => get_class( $gateway ) );
			}
		}
		return $out;
	}
	private static function gateway() {
		$g = isset( $_POST['gateway'] ) && is_string( $_POST['gateway'] ) ? wp_unslash( $_POST['gateway'] ) : 'bacs';
		if ( ! preg_match( '/^[a-z0-9_\-]{1,64}$/D', $g ) || ! isset( self::gateways()[ $g ] ) ) { throw new RuntimeException( 'Choose a registered WooCommerce payment method.' ); }
		if ( ! self::elite() && 'bacs' !== $g ) { throw new RuntimeException( 'This guided job supports bank transfer in Free and Plus. Existing Plus native payment-method settings remain available in All country settings.' ); }
		return $g;
	}
	private static function json( $key ) {
		$raw = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '{}';
		if ( strlen( $raw ) > 10000 ) { throw new RuntimeException( 'The request is too large.' ); }
		$v = json_decode( $raw, true, 4 );
		if ( ! is_array( $v ) ) { throw new RuntimeException( 'The request could not be read.' ); }
		return $v;
	}
	private static function snapshot( $g ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name = %s ORDER BY option_name", $wpdb->esc_like( 'wcj_' ) . '%', 'woocommerce_' . $g . '_settings' ), ARRAY_A );
		if ( ! is_array( $rows ) || '' !== (string) $wpdb->last_error ) { throw new RuntimeException( 'The saved settings could not be read. Nothing was changed.' ); }
		$out = array(); foreach ( $rows as $r ) { $out[ $r['option_name'] ] = (string) $r['option_value']; } return $out;
	}
	private static function state( $s, $key ) { return array( 'exists' => array_key_exists( $key, $s ), 'raw' => isset( $s[ $key ] ) ? $s[ $key ] : '' ); }
	private static function value( $s, $key, $default = '' ) { return array_key_exists( $key, $s ) ? maybe_unserialize( $s[ $key ] ) : $default; }
	private static function keys( $g, $s, $activation ) {
		$keys = array( self::MODULE, 'wcj_modules_by_user_roles_enabled', 'woocommerce_' . $g . '_settings', 'wcj_gateways_by_location_country_type', 'wcj_gateways_by_location_state_type', 'wcj_gateways_by_location_postcodes_type' );
		foreach ( self::LOCATION as $p ) { $keys[] = 'wcj_gateways_' . $p . $g; }
		foreach ( self::OTHERS as $module => $prefixes ) {
			$keys[] = 'wcj_' . $module . '_enabled';
			foreach ( $prefixes as $p ) { $keys[] = $p . $g; }
		}
		foreach ( $s as $key => $v ) {
			if ( 0 === strpos( $key, 'wcj_modules_by_user_roles_' ) || 0 === strpos( $key, 'wcj_payment_gateways_' ) || ( $activation && preg_match( '/^wcj_gateways_(countries|states|postcodes)_(include|exclude)_/', $key ) ) ) { $keys[] = $key; }
		}
		return array_values( array_unique( $keys ) );
	}
	private static function guard( $g, $s, $omit = array(), $activation = false ) {
		$out = array(); foreach ( self::keys( $g, $s, $activation ) as $key ) { if ( ! in_array( $key, $omit, true ) ) { $out[ $key ] = self::state( $s, $key ); } }
		ksort( $out );
		return hash( 'sha256', serialize( array( $out, self::gateways(), function_exists( 'wcj_get_enabled_module_allowlist' ) ? wcj_get_enabled_module_allowlist() : null, wcj_get_countries() ) ) );
	}
	/** Decode source entities once; every consumer still renders these labels as plain text. */
	private static function country_labels() {
		static $labels = null;
		if ( null === $labels ) {
			$labels = array_map( static function ( $label ) { return html_entity_decode( (string) $label, ENT_QUOTES | ENT_HTML5, 'UTF-8' ); }, wcj_get_countries() );
		}
		return $labels;
	}
	private static function country_list( $s, $key ) {
		$v = self::value( $s, $key );
		if ( '' === $v ) { return array(); }
		if ( ! is_array( $v ) || array_values( $v ) !== $v || count( array_unique( $v, SORT_REGULAR ) ) !== count( $v ) ) { return null; }
		$countries = wcj_get_countries();
		foreach ( $v as $c ) { if ( ! is_string( $c ) || ! isset( $countries[ $c ] ) ) { return null; } }
		return $v;
	}
	private static function module() { $core = WCJ_Job_Store::core(); return isset( $core->all_modules[ self::ID ] ) ? $core->all_modules[ self::ID ] : null; }
	private static function admission( $g, $s ) {
		if ( ! self::module() || ! method_exists( self::module(), 'explain_payment_gateway' ) ) { return 'The native country check is unavailable. Use All country settings.'; }
		foreach ( self::keys( $g, $s, true ) as $key ) {
			if ( has_filter( $key ) || has_filter( 'option_' . $key ) || has_filter( 'pre_option_' . $key ) ) { return 'An option filter customizes these settings. Use All country settings.'; }
		}
		foreach ( array( 'country', 'state', 'postcodes' ) as $type ) {
			if ( ! in_array( self::value( $s, 'wcj_gateways_by_location_' . $type . '_type', 'billing' ), array( 'billing', 'shipping' ), true ) ) { return 'Country by IP or an unknown location source needs All country settings.'; }
		}
		$in = self::country_list( $s, 'wcj_gateways_countries_include_' . $g ); $ex = self::country_list( $s, 'wcj_gateways_countries_exclude_' . $g );
		if ( null === $in || null === $ex ) { return 'A saved country list has an unsupported format. It is preserved; use All country settings.'; }
		if ( $in && $ex ) { return 'Both country lists are filled. They are preserved; use All country settings.'; }
		if ( in_array( 'EU', $ex, true ) ) { return 'The saved EU exclusion does not expand to member countries. Use All country settings.'; }
		foreach ( array_slice( self::LOCATION, 2 ) as $p ) { if ( ! empty( self::value( $s, 'wcj_gateways_' . $p . $g ) ) ) { return 'This method has state or postcode restrictions. Use All country settings.'; } }
		if ( function_exists( 'wcj_is_module_allowed_by_allowlist' ) && ! wcj_is_module_allowed_by_allowlist( self::ID ) ) { return 'The country module is outside this site\'s module allowlist.'; }
		if ( 'yes' === self::value( $s, 'wcj_modules_by_user_roles_enabled' ) ) { return 'Modules by User Roles needs the native settings; this job cannot promise one shared country rule.'; }
		foreach ( self::OTHERS as $module => $prefixes ) {
			if ( 'yes' === self::value( $s, 'wcj_' . $module . '_enabled' ) ) {
				foreach ( $prefixes as $p ) { if ( ! empty( self::value( $s, $p . $g ) ) ) { return 'Another active payment restriction affects this method. Use All country settings.'; } }
			}
		}
		return '';
	}
	private static function other_rows( $g, $s ) {
		$n = 0; foreach ( $s as $key => $raw ) {
			if ( preg_match( '/^wcj_gateways_(countries|states|postcodes)_(include|exclude)_(.+)$/D', $key, $m ) && $g !== $m[3] && ! empty( maybe_unserialize( $raw ) ) ) { ++$n; }
		} return $n;
	}
	public static function load() {
		self::authorize(); $g = self::gateway(); $s = self::snapshot( $g ); return self::view( $g, $s );
	}
	private static function view( $g, $s ) {
		$in = self::country_list( $s, 'wcj_gateways_countries_include_' . $g ); $ex = self::country_list( $s, 'wcj_gateways_countries_exclude_' . $g );
		$gateways = self::gateways(); if ( ! self::elite() ) { $gateways = array_intersect_key( $gateways, array( 'bacs' => true ) ); }
		$r = get_option( self::RECEIPT, null );
		return array( 'gateway' => $g, 'gateways' => $gateways, 'lite' => ! self::elite(), 'saved' => array( 'mode' => $ex ? 'exclude' : 'include', 'countries' => $ex ? $ex : ( $in ?: array() ), 'module' => 'yes' === self::value( $s, self::MODULE ) ), 'source' => self::value( $s, 'wcj_gateways_by_location_country_type', 'billing' ), 'lock' => self::admission( $g, $s ), 'activation_rows' => self::other_rows( $g, $s ), 'base' => self::guard( $g, $s, array(), 'yes' !== self::value( $s, self::MODULE ) ), 'close_base' => self::guard( $g, $s, array(), true ), 'pending_elsewhere' => is_array( $r ) && isset( $r['gateway'], $r['status'] ) && $g !== $r['gateway'] && 'pending' === $r['status'], 'receipt' => self::receipt_view( $g, $s, $r ) );
	}
	private static function check( $g, $s, $draft ) {
		$lock = self::admission( $g, $s ); if ( $lock ) { throw new RuntimeException( $lock ); }
		if ( array_diff( array_keys( $draft ), array( 'mode', 'countries', 'activate' ) ) || ! isset( $draft['mode'], $draft['countries'] ) || ! in_array( $draft['mode'], array( 'include', 'exclude' ), true ) || ! is_array( $draft['countries'] ) || array_values( $draft['countries'] ) !== $draft['countries'] || ( isset( $draft['activate'] ) && ! is_bool( $draft['activate'] ) ) ) { throw new RuntimeException( 'Choose one country mode and a list of countries.' ); }
		$codes = $draft['countries']; $table = wcj_get_countries();
		if ( count( array_unique( $codes, SORT_REGULAR ) ) !== count( $codes ) ) { throw new RuntimeException( 'Choose each country only once.' ); }
		foreach ( $codes as $c ) { if ( ! is_string( $c ) || ! isset( $table[ $c ] ) || ( 'exclude' === $draft['mode'] && 'EU' === $c ) ) { throw new RuntimeException( 'Choose valid countries. European Union is supported only in Only mode.' ); } }
		$activate = ! empty( $draft['activate'] ) && 'yes' !== self::value( $s, self::MODULE );
		if ( $activate && self::other_rows( $g, $s ) ) { throw new RuntimeException( 'Turning on this shared module would activate other methods\' saved location rows. Use All country settings.' ); }
		// Fill the target list before clearing the old list. The native empty value is an empty string.
		$opposite = 'include' === $draft['mode'] ? 'exclude' : 'include';
		$values = array( 'wcj_gateways_countries_' . $draft['mode'] . '_' . $g => $codes ?: '', 'wcj_gateways_countries_' . $opposite . '_' . $g => '' );
		if ( $activate ) { $values[ self::MODULE ] = 'yes'; }
		$entries = array();
		foreach ( $values as $key => $v ) {
			$before = self::state( $s, $key ); $after = WCJ_Job_Store::value_state( $v );
			// Empty absent fields need no row. Existing rows retain their native empty format.
			if ( ( ! $before['exists'] && '' === $v ) || $before['raw'] === $after['raw'] ) { continue; }
			$entries[ $key ] = array( 'before' => $before, 'after' => $after );
		}
		return array( 'entries' => $entries, 'activate' => $activate, 'values' => $values );
	}
	/** Overlay only the selected two native option hooks, restore exact cache and remove hooks even on throw. */
	private static function evaluate( $g, $s, $values, $country, $tag = 'known' ) {
		$core = WCJ_Job_Store::core(); $cache = $core->options; $filters = array(); $original = array();
		try {
			foreach ( array( 'include', 'exclude' ) as $mode ) { $key = 'wcj_gateways_countries_' . $mode . '_' . $g; $original[ $key ] = wcj_get_option( $key, '' ); }
			foreach ( self::keys( $g, $s, false ) as $key ) { $core->options[ $key ] = self::value( $s, $key, 0 === strpos( $key, 'wcj_gateways_by_location_' ) ? 'billing' : '' ); }
			foreach ( array( 'include', 'exclude' ) as $mode ) {
				$key = 'wcj_gateways_countries_' . $mode . '_' . $g;
				$v = array_key_exists( $key, $values ) ? $values[ $key ] : self::value( $s, $key );
				$filters[ $key ] = static function () use ( $v ) { return $v; };
				add_filter( $key, $filters[ $key ], PHP_INT_MAX );
			}
			$address = array( 'country' => array( 'state' => $tag, 'value' => $country ), 'state' => array( 'state' => 'known_empty', 'value' => '' ), 'postcode' => array( 'state' => 'known_empty', 'value' => '' ) );
			return self::module()->explain_payment_gateway( $g, array( 'billing' => $address, 'shipping' => $address ) );
		} finally {
			foreach ( $filters as $key => $callback ) { remove_filter( $key, $callback, PHP_INT_MAX ); }
			$core->options = $cache;
			try { foreach ( $filters as $key => $callback ) { if ( has_filter( $key ) || wcj_get_option( $key, '' ) !== $original[ $key ] ) { throw new RuntimeException( 'The isolated country check did not close cleanly. No settings were changed.' ); } } }
			finally { $core->options = $cache; }
		}
	}
	public static function preview() {
		self::authorize(); $g = self::gateway(); $s = self::snapshot( $g ); $draft = self::json( 'draft' ); $check = self::check( $g, $s, $draft );
		$changes = self::rows( $check['entries'] ); $samples = array_values( array_diff( wcj_maybe_add_european_union_countries( $draft['countries'] ), array( 'EU' ) ) );
		$base = WC()->countries->get_base_country(); if ( $base ) { $samples[] = $base; }
		foreach ( array( 'US', 'CA', 'GB', 'DE' ) as $other ) { if ( ! in_array( $other, $samples, true ) ) { $samples[] = $other; break; } }
		$samples = array_values( array_unique( $samples ) ); $examples = array(); $table = self::country_labels();
		foreach ( $samples as $c ) { $examples[] = array( 'country' => $table[ $c ], 'code' => $c, 'now' => self::evaluate( $g, $s, array(), $c ), 'after' => self::evaluate( $g, $s, $check['values'], $c ) ); }
		foreach ( array( 'known_empty', 'unknown' ) as $tag ) { $examples[] = array( 'country' => 'known_empty' === $tag ? 'Country supplied empty' : 'No country known yet', 'code' => '', 'now' => self::evaluate( $g, $s, array(), '', $tag ), 'after' => self::evaluate( $g, $s, $check['values'], '', $tag ) ); }
		return array( 'changes' => $changes, 'samples' => $examples, 'on_now' => 'yes' === self::value( $s, self::MODULE ), 'on_after' => 'yes' === self::value( $s, self::MODULE ) || $check['activate'], 'enabled' => self::gateways()[ $g ]['enabled'] );
	}
	private static function rows( $entries ) {
		$out = array(); $table = self::country_labels();
		foreach ( $entries as $key => $entry ) {
			$label = self::MODULE === $key ? 'Country module' : ( false !== strpos( $key, '_include_' ) ? 'Only in these countries' : 'Except these countries' );
			$display = static function ( $state ) use ( $key, $table ) { $v = WCJ_Job_Store::state_value( $state ); if ( self::MODULE === $key ) { return 'yes' === $v ? 'On' : 'Off'; } return is_array( $v ) && $v ? implode( ', ', array_map( static function ( $c ) use ( $table ) { return isset( $table[ $c ] ) ? $table[ $c ] : $c; }, $v ) ) : 'No restriction'; };
			$out[] = array( 'label' => $label, 'before' => $display( $entry['before'] ), 'after' => $display( $entry['after'] ) );
		} return $out;
	}
	private static function receipt_view( $g, $s, $r ) {
		if ( ! is_array( $r ) || ! isset( $r['gateway'], $r['entries'], $r['status'] ) || $g !== $r['gateway'] || ! is_array( $r['entries'] ) ) { return null; }
		$rows = self::rows( $r['entries'] ); $i = 0;
		foreach ( $r['entries'] as $key => $entry ) {
			$current = self::state( $s, $key );
			$current_row = self::rows( array( $key => array( 'before' => $current, 'after' => $current ) ) );
			$rows[ $i ]['current'] = $current_row[0]['after'];
			$rows[ $i ]['current_status'] = $current === $entry['after'] ? 'applied' : ( $current === $entry['before'] ? 'unchanged' : 'changed_elsewhere' );
			++$i;
		}
		return array( 'token' => hash( 'sha256', serialize( $r ) ), 'status' => $r['status'], 'rows' => $rows, 'pending' => 'pending' === $r['status'], 'recover' => in_array( $r['status'], array( 'applied', 'pending' ), true ), 'message' => isset( $r['message'] ) ? $r['message'] : '' );
	}
	private static function save_receipt( $r ) {
		update_option( self::RECEIPT, $r, false );
		if ( get_option( self::RECEIPT ) !== $r ) { throw new RuntimeException( 'The change journal could not be saved. No success is claimed; reload.' ); }
	}
	public static function apply() {
		self::authorize(); $g = self::gateway(); $lock = WCJ_Job_Store::lock( self::LOCK, 'Another country change is running. Try again.' );
		try {
			$s = self::snapshot( $g ); $draft = self::json( 'draft' ); $check = self::check( $g, $s, $draft );
			$activation = 'yes' !== self::value( $s, self::MODULE );
			$base = isset( $_POST['base'] ) && is_string( $_POST['base'] ) ? wp_unslash( $_POST['base'] ) : '';
			if ( ! hash_equals( self::guard( $g, $s, array(), $activation ), $base ) ) { throw new RuntimeException( 'Saved country settings or dependencies changed. Nothing was changed. Reload and compare again.' ); }
			if ( ! isset( $_POST['confirm'] ) || '1' !== $_POST['confirm'] ) { throw new RuntimeException( 'Review and confirm the proposed changes before applying.' ); }
			$previous = get_option( self::RECEIPT, null );
			if ( is_array( $previous ) && isset( $previous['status'] ) && 'pending' === $previous['status'] ) { throw new RuntimeException( 'A previous country change needs recovery. Check its payment method before applying another.' ); }
			if ( ! $check['entries'] ) { return array( 'message' => 'These values are already saved.', 'fresh' => self::view( $g, $s ) ); }
			$r = array( 'v' => 1, 'id' => bin2hex( random_bytes( 16 ) ), 'gateway' => $g, 'entries' => $check['entries'], 'activation' => $activation, 'guard' => self::guard( $g, $s, array_keys( $check['entries'] ), $activation ), 'status' => 'pending', 'time' => gmdate( 'c' ), 'message' => 'This change is not complete. Recover before another apply.' );
			self::save_receipt( $r ); $expected = $s;
			foreach ( $check['entries'] as $key => $entry ) {
				if ( self::guard( $g, self::snapshot( $g ), array(), $activation ) !== self::guard( $g, $expected, array(), $activation ) || ! WCJ_Job_Store::compare_exchange( $key, $entry['before'], $entry['after'] ) ) { $r['message'] = 'A concurrent save interrupted this change. Newer values are kept. Recover the group, or check All country settings.'; self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( $g, self::snapshot( $g ) ) ); }
				$expected[ $key ] = $entry['after']['raw'];
			}
			$done = self::snapshot( $g );
			if ( self::guard( $g, $done, array(), $activation ) !== self::guard( $g, $expected, array(), $activation ) ) { return array( 'message' => $r['message'], 'fresh' => self::view( $g, $done ) ); }
			$r['status'] = 'applied'; $r['message'] = 'Saved and read back. Only this method\'s country lists and the explicitly requested activation changed.'; self::save_receipt( $r );
			return array( 'message' => $r['message'], 'fresh' => self::view( $g, $done ) );
		} finally { WCJ_Job_Store::unlock( self::LOCK, $lock ); }
	}
	public static function recover() {
		self::authorize(); $g = self::gateway(); $lock = WCJ_Job_Store::lock( self::LOCK, 'Another country change is running. Try again.' );
		try {
			$r = get_option( self::RECEIPT, null );
			if ( ! is_array( $r ) || ! isset( $r['v'], $r['gateway'], $r['entries'], $r['status'], $r['guard'], $r['activation'] ) || 1 !== $r['v'] || $g !== $r['gateway'] || ! in_array( $r['status'], array( 'applied', 'pending' ), true ) || ! is_array( $r['entries'] ) ) { throw new RuntimeException( 'No current recoverable change for this payment method.' ); }
			self::receipt_token( $r );
			$allowed = array( 'wcj_gateways_countries_include_' . $g, 'wcj_gateways_countries_exclude_' . $g, self::MODULE );
			foreach ( $r['entries'] as $key => $entry ) {
				if ( ! in_array( $key, $allowed, true ) || ! is_array( $entry ) ) { throw new RuntimeException( 'This journal is unsupported; check All country settings.' ); }
				foreach ( array( 'before', 'after' ) as $side ) { if ( ! isset( $entry[ $side ]['exists'], $entry[ $side ]['raw'] ) || ! is_bool( $entry[ $side ]['exists'] ) || ! is_string( $entry[ $side ]['raw'] ) ) { throw new RuntimeException( 'This journal is unsupported; check All country settings.' ); } }
			}
			$s = self::snapshot( $g );
			$conflict = self::guard( $g, $s, array_keys( $r['entries'] ), $r['activation'] ) !== $r['guard'];
			foreach ( $r['entries'] as $key => $entry ) { $current = self::state( $s, $key ); if ( $current !== $entry['after'] && $current !== $entry['before'] ) { $conflict = true; } }
			if ( $conflict ) { $r['status'] = 'pending'; $r['message'] = 'Recovery stopped before changing any row: newer settings or dependencies must be checked in All country settings. No newer value was overwritten.'; self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( $g, $s ) ); }
			// Reverse fail-closed write order as a guarded group. Do not clear one side if another side has newer data.
			$expected = $s;
			foreach ( array_reverse( $r['entries'], true ) as $key => $entry ) {
				if ( self::guard( $g, self::snapshot( $g ), array(), $r['activation'] ) !== self::guard( $g, $expected, array(), $r['activation'] ) ) { $r['status'] = 'pending'; $r['message'] = 'A native save interrupted recovery. Newer values are kept; check All country settings.'; self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( $g, self::snapshot( $g ) ) ); }
				$current = self::state( $expected, $key );
				if ( $current !== $entry['before'] && ! WCJ_Job_Store::compare_exchange( $key, $entry['after'], $entry['before'] ) ) { $r['status'] = 'pending'; $r['message'] = 'A newer value stopped recovery. Check All country settings.'; self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( $g, self::snapshot( $g ) ) ); }
				if ( $entry['before']['exists'] ) { $expected[ $key ] = $entry['before']['raw']; } else { unset( $expected[ $key ] ); }
			}
			$done = self::snapshot( $g );
			if ( self::guard( $g, $done, array(), $r['activation'] ) !== self::guard( $g, $expected, array(), $r['activation'] ) ) { $r['status'] = 'pending'; $r['message'] = 'A newer save arrived at final recovery readback. No complete recovery is claimed.'; }
			else { $r['status'] = 'recovered'; $r['message'] = 'Previous raw country settings restored and read back, including absent rows. Newer unrelated settings were not changed.'; }
			self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( $g, $done ) );
		} finally { WCJ_Job_Store::unlock( self::LOCK, $lock ); }
	}
	private static function receipt_token( $r ) {
		$token = isset( $_POST['receipt'] ) && is_string( $_POST['receipt'] ) ? wp_unslash( $_POST['receipt'] ) : '';
		if ( ! hash_equals( hash( 'sha256', serialize( $r ) ), $token ) ) { throw new RuntimeException( 'The last change is different from the one displayed. Nothing was recovered or closed. Reload and inspect it.' ); }
	}
	/** Explicit acknowledgment closes only this displayed pending journal. No country configuration is written. */
	public static function resolve() {
		self::authorize(); $g = self::gateway(); $lock = WCJ_Job_Store::lock( self::LOCK, 'Another country change is running. Try again.' );
		try {
			$r = get_option( self::RECEIPT, null );
			if ( ! is_array( $r ) || ! isset( $r['gateway'], $r['status'] ) || $g !== $r['gateway'] || 'pending' !== $r['status'] ) { throw new RuntimeException( 'There is no pending change for this admitted payment method.' ); }
			self::receipt_token( $r ); $s = self::snapshot( $g );
			$base = isset( $_POST['close_base'] ) && is_string( $_POST['close_base'] ) ? wp_unslash( $_POST['close_base'] ) : '';
			if ( ! isset( $_POST['confirm'] ) || '1' !== $_POST['confirm'] || ! hash_equals( self::guard( $g, $s, array(), true ), $base ) ) { throw new RuntimeException( 'Reload and check the current settings before closing this pending change. Nothing was changed.' ); }
			$r['status'] = 'checked'; $r['checked'] = gmdate( 'c' ); $r['message'] = 'Current settings kept. This previous change was closed by your confirmation; no country setting was written.';
			self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( $g, $s ) );
		} finally { WCJ_Job_Store::unlock( self::LOCK, $lock ); }
	}
	public function handle() {
		if ( ! WCJ_Task_Finder::allowed( 'payment_gateways_by_country' ) ) { wp_send_json_error( array( 'error' => esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ) ), 403 ); }
		try {
			self::authorize(); $action = isset( $_POST['action'] ) && is_string( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : ''; $op = str_replace( 'wcj_gateway_country_job_', '', $action );
			if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! in_array( $op, self::OPS, true ) ) { throw new RuntimeException( 'Invalid country-job request.' ); }
			check_ajax_referer( 'wcj_gateway_country_job_' . $op, 'nonce' ); $result = call_user_func( array( __CLASS__, $op ) ); nocache_headers(); wp_send_json_success( $result );
		} catch ( Throwable $e ) { nocache_headers(); wp_send_json_error( array( 'error' => $e instanceof RuntimeException ? $e->getMessage() : 'The country change could not complete. No success is claimed; reload the saved state.', 'native' => self::native_url() ), 409 ); }
	}
	public function render() {
		if ( ! WCJ_Task_Finder::allowed( 'payment_gateways_by_country' ) ) { wp_die( esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) ); } wp_enqueue_style( 'wcj-gateway-country-job', wcj_plugin_url() . '/assets/css/admin/wcj-gateway-country-job.css', array(), '1.2' );
		wp_enqueue_script( 'wcj-gateway-country-job', wcj_plugin_url() . '/assets/js/admin/wcj-gateway-country-job.js', array(), '1.5', true );
		$nonces = array(); foreach ( self::OPS as $op ) { $nonces[ $op ] = wp_create_nonce( 'wcj_gateway_country_job_' . $op ); }
		wp_localize_script( 'wcj-gateway-country-job', 'wcjGatewayCountryJob', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonces' => $nonces, 'countries' => self::country_labels() ) );
		include __DIR__ . '/views/gateway-country-job.php';
	}
}
endif;
new WCJ_Gateway_Country_Job();
