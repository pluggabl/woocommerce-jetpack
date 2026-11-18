<?php
/** One-product native Date configuration. No global activation or native evaluator replacement.
 *
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-job-store.php';
require_once __DIR__ . '/product-availability-groups.php';
/** Native-backed optional admin workflow and its narrowly scoped helpers. */
final class WCJ_Product_Date_Job {
	const PREFIX = '_wcj_product_by_date_';
	const OPS    = array( 'search', 'load', 'review', 'save', 'undo' );
	/**
	 * Register the optional editor and contextual native help.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register' ), 99 );
		add_action( 'add_meta_boxes_product', array( 'WCJ_Product_Availability_Groups', 'panel_help' ), 101 );
		add_action( 'woocommerce_product_quick_edit_end', array( 'WCJ_Product_Availability_Groups', 'quick_help' ), 100 );
		add_action( 'woocommerce_product_bulk_edit_end', array( 'WCJ_Product_Availability_Groups', 'bulk_help' ), 100 );
		foreach ( self::OPS as $op ) {
			add_action( 'wp_ajax_wcj_product_date_' . $op, array( $this, 'handle' ) ); }
	}
	/**
	 * Return the guided route without a write action.
	 *
	 * @return mixed
	 */
	public static function url() {
		return admin_url( 'admin.php?page=wcj-product-date-job' ); }
	/**
	 * Return the native settings route.
	 *
	 * @return mixed
	 */
	public static function native_url() {
		return WCJ_Product_Availability_Groups::native_url( 'product_by_date' ); }
	/**
	 * Stop this request without claiming a verified save.
	 *
	 * @param mixed $message Message.
	 * @param mixed $code Code.
	 * @return void
	 */
	private static function fail( $message, $code = 422 ) {
		throw new RuntimeException( $message, $code ); }
	/**
	 * Enforce module policy and selected product editing rights.
	 *
	 * @param mixed $id Id.
	 * @return void
	 */
	public static function authorize( $id = 0 ) {
		if ( ! WCJ_Task_Finder::allowed( 'product_by_date' ) ) {
			self::fail( __( 'You need permission for Product Availability by Date.', 'woocommerce-jetpack' ), 403 ); }
		if ( $id && ( 'product' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) || in_array( get_post_status( $id ), array( 'trash', 'auto-draft' ), true ) ) ) {
			self::fail( __( 'Choose a product you can edit.', 'woocommerce-jetpack' ), 403 ); }
	}
	/**
	 * Expose the route only to an admitted owner.
	 *
	 * @return void
	 */
	public function register() {
		if ( WCJ_Task_Finder::allowed( 'product_by_date' ) ) {
			add_submenu_page( 'woocommerce', __( 'Product available from date', 'woocommerce-jetpack' ), __( 'Product available from date', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-product-date-job', array( $this, 'render' ) ); }
	}
	/**
	 * Name the product-scoped saved record.
	 *
	 * @param mixed $id Id.
	 * @return mixed
	 */
	private static function receipt_key( $id ) {
		return 'booster_product_date_job_' . $id; }
	/**
	 * Read exact native configuration, optionally under transaction locks.
	 *
	 * @param mixed $id Id.
	 * @param mixed $lock Lock.
	 * @return mixed
	 */
	private static function context( $id, $lock = false ) {
		global $wpdb;
		$tail    = $lock ? ' FOR UPDATE' : '';
		$options = $wpdb->get_results( $wpdb->prepare( "SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name IN ('timezone_string','gmt_offset') ORDER BY option_name LIMIT 10001" . $tail, $wpdb->esc_like( 'wcj_product_by_date_' ) . '%' ), ARRAY_A );
		$meta    = $wpdb->get_results( $wpdb->prepare( "SELECT m.meta_id,m.post_id,m.meta_key,m.meta_value,p.post_type,p.post_status FROM {$wpdb->postmeta} m LEFT JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE m.meta_key LIKE %s ORDER BY m.meta_id LIMIT 10001" . $tail, $wpdb->esc_like( self::PREFIX ) . '%' ), ARRAY_A );
		if ( $wpdb->last_error || ! is_array( $options ) || ! is_array( $meta ) || count( $options ) > 10000 || count( $meta ) > 10000 ) {
			self::fail( __( 'This configuration needs full settings. Nothing was changed.', 'woocommerce-jetpack' ) ); }
		$selected = array_values(
			array_filter(
				$meta,
				static function ( $r ) use ( $id ) {
					return (int) $r['post_id'] === $id;
				}
			)
		);
		return array(
			'options'  => $options,
			'meta'     => $meta,
			'selected' => $selected,
			'receipt'  => WCJ_Job_Store::read_state( self::receipt_key( $id ) ),
		);
	}
	/**
	 * Read a raw option or its declared fallback.
	 *
	 * @param mixed $c C.
	 * @param mixed $key Key.
	 * @param mixed $fallback Fallback.
	 * @return mixed
	 */
	private static function option( $c, $key, $fallback = '' ) {
		foreach ( $c['options'] as $r ) {
			if ( $key === $r['option_name'] ) {
				return $r['option_value']; }
		}
		return $fallback;
	}
	/**
	 * Select metadata rows for one owned key.
	 *
	 * @param mixed $c C.
	 * @param mixed $key Key.
	 * @return mixed
	 */
	private static function rows( $c, $key ) {
		return array_values(
			array_filter(
				$c['selected'],
				static function ( $r ) use ( $key ) {
					return self::PREFIX . $key === $r['meta_key'];
				}
			)
		); }
	/**
	 * Read a raw value, preserving metadata absence.
	 *
	 * @param mixed $c C.
	 * @param mixed $key Key.
	 * @return mixed
	 */
	private static function raw( $c, $key ) {
		$rows = self::rows( $c, $key );
		return $rows ? $rows[0]['meta_value'] : null; }
	/**
	 * Read the current displayed value without creating storage.
	 *
	 * @param mixed $c C.
	 * @param mixed $key Key.
	 * @return mixed
	 */
	private static function value( $c, $key ) {
		$v = self::raw( $c, $key );
		return null === $v ? ( 'enabled' === $key ? 'no' : '' ) : $v; }
	/**
	 * Bind user, product and exact raw context.
	 *
	 * @param mixed $id Id.
	 * @param mixed $c C.
	 * @param mixed $receipt Receipt.
	 * @return mixed
	 */
	private static function hash( $id, $c, $receipt = true ) {
		if ( ! $receipt ) {
			unset( $c['receipt'] ); }
		return hash_hmac( 'sha256', wp_json_encode( array( get_current_user_id(), $id, $c ) ), wp_salt( 'nonce' ) );
	}
	/**
	 * Decode the local record without constructing objects.
	 *
	 * @param mixed $c C.
	 * @return mixed
	 */
	private static function record( $c ) {
		$r = $c['receipt'];
		return $r['exists'] && is_serialized( $r['raw'] ) ? unserialize( $r['raw'], array( 'allowed_classes' => false ) ) : null;
	}
	/**
	 * Use the same native paid-edition filter as admission.
	 *
	 * @return mixed
	 */
	private static function paid() {
		return true === apply_filters( 'booster_option', false, true ); }
	/**
	 * Read the native direct-date format without changing it.
	 *
	 * @param mixed $c C.
	 * @return mixed
	 */
	private static function format( $c ) {
		return self::option( $c, 'wcj_product_by_date_direct_date_format', 'm/d/Y' ); }
	/**
	 * Strictly validate a supported calendar date and roundtrip.
	 *
	 * @param mixed $value Value.
	 * @param mixed $format Format.
	 * @return mixed
	 */
	private static function valid_date( $value, $format ) {
		if ( ! is_string( $value ) || strlen( $value ) > 32 || ! in_array( $format, array( 'm/d/Y', 'd/m/Y', 'Y-m-d', 'd.m.Y', 'n/j/Y', 'j/n/Y', 'Y/m/d' ), true ) ) {
			return false; }
		if ( '' === $value ) {
			return true; }
		$d = DateTimeImmutable::createFromFormat( '!' . $format, $value, new DateTimeZone( 'UTC' ) );
		$e = DateTimeImmutable::getLastErrors();
		return false !== $d && ( false === $e || ( 0 === $e['warning_count'] && 0 === $e['error_count'] ) ) && $d->format( $format ) === $value && (int) $d->format( 'Y' ) >= 1000;
	}
	/**
	 * Retain custom or duplicate state in the full editor.
	 *
	 * @param mixed $c C.
	 * @return mixed
	 */
	private static function locked( $c ) {
		return count( self::rows( $c, 'enabled' ) ) > 1 || count( self::rows( $c, 'direct_date' ) ) > 1 || ! in_array( self::value( $c, 'enabled' ), array( 'yes', 'no' ), true ) || ! self::valid_date( self::value( $c, 'direct_date' ), self::format( $c ) );
	}
	/**
	 * Inspect the two global gates without activating either.
	 *
	 * @param mixed $c C.
	 * @return mixed
	 */
	private static function active( $c ) {
		return 'yes' === self::option( $c, 'wcj_product_by_date_enabled', 'no' ) && 'yes' === self::option( $c, 'wcj_product_by_date_per_product_enabled', 'no' ); }
	/**
	 * Determine whether this context can affect purchasing.
	 *
	 * @param mixed $c C.
	 * @return mixed
	 */
	private static function consequential( $c ) {
		return self::active( $c ) && 'non_purchasable' === self::option( $c, 'wcj_product_by_date_action', 'non_purchasable' ); }
	/**
	 * Enforce the Free Date slot without exposing another product.
	 *
	 * @param mixed $id Id.
	 * @param mixed $c C.
	 * @param mixed $enable Enable.
	 * @return mixed
	 */
	private static function admission( $id, $c, $enable ) {
		if ( 'yes' !== $enable || self::paid() ) {
			return; }
		foreach ( $c['meta'] as $r ) {
			if ( self::PREFIX . 'enabled' === $r['meta_key'] && 'yes' === $r['meta_value'] && $id !== (int) $r['post_id'] && 'product' === $r['post_type'] && ! in_array( $r['post_status'], array( 'trash', 'auto-draft', 'inherit' ), true ) ) {
				self::fail( __( 'Free supports one enabled per-product Date rule. Another product already uses that slot. Use full settings to review the rules you can access; no other product was changed.', 'woocommerce-jetpack' ) );
			}
		}
	}
	/**
	 * Validate only the two owned product values.
	 *
	 * @param mixed $id Id.
	 * @param mixed $c C.
	 * @param mixed $draft Draft.
	 * @return mixed
	 */
	private static function draft( $id, $c, $draft ) {
		if ( self::locked( $c ) ) {
			self::fail( __( 'Existing custom values or an unsupported date format need full settings. They were not changed.', 'woocommerce-jetpack' ) ); }
		if ( ! is_array( $draft ) || array_diff( array_keys( $draft ), array( 'enabled', 'date' ) ) || ! isset( $draft['enabled'], $draft['date'] ) || ! in_array( $draft['enabled'], array( 'yes', 'no' ), true ) || ! self::valid_date( $draft['date'], self::format( $c ) ) ) {
			self::fail( __( 'Enter a real calendar date exactly in the displayed format, or leave it empty to use monthly rules.', 'woocommerce-jetpack' ) ); }
		self::admission( $id, $c, $draft['enabled'] );
		$target = array();
		foreach ( array(
			'enabled'     => 'enabled',
			'direct_date' => 'date',
		) as $key => $field ) {
			$target[ $key ] = $draft[ $field ] === self::value( $c, $key ) ? self::raw( $c, $key ) : $draft[ $field ];
		}
		return $target;
	}
	/**
	 * Reject malformed records outside the owned metadata scope.
	 *
	 * @param mixed $values Values.
	 * @return mixed
	 */
	private static function owned_shape( $values ) {
		return is_array( $values ) && count( $values ) === 2 && array_key_exists( 'enabled', $values ) && array_key_exists( 'direct_date', $values ) && in_array( $values['enabled'], array( null, 'yes', 'no' ), true ) && ( null === $values['direct_date'] || ( is_string( $values['direct_date'] ) && strlen( $values['direct_date'] ) <= 32 ) );
	}
	/**
	 * Describe exact old and new values with absence qualified.
	 *
	 * @param mixed $before Before.
	 * @param mixed $after After.
	 * @return mixed
	 */
	private static function change_lines( $before, $after ) {
		if ( ! self::owned_shape( $before ) || ! self::owned_shape( $after ) ) {
			return array(); }
		$lines = array();
		foreach ( array(
			'enabled'     => __( 'Product override', 'woocommerce-jetpack' ),
			'direct_date' => __( 'Available from date', 'woocommerce-jetpack' ),
		) as $key => $label ) {
			if ( $before[ $key ] === $after[ $key ] ) {
				continue; }
			$values = array();
			foreach ( array( $before[ $key ], $after[ $key ] ) as $value ) {
				$values[] = null === $value ? ( 'enabled' === $key ? __( 'Not stored (disabled)', 'woocommerce-jetpack' ) : __( 'Not stored (empty)', 'woocommerce-jetpack' ) ) : ( 'enabled' === $key ? ( 'yes' === $value ? __( 'Enabled', 'woocommerce-jetpack' ) : __( 'Disabled', 'woocommerce-jetpack' ) ) : ( '' === $value ? __( 'Empty', 'woocommerce-jetpack' ) : $value ) );
			}
			$lines[] = $label . ': ' . $values[0] . ' -> ' . $values[1];
		}
		return $lines;
	}
	/**
	 * Admit only a matching user-owned saved record.
	 *
	 * @param mixed $id Id.
	 * @param mixed $c C.
	 * @return mixed
	 */
	private static function undo_target( $id, $c ) {
		$r = self::record( $c );
		if ( ! is_array( $r ) || 'applied' !== ( $r['status'] ?? '' ) || get_current_user_id() !== ( $r['user'] ?? 0 ) || ! isset( $r['after_guard'], $r['before'] ) || ! self::owned_shape( $r['before'] ) || ! hash_equals( self::hash( $id, $c, false ), $r['after_guard'] ) ) {
			self::fail( __( 'This saved change no longer matches. Load current values and inspect full settings; Undo will not overwrite another change.', 'woocommerce-jetpack' ), 409 ); }
		self::admission( $id, $c, null === $r['before']['enabled'] ? 'no' : $r['before']['enabled'] );
		return $r['before'];
	}
	/**
	 * Resolve either a validated edit or matching Undo target.
	 *
	 * @param mixed $id Id.
	 * @param mixed $c C.
	 * @param mixed $draft Draft.
	 * @param mixed $undo Undo.
	 * @return mixed
	 */
	private static function target( $id, $c, $draft, $undo ) {
		return $undo ? self::undo_target( $id, $c ) : self::draft( $id, $c, $draft ); }
	/**
	 * Bind a short-lived review to the exact proposed change.
	 *
	 * @param mixed $id Id.
	 * @param mixed $guard Guard.
	 * @param mixed $target Target.
	 * @param mixed $undo Undo.
	 * @param mixed $expires Expires.
	 * @return mixed
	 */
	private static function review_signature( $id, $guard, $target, $undo, $expires ) {
		return hash_hmac( 'sha256', wp_json_encode( array( get_current_user_id(), $id, $guard, $target, $undo, $expires ) ), wp_salt( 'nonce' ) ); }
	/**
	 * Read a fresh consequence review without persistence.
	 *
	 * @param mixed $id Id.
	 * @param mixed $guard Guard.
	 * @param mixed $draft Draft.
	 * @param mixed $undo Undo.
	 * @return mixed
	 */
	public static function review( $id, $guard, $draft, $undo ) {
		self::authorize( $id );
		$c = self::context( $id );
		if ( ! hash_equals( self::hash( $id, $c ), $guard ) ) {
			self::fail( __( 'Settings changed elsewhere. Keep a copy of your draft, then discard it and load current values.', 'woocommerce-jetpack' ), 409 ); }
		$target  = self::target( $id, $c, $draft, $undo );
		$expires = time() + 300;
		return array(
			'expires' => $expires,
			'token'   => self::review_signature( $id, $guard, $target, $undo, $expires ),
			'summary' => self::summary( $c, $target ),
			'changes' => self::change_lines(
				array(
					'enabled'     => self::raw( $c, 'enabled' ),
					'direct_date' => self::raw( $c, 'direct_date' ),
				),
				$target
			),
			'undo'    => $undo,
		);
	}
	/**
	 * Explain configuration and native effect boundaries.
	 *
	 * @param mixed $c C.
	 * @param mixed $target Target.
	 * @return mixed
	 */
	private static function summary( $c, $target ) {
		if ( ! self::active( $c ) ) {
			return __( 'Configuration only: the module or global per-product gate is off. This save does not turn either on.', 'woocommerce-jetpack' ); }
		$message = 'non_purchasable' !== self::option( $c, 'wcj_product_by_date_action', 'non_purchasable' );
		if ( 'yes' === $target['enabled'] && ! empty( $target['direct_date'] ) ) {
			return sprintf( __( 'Direct date: %1$s. It replaces this product\'s monthly schedule while its override is enabled. The native Date rule is available from that date, not only on that date. %2$s Other purchase restrictions still apply. Calendar-only dates start at midnight in the store timezone. This is a configuration review, not a live shopper eligibility test.', 'woocommerce-jetpack' ), $target['direct_date'], $message ? __( 'The current action only outputs a message; it does not prohibit purchase.', 'woocommerce-jetpack' ) : __( 'The current action can prevent purchase before the native date threshold.', 'woocommerce-jetpack' ) );
		}
		return ( $message ? __( 'The current action only outputs messages; it does not prohibit purchase. ', 'woocommerce-jetpack' ) : '' ) . __( 'No active direct date in this proposal. Enabling the product override with an empty date uses its retained monthly schedule; disabling it returns to global monthly rules if enabled. Monthly values, global gates and action are not changed here.', 'woocommerce-jetpack' ) . ( $message ? '' : __( ' With the current non-purchasable action, those monthly rules may allow or prevent purchase.', 'woocommerce-jetpack' ) );
	}
	/**
	 * Load authorized current values and matching receipt state.
	 *
	 * @param mixed $id Id.
	 * @return mixed
	 */
	public static function load( $id ) {
		self::authorize( $id );
		$c       = self::context( $id );
		$r       = self::record( $c );
		$undo    = is_array( $r ) && 'applied' === ( $r['status'] ?? '' ) && get_current_user_id() === ( $r['user'] ?? 0 ) && isset( $r['after_guard'] ) && hash_equals( self::hash( $id, $c, false ), $r['after_guard'] );
		$monthly = array(); for ( $i = 1; $i <= 12; $i++ ) {
			$monthly[ $i ] = self::raw( $c, (string) $i ); }
		return array(
			'id'            => $id,
			'title'         => get_the_title( $id ),
			'guard'         => self::hash( $id, $c ),
			'draft'         => array(
				'enabled' => self::value( $c, 'enabled' ),
				'date'    => self::value( $c, 'direct_date' ),
			),
			'locked'        => self::locked( $c ),
			'format'        => self::format( $c ),
			'timezone'      => wp_timezone_string(),
			'module'        => self::option( $c, 'wcj_product_by_date_enabled', 'no' ),
			'local'         => self::option( $c, 'wcj_product_by_date_per_product_enabled', 'no' ),
			'global'        => self::option( $c, 'wcj_product_by_date_section_enabled', 'no' ),
			'action'        => self::option( $c, 'wcj_product_by_date_action', 'non_purchasable' ),
			'paid'          => self::paid(),
			'consequential' => self::consequential( $c ),
			'undo'          => $undo,
			'saved_record'  => is_array( $r ) && get_current_user_id() === ( $r['user'] ?? 0 ) ? array(
				'status'  => $r['status'],
				'time'    => wp_date( 'Y-m-d H:i:s T', $r['time'] ),
				'changes' => self::change_lines( $r['before'] ?? null, $r['after'] ?? null ),
			) : null,
			'raw'           => array(
				'enabled'     => self::raw( $c, 'enabled' ),
				'direct_date' => self::raw( $c, 'direct_date' ),
				'monthly'     => $monthly,
			),
			'summary'       => self::summary(
				$c,
				array(
					'enabled'     => self::value( $c, 'enabled' ),
					'direct_date' => self::value( $c, 'direct_date' ),
				)
			),
			'native'        => get_edit_post_link( $id, 'raw' ),
			'shopper'       => get_permalink( $id ),
		);
	}
	/**
	 * Require transactional tables and supported cache semantics.
	 *
	 * @return void
	 */
	private static function transactional() {
		global $wpdb;
		if ( wp_using_ext_object_cache() ) {
			self::fail( __( 'Use full settings with persistent object caching. This task cannot safely save in this environment.', 'woocommerce-jetpack' ) ); }
		foreach ( array( $wpdb->options, $wpdb->postmeta, $wpdb->posts ) as $table ) {
			$r = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A );
			if ( ! $r || 'InnoDB' !== $r['Engine'] ) {
				self::fail( __( 'This task needs transactional database tables. Nothing was changed.', 'woocommerce-jetpack' ) ); }
		}
	}
	/**
	 * Conditionally write one owned raw metadata value.
	 *
	 * @param mixed $id Id.
	 * @param mixed $c C.
	 * @param mixed $key Key.
	 * @param mixed $raw Raw.
	 * @return void
	 */
	private static function write_meta( $id, $c, $key, $raw ) {
		global $wpdb;
		$rows = self::rows( $c, $key );
		if ( count( $rows ) > 1 ) {
			self::fail( __( 'Duplicate metadata needs full settings.', 'woocommerce-jetpack' ) ); }
		if ( self::raw( $c, $key ) === $raw ) {
			return; }
		if ( $rows ) {
			$r      = $rows[0];
			$result = null === $raw ? $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_id=%d AND post_id=%d AND meta_key=%s AND BINARY meta_value=%s", $r['meta_id'], $id, self::PREFIX . $key, $r['meta_value'] ) ) : $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value=%s WHERE meta_id=%d AND post_id=%d AND meta_key=%s AND BINARY meta_value=%s", $raw, $r['meta_id'], $id, self::PREFIX . $key, $r['meta_value'] ) ); } else {
			$result = $wpdb->insert(
				$wpdb->postmeta,
				array(
					'post_id'    => $id,
					'meta_key'   => self::PREFIX . $key,
					'meta_value' => $raw,
				),
				array( '%d', '%s', '%s' )
			); }
			if ( 1 !== $result ) {
				self::fail( __( 'Native values changed. Nothing from this save was applied.', 'woocommerce-jetpack' ), 409 ); }
	}
	/**
	 * Invalidate only the selected product and receipt caches.
	 *
	 * @param mixed $id Id.
	 * @return void
	 */
	private static function clear_cache( $id ) {
		wp_cache_delete( $id, 'post_meta' );
		wp_cache_delete( self::receipt_key( $id ), 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' ); }
	/**
	 * Atomically save or undo after admission, raw guard and review checks.
	 *
	 * @param mixed $id Id.
	 * @param mixed $guard Guard.
	 * @param mixed $draft Draft.
	 * @param mixed $undo Undo.
	 * @param mixed $review Review.
	 * @return mixed
	 */
	public static function mutate( $id, $guard, $draft, $undo = false, $review = array() ) {
		global $wpdb;
		self::authorize( $id );
		self::transactional();
		$lock = 'wcj_date_' . substr( hash( 'sha256', $wpdb->prefix . DB_NAME ), 0, 40 );
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,5)', $lock ) ) ) {
			self::fail( __( 'Another Date save is in progress. Try again after it finishes.', 'woocommerce-jetpack' ), 409 ); }
		$begun = false;
		try {
			if ( false === $wpdb->query( 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE' ) ) {
				self::fail( __( 'Could not establish a safe admission check.', 'woocommerce-jetpack' ) ); }
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
				self::fail( __( 'Could not begin a safe save.', 'woocommerce-jetpack' ) );
			} $begun = true;
			$post    = $wpdb->get_row( $wpdb->prepare( "SELECT ID,post_type,post_status FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE", $id ), ARRAY_A );
			self::authorize( $id );
			if ( ! $post || 'product' !== $post['post_type'] || in_array( $post['post_status'], array( 'trash', 'auto-draft' ), true ) ) {
				self::fail( __( 'This product is no longer editable.', 'woocommerce-jetpack' ), 403 ); }
			$c = self::context( $id, true );
			if ( ! hash_equals( self::hash( $id, $c ), $guard ) ) {
				self::fail( __( 'Settings changed elsewhere. Your draft was not saved. Discard and load current values before trying again.', 'woocommerce-jetpack' ), 409 ); }
			$target = self::target( $id, $c, $draft, $undo );
			$before = array(
				'enabled'     => self::raw( $c, 'enabled' ),
				'direct_date' => self::raw( $c, 'direct_date' ),
			);
			if ( $target === $before ) {
				self::fail( __( 'No values changed. Nothing was saved.', 'woocommerce-jetpack' ) ); }
			if ( self::consequential( $c ) ) {
				$expires = isset( $review['expires'] ) ? (int) $review['expires'] : 0;
				if ( empty( $review['confirm'] ) || 'yes' !== $review['confirm'] || $expires < time() || $expires > time() + 300 || empty( $review['token'] ) || ! is_string( $review['token'] ) || ! hash_equals( self::review_signature( $id, $guard, $target, $undo, $expires ), $review['token'] ) ) {
					self::fail( __( 'Review the current purchase consequence and acknowledge it before saving.', 'woocommerce-jetpack' ) ); }
			}
			foreach ( $target as $key => $raw ) {
				self::write_meta( $id, $c, $key, $raw ); }
			$after = self::context( $id, true );
			foreach ( $target as $key => $raw ) {
				if ( self::raw( $after, $key ) !== $raw ) {
					self::fail( __( 'Readback did not match. This transaction was rolled back.', 'woocommerce-jetpack' ), 500 ); }
			}
			$record = array(
				'status'      => $undo ? 'undone' : 'applied',
				'user'        => get_current_user_id(),
				'time'        => time(),
				'before'      => $before,
				'after'       => $target,
				'after_guard' => self::hash( $id, $after, false ),
			);
			if ( ! WCJ_Job_Store::compare_exchange( self::receipt_key( $id ), $c['receipt'], WCJ_Job_Store::value_state( $record ), array( self::receipt_key( $id ) ) ) ) {
				self::fail( __( 'The saved record changed. Nothing from this transaction was applied.', 'woocommerce-jetpack' ), 409 ); }
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				self::fail( __( 'Commit outcome could not be verified. Load current values before any further save.', 'woocommerce-jetpack' ), 500 );
			} $begun = false;
		} catch ( Throwable $e ) {
			if ( $begun ) {
				$wpdb->query( 'ROLLBACK' );
			} self::clear_cache( $id );
			throw $e; } finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); }
			self::clear_cache( $id );
			return self::load( $id );
	}
	/**
	 * Dispatch nonce-checked requests without shopper mutations.
	 *
	 * @return void
	 */
	public function handle() {
		try {
			self::authorize();
			$op = isset( $_POST['op'] ) && is_string( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
			if ( ! in_array( $op, self::OPS, true ) || ! check_ajax_referer( 'wcj_product_date_' . $op, 'nonce', false ) ) {
				self::fail( __( 'Your request expired. Reload this page; nothing was changed.', 'woocommerce-jetpack' ), 403 ); }
			if ( 'search' === $op ) {
				$q = isset( $_POST['q'] ) && is_string( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
				if ( strlen( $q ) > 100 ) {
					self::fail( __( 'Use a shorter search.', 'woocommerce-jetpack' ) ); }
				$query = new WP_Query(
					array(
						'post_type'      => 'product',
						'post_status'    => array( 'publish', 'private', 'draft', 'pending', 'future' ),
						's'              => $q,
						'posts_per_page' => 25,
						'no_found_rows'  => true,
						'fields'         => 'ids',
					)
				);
				$items = array();
				foreach ( $query->posts as $id ) {
					if ( current_user_can( 'edit_post', $id ) ) {
						$items[] = array(
							'id'    => $id,
							'title' => get_the_title( $id ),
						); }
				}
				wp_send_json_success(
					array(
						'items' => $items,
						'limit' => 25,
					)
				);
			}
			$id = isset( $_POST['product'] ) ? absint( $_POST['product'] ) : 0;
			if ( ! $id ) {
				self::fail( __( 'Choose a product first.', 'woocommerce-jetpack' ) ); }
			self::authorize( $id );
			if ( 'load' === $op ) {
				wp_send_json_success( self::load( $id ) ); }
			$guard = isset( $_POST['guard'] ) && is_string( $_POST['guard'] ) ? wp_unslash( $_POST['guard'] ) : '';
			$draft = isset( $_POST['draft'] ) && is_string( $_POST['draft'] ) ? json_decode( wp_unslash( $_POST['draft'] ), true ) : null;
			$undo  = 'undo' === $op || ( 'review' === $op && isset( $_POST['undo'] ) && 'yes' === $_POST['undo'] );
			if ( 'review' === $op ) {
				wp_send_json_success( self::review( $id, $guard, $draft, $undo ) ); }
			$review = isset( $_POST['review'] ) && is_string( $_POST['review'] ) ? json_decode( wp_unslash( $_POST['review'] ), true ) : array();
			wp_send_json_success( self::mutate( $id, $guard, $draft, $undo, is_array( $review ) ? $review : array() ) );
		} catch ( Throwable $e ) {
			$code = in_array( $e->getCode(), array( 403, 409, 422 ), true ) ? $e->getCode() : 500;
			wp_send_json_error(
				array(
					'message' => $code === 500 ? __( 'The request could not be verified. Keep a copy of your draft and load current values before another save.', 'woocommerce-jetpack' ) : $e->getMessage(),
					'kind'    => 422 === $code ? 'validation' : ( 409 === $code ? 'conflict' : 'uncertain' ),
				),
				$code
			); }
	}
	/**
	 * Render the optional editor with product controls initially blocked.
	 *
	 * @return void
	 */
	public function render() {
		try {
			self::authorize();
		} catch ( Throwable $e ) {
			wp_die( esc_html( $e->getMessage() ) ); }
		wp_enqueue_style( 'wcj-product-date-job', wcj_plugin_url() . '/assets/css/admin/wcj-product-date-job.css', array(), '1.0' );
		wp_enqueue_script( 'wcj-product-date-job', wcj_plugin_url() . '/assets/js/admin/wcj-product-date-job.js', array( 'wp-i18n' ), '1.2', true );
		$nonces = array();
		foreach ( self::OPS as $op ) {
			$nonces[ $op ] = wp_create_nonce( 'wcj_product_date_' . $op ); }
		wp_localize_script(
			'wcj-product-date-job',
			'wcjProductDate',
			array(
				'url'    => admin_url( 'admin-ajax.php' ),
				'nonces' => $nonces,
				'user'   => get_current_user_id(),
			)
		);
		include __DIR__ . '/views/product-date-job.php';
	}
}
new WCJ_Product_Date_Job();
