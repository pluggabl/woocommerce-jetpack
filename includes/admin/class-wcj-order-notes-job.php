<?php
/**
 * A three-option Order notes wording editor. Never renders or mutates a shopper checkout.
 *
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-job-store.php';
require_once __DIR__ . '/cart-checkout-groups.php';

/** Narrow, native-backed wording transaction with exact absence-preserving recovery. */
final class WCJ_Order_Notes_Job {
	const PREFIX  = 'wcj_checkout_fields_order_comments_';
	const RECEIPT = 'booster_order_notes_job_v1';
	const FIELDS  = array( 'label', 'placeholder', 'description' );
	const OPS     = array( 'load', 'save', 'undo' );

	/** Register only admin endpoints and read-only help. */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register' ), 99 );
		add_action( 'add_meta_boxes_product', array( 'WCJ_Cart_Checkout_Groups', 'panel_help' ), 102 );
		add_action( 'add_meta_boxes', array( 'WCJ_Cart_Checkout_Groups', 'order_panel_help' ), 102 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( 'WCJ_Cart_Checkout_Groups', 'order_answers_help' ), PHP_INT_MAX );
		add_action( 'show_user_profile', array( 'WCJ_Cart_Checkout_Groups', 'profile_help' ), 99 );
		add_action( 'edit_user_profile', array( 'WCJ_Cart_Checkout_Groups', 'profile_help' ), 99 );
		foreach ( self::OPS as $op ) {
			add_action( 'wp_ajax_wcj_order_notes_' . $op, array( $this, 'handle' ) );
		}
	}

	/** Guided URL, never an action. */
	public static function url() {
		return admin_url( 'admin.php?page=wcj-order-notes-job' );
	}

	/** Native URL with its existing nonce and category. */
	public static function native_url() {
		return WCJ_Cart_Checkout_Groups::native_url( 'checkout_core_fields', 'wcj_cart_core_fields_fields_options_tab' );
	}

	/** Throw a classified, user-readable refusal. */
	private static function fail( $message, $code = 422 ) {
		throw new RuntimeException( $message, $code );
	}

	/** Preserve module registration, allowlist and role policy. */
	public static function authorize() {
		if ( ! WCJ_Cart_Checkout_Groups::allowed( 'checkout_core_fields' ) ) {
			self::fail( __( 'Ask an administrator for access to Checkout Core Fields.', 'woocommerce-jetpack' ), 403 );
		}
	}

	/** Register the optional editor and the family navigation. */
	public function register() {
		if ( WCJ_Cart_Checkout_Groups::allowed( 'checkout_core_fields' ) ) {
			add_submenu_page( 'woocommerce', __( 'Order notes wording', 'woocommerce-jetpack' ), __( 'Order notes wording', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-order-notes-job', array( $this, 'render' ) );
		}
		if ( current_user_can( 'manage_woocommerce' ) ) {
			add_submenu_page( 'woocommerce', __( 'Cart and checkout tasks', 'woocommerce-jetpack' ), __( 'Cart and checkout tasks', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-cart-checkout-tasks', array( 'WCJ_Cart_Checkout_Groups', 'hub' ) );
		}
	}

	/** Exact keys needed for this edit and its applicability. */
	private static function keys() {
		return array_merge(
			array_map( static function ( $field ) { return self::PREFIX . $field; }, self::FIELDS ),
			array( 'wcj_checkout_core_fields_enabled', self::PREFIX . 'is_enabled', self::PREFIX . 'is_required', self::PREFIX . 'cats_incl', self::PREFIX . 'cats_excl', 'wcj_checkout_core_fields_checking_relation', 'woocommerce_checkout_page_id', self::RECEIPT )
		);
	}

	/** Read raw options and configured page, without invoking checkout filters or shortcodes. */
	private static function context( $lock = false ) {
		global $wpdb;
		$keys = self::keys();
		$sql  = "SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name IN (" . implode( ',', array_fill( 0, count( $keys ), '%s' ) ) . ') ORDER BY option_name' . ( $lock ? ' FOR UPDATE' : '' );
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $keys ), ARRAY_A );
		if ( $wpdb->last_error || ! is_array( $rows ) ) {
			self::fail( __( 'Current settings could not be read. Nothing was changed.', 'woocommerce-jetpack' ), 500 );
		}
		$states = array_fill_keys( $keys, array( 'exists' => false, 'raw' => '' ) );
		foreach ( $rows as $row ) {
			if ( $states[ $row['option_name'] ]['exists'] ) {
				self::fail( __( 'Duplicate native settings need full settings. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			$states[ $row['option_name'] ] = array( 'exists' => true, 'raw' => (string) $row['option_value'] );
		}
		$page_id = $states['woocommerce_checkout_page_id']['raw'];
		$page    = null;
		if ( ctype_digit( $page_id ) && (int) $page_id > 0 ) {
			$page = $wpdb->get_row( $wpdb->prepare( "SELECT ID,post_type,post_status,post_content FROM {$wpdb->posts} WHERE ID=%d" . ( $lock ? ' FOR UPDATE' : '' ), (int) $page_id ), ARRAY_A );
			if ( $wpdb->last_error ) {
				self::fail( __( 'The configured checkout page could not be read.', 'woocommerce-jetpack' ), 500 );
			}
		}
		return array( 'states' => $states, 'page' => $page, 'paid' => true === apply_filters( 'booster_option', false, true ) );
	}

	/** Bind exact relevant configuration, page bytes, edition and current actor. */
	private static function guard( $context, $receipt = true ) {
		if ( ! $receipt ) {
			unset( $context['states'][ self::RECEIPT ] );
		}
		return hash_hmac( 'sha256', serialize( array( get_current_user_id(), $context ) ), wp_salt( 'nonce' ) );
	}

	/** Read a string without changing native absence semantics. */
	private static function value( $context, $key, $default = '' ) {
		$state = $context['states'][ $key ];
		return $state['exists'] ? $state['raw'] : $default;
	}

	/** This optional editor accepts plain single-line text, not advanced native markup. */
	private static function plain( $value ) {
		return is_string( $value ) && strlen( $value ) <= 500 && 1 === preg_match( '//u', $value ) && ! is_serialized( $value ) && ! preg_match( '/[\x00-\x1F\x7F<>\[\]]/u', $value ) && ( '' === $value || 1 === preg_match( '/[^\s\p{Z}\p{Cf}]/u', $value ) );
	}

	/** Read-only classification, never proof of a live shopper field. */
	private static function checkout_kind( $context ) {
		$page = $context['page'];
		if ( ! $page || 'page' !== $page['post_type'] || 'publish' !== $page['post_status'] ) {
			return 'unknown';
		}
		$blocks  = has_block( 'woocommerce/checkout', $page['post_content'] );
		$classic = has_shortcode( $page['post_content'], 'woocommerce_checkout' );
		return $blocks && ! $classic ? 'blocks' : ( $classic && ! $blocks ? 'classic' : 'unknown' );
	}

	/** Explain supported editing boundaries without normalizing custom state. */
	private static function lock_reason( $context ) {
		if ( wp_using_ext_object_cache() ) {
			return __( 'Use full settings with persistent object caching. This editor cannot safely save here.', 'woocommerce-jetpack' );
		}
		if ( has_filter( 'pre_option' ) ) {
			return __( 'A site-wide option filter is active. Use full settings; this editor will not bypass it.', 'woocommerce-jetpack' );
		}
		if ( ! class_exists( 'WCJ_Checkout_Core_Fields' ) ) {
			return __( 'The native Core Fields integration is unavailable. Use full settings.', 'woocommerce-jetpack' );
		}
		foreach ( self::keys() as $key ) {
			if ( self::RECEIPT !== $key && ( has_filter( 'pre_option_' . $key ) || has_filter( 'option_' . $key ) || has_filter( 'default_option_' . $key ) || has_filter( $key ) ) ) {
				return __( 'A site filter owns a relevant setting. Use full settings; this editor will not bypass it.', 'woocommerce-jetpack' );
			}
		}
		foreach ( self::FIELDS as $field ) {
			if ( ! self::plain( self::value( $context, self::PREFIX . $field ) ) ) {
				return __( 'Existing advanced or nonstandard wording was kept unchanged. Use full settings to edit it.', 'woocommerce-jetpack' );
			}
		}
		return '';
	}


	/** Parse only bounded serialized arrays. Corrupt private records must not pollute an AJAX response. */
	private static function safe_array( $raw ) {
		if ( ! is_string( $raw ) || strlen( $raw ) > 30000 || ! is_serialized( $raw ) ) {
			return null;
		}
		// Suppress only parser warnings for this bounded value, then restore the caller's handler.
		set_error_handler( static function () { return true; }, E_WARNING | E_NOTICE );
		try {
			$value = unserialize( $raw, array( 'allowed_classes' => false ) );
			return is_array( $value ) ? $value : null;
		} catch ( Throwable $error ) {
			return null;
		} finally {
			restore_error_handler();
		}
	}

	/** Display bounded valid UTF-8 only, while guards and persistence retain exact native bytes. */
	private static function display_value( $raw ) {
		if ( 1 !== preg_match( '//u', $raw ) ) {
			return array( 'text' => __( 'This native value is not valid UTF-8 and cannot be displayed here. It was kept unchanged.', 'woocommerce-jetpack' ), 'truncated' => true );
		}
		preg_match( '/\A.{0,2000}/us', $raw, $match );
		$text = $match[0];
		return array( 'text' => $text, 'truncated' => strlen( $text ) !== strlen( $raw ) );
	}

	/** Empty serialized native category lists are not active conditions. Unknown types stay qualified. */
	private static function conditions( $context ) {
		if ( ! $context['paid'] ) {
			return 'ignored-free';
		}
		$configured = false;
		foreach ( array( 'cats_incl', 'cats_excl' ) as $suffix ) {
			$raw = self::value( $context, self::PREFIX . $suffix );
			if ( '' === $raw || 'a:0:{}' === $raw ) {
				continue;
			}
			$list = self::safe_array( $raw );
			if ( null === $list || array_filter( $list, static function ( $v ) { return ! is_scalar( $v ); } ) ) {
				return 'unknown';
			}
			$configured = $configured || count( $list ) > 0;
		}
		return $configured ? 'configured' : 'none';
	}

	/** Decode our own bounded receipt without constructing objects. */
	private static function record( $context ) {
		$state = $context['states'][ self::RECEIPT ];
		if ( ! $state['exists'] || strlen( $state['raw'] ) > 30000 || ! is_serialized( $state['raw'] ) ) {
			return null;
		}
		$record = self::safe_array( $state['raw'] );
		if ( ! is_array( $record ) || 1 !== ( $record['version'] ?? null ) || get_current_user_id() !== ( $record['user'] ?? null ) || ! isset( $record['before'], $record['after'], $record['after_guard'], $record['status'] ) || ! is_string( $record['after_guard'] ) ) {
			return null;
		}
		foreach ( array( 'before', 'after' ) as $side ) {
			if ( ! is_array( $record[ $side ] ) || array_keys( $record[ $side ] ) !== self::FIELDS ) {
				return null;
			}
			foreach ( $record[ $side ] as $state ) {
				if ( ! is_array( $state ) || ! isset( $state['exists'], $state['raw'] ) || ! is_bool( $state['exists'] ) || ! self::plain( $state['raw'] ) || ( ! $state['exists'] && '' !== $state['raw'] ) ) {
					return null;
				}
			}
		}
		return $record;
	}

	/** Send only authorized, bounded current configuration and own receipt to the UI. */
	public static function load() {
		self::authorize();
		$context = self::context();
		$record  = self::record( $context );
		$draft   = array();
		$raw     = array();
		foreach ( self::FIELDS as $field ) {
			$raw[ $field ]   = $context['states'][ self::PREFIX . $field ];
			$display = self::display_value( $raw[ $field ]['raw'] );
			$raw[ $field ]['truncated'] = $display['truncated'];
			$raw[ $field ]['raw'] = $display['text'];
			$draft[ $field ] = $display['text'];
		}
		return array(
			'guard' => self::guard( $context ), 'draft' => $draft, 'raw' => $raw,
			'locked' => self::lock_reason( $context ), 'module' => self::value( $context, 'wcj_checkout_core_fields_enabled', 'no' ),
			'field' => self::value( $context, self::PREFIX . 'is_enabled', 'default' ),
			'conditional' => 'configured' === self::conditions( $context ),
			'conditions' => self::conditions( $context ),
			'kind' => self::checkout_kind( $context ),
			'undo' => $record && 'applied' === $record['status'] && hash_equals( $record['after_guard'], self::guard( $context, false ) ),
			'record' => $record ? array( 'status' => $record['status'], 'before' => $record['before'], 'after' => $record['after'] ) : null,
		);
	}

	/** Refuse unsupported transaction/cache environments before any mutation. */
	private static function transactional() {
		global $wpdb;
		if ( wp_using_ext_object_cache() ) {
			self::fail( __( 'Use full settings with persistent object caching. This editor cannot safely save here.', 'woocommerce-jetpack' ) );
		}
		foreach ( array( $wpdb->options, $wpdb->posts ) as $table ) {
			$row = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A );
			if ( ! $row || 'InnoDB' !== $row['Engine'] ) {
				self::fail( __( 'Transactional database tables are required. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
		}
	}

	/** Clear only the touched option caches, including rollback. */
	private static function clear_cache() {
		$core = WCJ_Job_Store::core();
		foreach ( array_merge( array_map( static function ( $field ) { return self::PREFIX . $field; }, self::FIELDS ), array( self::RECEIPT ) ) as $key ) {
			wp_cache_delete( $key, 'options' );
			if ( $core && isset( $core->options[ $key ] ) ) {
				unset( $core->options[ $key ] );
			}
		}
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	/** Save or restore the exact three option states atomically. */
	public static function mutate( $guard, $draft, $undo = false ) {
		global $wpdb;
		self::authorize();
		self::transactional();
		if ( ! is_string( $guard ) || strlen( $guard ) !== 64 ) {
			self::fail( __( 'Load current values before saving.', 'woocommerce-jetpack' ), 409 );
		}
		if ( ! $undo ) {
			if ( ! is_array( $draft ) || count( $draft ) !== 3 || array_diff( array_keys( $draft ), self::FIELDS ) ) {
				self::fail( __( 'Send only Label, Placeholder and Description.', 'woocommerce-jetpack' ) );
			}
			foreach ( $draft as $value ) {
				if ( ! self::plain( $value ) ) {
					self::fail( __( 'Use visible plain single-line text up to 500 bytes, or clear the whole value to use the incoming default. Markup and shortcodes belong in full settings.', 'woocommerce-jetpack' ) );
				}
			}
		}
		$lock  = 'wcj_notes_' . substr( hash( 'sha256', $wpdb->options ), 0, 40 );
		$begun = false;
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 2)', $lock ) ) ) {
			self::fail( __( 'Another wording edit is running. Try again shortly.', 'woocommerce-jetpack' ), 409 );
		}
		try {
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
				self::fail( __( 'The transaction could not start. Nothing was changed.', 'woocommerce-jetpack' ), 500 );
			}
			$begun   = true;
			$context = self::context( true );
			self::authorize();
			if ( ! hash_equals( self::guard( $context ), $guard ) ) {
				self::fail( __( 'Relevant settings changed elsewhere. Your draft was kept. Discard it and load current values before another save.', 'woocommerce-jetpack' ), 409 );
			}
			$reason = self::lock_reason( $context );
			if ( $reason ) {
				self::fail( $reason );
			}
			$record = self::record( $context );
			if ( $undo && ( ! $record || 'applied' !== $record['status'] || ! hash_equals( $record['after_guard'], self::guard( $context, false ) ) ) ) {
				self::fail( __( 'Undo no longer matches the current settings. Newer values were kept.', 'woocommerce-jetpack' ), 409 );
			}
			$before = array();
			$target = array();
			foreach ( self::FIELDS as $field ) {
				$before[ $field ] = $context['states'][ self::PREFIX . $field ];
				$target[ $field ] = $undo ? $record['before'][ $field ] : ( ! $before[ $field ]['exists'] && '' === $draft[ $field ] ? $before[ $field ] : WCJ_Job_Store::value_state( $draft[ $field ] ) );
			}
			if ( $before === $target ) {
				$wpdb->query( 'ROLLBACK' );
				$begun = false;
				return self::load();
			}
			foreach ( $target as $field => $state ) {
				if ( ! WCJ_Job_Store::compare_exchange( self::PREFIX . $field, $before[ $field ], $state ) ) {
					self::fail( __( 'A native value changed. This transaction was rolled back.', 'woocommerce-jetpack' ), 409 );
				}
			}
			$after = self::context( true );
			foreach ( $target as $field => $state ) {
				if ( $after['states'][ self::PREFIX . $field ] !== $state ) {
					self::fail( __( 'Saved readback did not match. This transaction was rolled back.', 'woocommerce-jetpack' ), 500 );
				}
			}
			$new_record = WCJ_Job_Store::value_state( array( 'version' => 1, 'user' => get_current_user_id(), 'status' => $undo ? 'undone' : 'applied', 'before' => $before, 'after' => $target, 'after_guard' => self::guard( $after, false ) ) );
			if ( ! WCJ_Job_Store::compare_exchange( self::RECEIPT, $context['states'][ self::RECEIPT ], $new_record, array( self::RECEIPT ) ) || WCJ_Job_Store::read_state( self::RECEIPT ) !== $new_record ) {
				self::fail( __( 'The saved record did not match. This transaction was rolled back.', 'woocommerce-jetpack' ), 409 );
			}
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				self::fail( __( 'Commit outcome is unknown. Load current values before another save.', 'woocommerce-jetpack' ), 500 );
			}
			$begun = false;
		} catch ( Throwable $error ) {
			if ( $begun ) {
				$wpdb->query( 'ROLLBACK' );
			}
			throw $error;
		} finally {
			self::clear_cache();
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
		return self::load();
	}

	/** Narrow POST schema and operation-specific nonce checks. */
	public function handle() {
		try {
			self::authorize();
			$op = isset( $_POST['op'] ) && is_string( $_POST['op'] ) ? wp_unslash( $_POST['op'] ) : '';
			if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! in_array( $op, self::OPS, true ) || ! check_ajax_referer( 'wcj_order_notes_' . $op, 'nonce', false ) ) {
				self::fail( __( 'Your request expired or access changed. Reload this page.', 'woocommerce-jetpack' ), 403 );
			}
			$allowed = 'load' === $op ? array( 'action', 'op', 'nonce' ) : ( 'undo' === $op ? array( 'action', 'op', 'nonce', 'guard' ) : array( 'action', 'op', 'nonce', 'guard', 'draft' ) );
			if ( array_diff( array_keys( $_POST ), $allowed ) || ( $_POST['action'] ?? '' ) !== 'wcj_order_notes_' . $op ) {
				self::fail( __( 'Unexpected request fields. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			if ( 'load' === $op ) {
				wp_send_json_success( self::load() );
			}
			$guard = isset( $_POST['guard'] ) && is_string( $_POST['guard'] ) ? wp_unslash( $_POST['guard'] ) : '';
			$draft = isset( $_POST['draft'] ) && is_string( $_POST['draft'] ) && strlen( $_POST['draft'] ) <= 5000 ? json_decode( wp_unslash( $_POST['draft'] ), true ) : null;
			wp_send_json_success( self::mutate( $guard, $draft, 'undo' === $op ) );
		} catch ( Throwable $error ) {
			$code = in_array( $error->getCode(), array( 403, 409, 422 ), true ) ? $error->getCode() : 500;
			wp_send_json_error( array( 'message' => 500 === $code ? __( 'The result could not be verified. Keep a copy of your draft and load current values before another save.', 'woocommerce-jetpack' ) : $error->getMessage(), 'kind' => 422 === $code ? 'validation' : ( 409 === $code ? 'conflict' : 'uncertain' ) ), $code );
		}
	}

	/** Render without native field producers or writes. */
	public function render() {
		try {
			self::authorize();
		} catch ( RuntimeException $error ) {
			wp_die( esc_html( $error->getMessage() ), '', array( 'response' => 403 ) );
		}
		wp_enqueue_style( 'wcj-order-notes-job', wcj_plugin_url() . '/assets/css/admin/wcj-order-notes-job.css', array(), '1.0' );
		wp_enqueue_script( 'wcj-order-notes-job', wcj_plugin_url() . '/assets/js/admin/wcj-order-notes-job.js', array( 'wp-i18n' ), '1.0', true );
		wp_set_script_translations( 'wcj-order-notes-job', 'woocommerce-jetpack' );
		$nonces = array();
		foreach ( self::OPS as $op ) {
			$nonces[ $op ] = wp_create_nonce( 'wcj_order_notes_' . $op );
		}
		wp_localize_script( 'wcj-order-notes-job', 'wcjOrderNotes', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonces' => $nonces ) );
		include __DIR__ . '/views/order-notes-job.php';
	}
}
new WCJ_Order_Notes_Job();
