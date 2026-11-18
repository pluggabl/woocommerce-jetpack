<?php
/**
 * Payment method fee job: one payment method's fee or discount in one draft, a Now/After estimate from the
 * native evaluator on cloned raw maps, conditional cell-merging apply, readback receipt and coherent group undo.
 * Writes only the selected method's cells in five native gateway-keyed maps and, when explicitly ticked, the
 * Gateways Fees and Discounts module switch.
 *
 * @package Booster_For_WooCommerce/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wcj-job-store.php';

if ( ! class_exists( 'WCJ_Gateway_Fee_Job' ) ) :

	/**
	 * Gateway fee adapter. Fee calculation, cart hooks and order-pay recalculation stay in the module.
	 */
	class WCJ_Gateway_Fee_Job {

		// Site-local job state. Not wcj_-prefixed: never exported, and a backup containing it is refused by import.
		const RECEIPT = 'booster_gateway_fee_job_receipt';
		const LOCK    = 'booster_gateway_fee_job_lock';

		const MODULE    = 'wcj_payment_gateways_fees_enabled';
		const MODULE_ID = 'payment_gateways_fees';
		const USERWISE  = 'wcj_enable_payment_gateway_charge_discount_userwise';
		const KLARNA    = 'wcj_enable_payment_gateway_charge_discount';
		const FORCE     = 'wcj_gateways_fees_force_default_payment_gateway';
		const FEES      = 'wcj_gateways_fees_';
		const ROLES     = 'wcj_modules_by_user_roles_';

		/** Receipt format. Records without it (earlier builds) are shown but never undone here. */
		const RECEIPT_V = 2;

		const OPS = array( 'load', 'preview', 'apply', 'recover' );

		/** Editable maps, in the write order used when a rule is filled (title last). */
		const EDIT = array(
			'min'   => 'wcj_gateways_fees_min_cart_amount',
			'max'   => 'wcj_gateways_fees_max_cart_amount',
			'type'  => 'wcj_gateways_fees_type',
			'value' => 'wcj_gateways_fees_value',
			'text'  => 'wcj_gateways_fees_text',
		);

		/** Maps the evaluator reads but this page never writes. Keys are the module's option keys. */
		const READONLY = array(
			'round'            => 'wcj_gateways_fees_round',
			'round_precision'  => 'wcj_gateways_fees_round_precision',
			'is_taxable'       => 'wcj_gateways_fees_is_taxable',
			'tax_class_id'     => 'wcj_gateways_fees_tax_class_id',
			'exclude_shipping' => 'wcj_gateways_fees_exclude_shipping',
			'include_taxes'    => 'wcj_gateways_fees_include_taxes',
			'include_products' => 'wcj_gateways_fees_include_products',
			'exclude_products' => 'wcj_gateways_fees_exclude_products',
		);

		/** Page field => module option key. */
		const OPTION_KEY = array(
			'min'   => 'min_cart_amount',
			'max'   => 'max_cart_amount',
			'type'  => 'type',
			'value' => 'value',
			'text'  => 'text',
		);

		/**
		 * Construct.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
			foreach ( self::OPS as $op ) {
				add_action( 'wp_ajax_wcj_gateway_fee_job_' . $op, array( $this, 'handle' ) );
			}
		}

		/**
		 * Page URL.
		 *
		 * @param string $gateway Gateway ID, '' for the default.
		 * @return string
		 */
		public static function url( $gateway = '' ) {
			return admin_url( 'admin.php?page=wcj-gateway-fee-job' . ( '' !== $gateway ? '&gateway=' . rawurlencode( $gateway ) : '' ) );
		}

		/**
		 * Native Gateways Fees and Discounts settings.
		 *
		 * @return string
		 */
		public static function native_url() {
			return admin_url( wcj_admin_tab_url() . '&wcj-cat=payment_gateways&section=payment_gateways_fees' );
		}

		/**
		 * Rule explanations stay an Elite diagnostic; the link is only offered there.
		 *
		 * @return string
		 */
		public static function explain_url() {
			return defined( 'WCJ_PLUGIN_FILE' ) && 'booster-elite-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ) ? admin_url( 'admin.php?page=wcj-rule-explanations' ) : '';
		}

		/**
		 * Capability check used by every entry point.
		 *
		 * @throws RuntimeException When the user lacks the native capability.
		 */
		public static function authorize() {
			if ( ! WCJ_Task_Finder::allowed( 'payment_gateways_fees' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by WooCommerce.
				throw new RuntimeException( esc_html__( 'You need permission to manage WooCommerce.', 'woocommerce-jetpack' ) );
			}
		}

		/**
		 * Register submenu.
		 */
		public function register_page() {
		if ( ! WCJ_Task_Finder::allowed( 'payment_gateways_fees' ) ) { return; }
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability.
			add_submenu_page( 'woocommerce', __( 'Payment method fee', 'woocommerce-jetpack' ), __( 'Payment method fee', 'woocommerce-jetpack' ), 'manage_woocommerce', 'wcj-gateway-fee-job', array( $this, 'render' ) );
		}

		/**
		 * Gateways Fees and Discounts module instance, if loaded.
		 *
		 * @return object|null
		 */
		private static function module() {
			$core = WCJ_Job_Store::core();
			return $core && isset( $core->all_modules[ self::MODULE_ID ] ) ? $core->all_modules[ self::MODULE_ID ] : null;
		}

		/**
		 * Registered WooCommerce payment methods (all of them, enabled or not). Never enables one.
		 *
		 * @return array id => { title, enabled }
		 */
		private static function gateways() {
			$out = array();
			if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways ) {
				return $out;
			}
			foreach ( (array) WC()->payment_gateways->payment_gateways() as $id => $gateway ) {
				if ( ! is_string( $id ) || ! preg_match( '/^[a-z0-9_\-]{1,64}$/D', $id ) || ! is_object( $gateway ) ) {
					continue;
				}
				$title      = function_exists( 'wcj_get_payment_gateway_admin_title' ) ? wcj_get_payment_gateway_admin_title( $gateway, $id ) : ( method_exists( $gateway, 'get_method_title' ) ? $gateway->get_method_title() : $id );
				$out[ $id ] = array(
					'title'   => wp_strip_all_tags( (string) $title ),
					'enabled' => isset( $gateway->enabled ) && 'yes' === $gateway->enabled,
				);
			}
			return $out;
		}

		/**
		 * Gateway from the request: a registered payment method only.
		 *
		 * @return string
		 * @throws RuntimeException When unknown.
		 */
		private static function posted_gateway() {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
			$raw = isset( $_POST['gateway'] ) && is_string( $_POST['gateway'] ) ? sanitize_text_field( wp_unslash( $_POST['gateway'] ) ) : '';
			if ( ! preg_match( '/^[a-z0-9_\-]{1,64}$/D', $raw ) || ! isset( self::gateways()[ $raw ] ) ) {
				throw new RuntimeException( esc_html__( 'That payment method is not registered in WooCommerce. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			return $raw;
		}

		/**
		 * Parse a submitted JSON object.
		 *
		 * @param string $field POST field.
		 * @return array
		 * @throws RuntimeException On malformed input.
		 */
		private static function posted_json( $field ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput -- Nonce verified in handle(); bounded JSON strictly validated per field.
			$raw = isset( $_POST[ $field ] ) && is_string( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '{}';
			if ( strlen( $raw ) > 8000 ) {
				throw new RuntimeException( esc_html__( 'The draft is too large.', 'woocommerce-jetpack' ) );
			}
			$data = json_decode( $raw, true, 4 );
			if ( ! is_array( $data ) ) {
				throw new RuntimeException( esc_html__( 'The draft could not be read. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			return $data;
		}

		// ---------------------------------------------------------------------
		// Raw map reading. Maps are read from the database before any normalisation; a stored
		// non-array map is never treated as empty.
		// ---------------------------------------------------------------------

		/**
		 * Every fee-related option row in one SELECT, past every cache: fee maps, role values, Klarna alias,
		 * per-role switch, role-module settings, the module switch, multicurrency and price decimals. One
		 * statement reads one consistent version of the rows; it is not a lock. Each step derives all of
		 * its decisions from one snapshot, so nothing read later can silently replace what was checked.
		 *
		 * @return array|null Option => raw value sorted by name, or null when the read failed.
		 */
		private static function snapshot() {
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name IN (%s, %s, %s)", $wpdb->esc_like( self::FEES ) . '%', $wpdb->esc_like( self::KLARNA ) . '%', $wpdb->esc_like( self::ROLES ) . '%', self::MODULE, 'wcj_multicurrency_enabled', 'woocommerce_price_num_decimals' ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( ! is_array( $rows ) || '' !== (string) $wpdb->last_error ) {
				return null;
			}
			$out = array();
			foreach ( $rows as $row ) {
				$out[ (string) $row['option_name'] ] = (string) $row['option_value'];
			}
			ksort( $out, SORT_STRING );
			return $out;
		}

		/**
		 * Snapshot for a read step; a failed read is never treated as absent settings.
		 *
		 * @return array
		 * @throws RuntimeException When the settings cannot be read.
		 */
		private static function read_snapshot() {
			$snap = self::snapshot();
			if ( null === $snap ) {
				throw new RuntimeException( esc_html__( 'The fee settings could not be read. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			return $snap;
		}

		/**
		 * Option state inside a snapshot, in the store's { exists, raw } shape.
		 *
		 * @param array  $snap snapshot().
		 * @param string $option Option.
		 * @return array
		 */
		private static function state_in( $snap, $option ) {
			return isset( $snap[ $option ] ) ? array(
				'exists' => true,
				'raw'    => $snap[ $option ],
			) : array(
				'exists' => false,
				'raw'    => '',
			);
		}

		/**
		 * Parse a raw map state.
		 *
		 * @param array $state State.
		 * @return array|null Map, or null when the stored value is not an array.
		 */
		private static function map_of( $state ) {
			if ( ! $state['exists'] ) {
				return array();
			}
			$value = WCJ_Job_Store::state_value( $state );
			return is_array( $value ) ? $value : null;
		}

		/**
		 * Cell of a map state: { present, value } or null for a malformed map.
		 *
		 * @param array  $state State.
		 * @param string $gw Gateway.
		 * @return array|null
		 */
		private static function cell( $state, $gw ) {
			$map = self::map_of( $state );
			if ( null === $map ) {
				return null;
			}
			return array_key_exists( $gw, $map ) ? array(
				'present' => true,
				'value'   => $map[ $gw ],
			) : array(
				'present' => false,
				'value'   => null,
			);
		}

		/**
		 * Stable signature of one cell for fingerprints.
		 *
		 * @param array  $state State.
		 * @param string $gw Gateway.
		 * @return string
		 */
		private static function cell_sig( $state, $gw ) {
			$cell = self::cell( $state, $gw );
			if ( null === $cell ) {
				return 'M:' . WCJ_Job_Store::state_hash( $state );
			}
			return $cell['present'] ? 'P:' . serialize( $cell['value'] ) : 'A'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Fingerprint only.
		}

		/**
		 * Plain one-line title: valid UTF-8, no markup, shortcode brackets, control characters or entities.
		 *
		 * @param mixed $value Value.
		 * @return bool
		 */
		private static function plain( $value ) {
			return is_string( $value ) && strlen( $value ) <= 200 && false !== preg_match( '//u', $value ) && ! preg_match( '/[<>\[\]\x00-\x1F\x7F]/u', $value ) && ! preg_match( '/&(?:#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]*);/i', $value );
		}

		/**
		 * Native-safe number: what the evaluator accepts in a scenario.
		 *
		 * @param mixed $value Value.
		 * @return bool
		 */
		private static function numeric( $value ) {
			return ( is_int( $value ) || is_float( $value ) || is_string( $value ) ) && is_numeric( $value ) && is_finite( (float) $value ) && abs( (float) $value ) <= 1000000000;
		}

		/**
		 * Module flag state as the shop runs it: saved switch and the runtime allowlist. The role-module
		 * switch is read raw; the request's cached value only ever adds caution.
		 *
		 * @param array $snap snapshot().
		 * @return array
		 */
		private static function module_env( $snap ) {
			$state   = self::state_in( $snap, self::MODULE );
			$allowed = ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( self::MODULE_ID );
			$raw     = 'yes' === WCJ_Job_Store::state_value( self::state_in( $snap, self::ROLES . 'enabled' ) ) && ( ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( 'modules_by_user_roles' ) );
			$roles   = $raw || ( function_exists( 'wcj_is_module_enabled' ) && wcj_is_module_enabled( 'modules_by_user_roles' ) );
			$saved   = 'yes' === WCJ_Job_Store::state_value( $state );
			return array(
				'state'   => $state,
				'saved'   => $saved,
				'allowed' => $allowed,
				'roles'   => $roles,
				'on'      => $saved && $allowed,
			);
		}

		/**
		 * Whether a multicurrency context changes fee amounts (the evaluator refuses scenarios then).
		 *
		 * @param array $snap snapshot().
		 * @return bool
		 */
		private static function multicurrency( $snap ) {
			$core = WCJ_Job_Store::core();
			$mc   = $core && isset( $core->all_modules['multicurrency'] ) ? $core->all_modules['multicurrency'] : null;
			return 'yes' === WCJ_Job_Store::state_value( self::state_in( $snap, 'wcj_multicurrency_enabled' ) ) || ( $mc && method_exists( $mc, 'is_enabled' ) && $mc->is_enabled() );
		}

		/**
		 * Role-specific value option names for a gateway's role overrides, excluding names that collide with maps.
		 *
		 * @return array
		 */
		private static function role_options() {
			$out = array();
			if ( function_exists( 'wcj_get_user_roles' ) ) {
				foreach ( array_keys( (array) wcj_get_user_roles() ) as $role ) {
					$option = 'wcj_gateways_fees_' . $role;
					if ( is_string( $role ) && ! in_array( $option, self::EDIT, true ) && ! in_array( $option, self::READONLY, true ) ) {
						$out[] = $option;
					}
				}
			}
			$out[] = 'wcj_gateways_fees_guest';
			return array_values( array_unique( $out ) );
		}

		/**
		 * Raw states of every map the evaluator reads, keyed by option.
		 *
		 * @param array $snap snapshot().
		 * @return array
		 */
		private static function states( $snap ) {
			$out = array();
			foreach ( array_merge( array_values( self::EDIT ), array_values( self::READONLY ), array( self::USERWISE ) ) as $option ) {
				$out[ $option ] = self::state_in( $snap, $option );
			}
			return $out;
		}

		/**
		 * Fingerprints of what the selected rule depends on and of everything else fee-related, from one
		 * snapshot (no further reads).
		 *
		 * 'deps': the selected method's cells in every fee map except $skip fields, its role override cells,
		 * the alias, multicurrency, allowlist and the raw role-module settings. 'others': every fee option
		 * with the selected method's editable cells removed and without the module switch, plus the policy
		 * and the registered method list. Neither includes what this job itself is about to write.
		 *
		 * @param array  $snap snapshot().
		 * @param string $gw Gateway.
		 * @param array  $skip Editable fields excluded from 'deps'.
		 * @return array { deps, others }
		 */
		private static function fingerprints( $snap, $gw, $skip = array() ) {
			$module = self::module_env( $snap );
			$roles  = array();
			foreach ( $snap as $name => $raw ) {
				if ( 0 === strpos( $name, self::ROLES ) ) {
					$roles[ $name ] = $raw;
				}
			}
			$policy = array(
				'allowed'   => $module['allowed'],
				'roles'     => $module['roles'],
				'role_rows' => $roles,
				'mc'        => self::multicurrency( $snap ),
				'klarna'    => self::state_in( $snap, self::KLARNA ),
				'force'     => self::state_in( $snap, self::FORCE ),
				'dec'       => self::state_in( $snap, 'woocommerce_price_num_decimals' ),
				'alias'     => self::aliased( $snap, $gw ),
			);
			$deps   = array( 'policy' => $policy );
			foreach ( self::EDIT as $field => $option ) {
				if ( ! in_array( $field, $skip, true ) ) {
					$deps[ $option ] = self::cell_sig( self::state_in( $snap, $option ), $gw );
				}
			}
			foreach ( array_merge( array_values( self::READONLY ), array( self::USERWISE ), self::role_options() ) as $option ) {
				$deps[ $option ] = self::cell_sig( self::state_in( $snap, $option ), $gw );
			}
			$others = array(
				'policy'   => $policy,
				'gateways' => self::gateways(),
			);
			foreach ( $snap as $name => $value ) {
				if ( 0 !== strpos( $name, self::FEES ) && 0 !== strpos( $name, self::KLARNA ) && 0 !== strpos( $name, self::ROLES ) ) {
					continue;
				}
				if ( in_array( $name, self::EDIT, true ) ) {
					$map = maybe_unserialize( $value );
					if ( is_array( $map ) ) {
						unset( $map[ $gw ] );
						// Holding only the selected method's cell, an editable map carries no other rule: it
						// counts as absent, so creating that row on first use is not another method's change.
						// A malformed value, any other key and every other option are still compared.
						if ( array() === $map ) {
							continue;
						}
						$value = 'A:' . serialize( $map ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Fingerprint only.
					}
				}
				$others[ $name ] = $value;
			}
			return array(
				'deps'   => hash( 'sha256', serialize( $deps ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Fingerprint only.
				'others' => hash( 'sha256', serialize( $others ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Fingerprint only.
			);
		}

		/**
		 * Klarna alias through the module's own resolver, without the live instance's state.
		 *
		 * @param string $gw Gateway.
		 * @return string
		 */
		private static function resolve( $gw ) {
			$module = self::module();
			return $module && method_exists( $module, 'resolve_fee_gateway' ) ? (string) $module->resolve_fee_gateway( $gw ) : $gw;
		}

		/**
		 * Whether the method's fee cells are shared through the Klarna alias. Decided by the raw flag: with it
		 * on, every Klarna method, klarna_payments itself included, uses the klarna_payments cells.
		 *
		 * @param array  $snap snapshot().
		 * @param string $gw Gateway.
		 * @return bool
		 */
		private static function aliased( $snap, $gw ) {
			return false !== strpos( $gw, 'klarna' ) && ( 'yes' === WCJ_Job_Store::state_value( self::state_in( $snap, self::KLARNA ) ) || self::resolve( $gw ) !== $gw );
		}

		/**
		 * Products the module would see for a gateway, through its own edition filter.
		 *
		 * @param array  $state Raw map state.
		 * @param string $gw Gateway.
		 * @return array
		 */
		private static function products( $state, $gw ) {
			$map = self::map_of( $state );
			$map = apply_filters( 'booster_option', array(), null === $map ? array() : $map );
			$map = is_array( $map ) ? $map : array();
			return isset( $map[ $gw ] ) ? array_values( array_filter( array_map( 'strval', (array) $map[ $gw ] ) ) ) : array();
		}

		/**
		 * Why the selected method's rule is kept as saved here, or null when it is editable.
		 *
		 * @param string $gw Gateway.
		 * @param array  $snap snapshot().
		 * @param array  $states states().
		 * @return string|null
		 */
		private static function lock_reason( $gw, $snap, $states ) {
			$expert = ' ' . __( 'It is kept exactly as saved; change it in the full fee settings.', 'woocommerce-jetpack' );
			if ( ! self::module() || ! method_exists( self::module(), 'evaluate_gateway_fee' ) ) {
				return __( 'The fee module is not available in this request.', 'woocommerce-jetpack' );
			}
			if ( self::aliased( $snap, $gw ) ) {
				return __( 'This method uses the Klarna fee settings (Klarna alias is on).', 'woocommerce-jetpack' ) . $expert;
			}
			if ( self::multicurrency( $snap ) ) {
				return __( 'Multicurrency is on, so fee amounts change with the shopper\'s currency.', 'woocommerce-jetpack' ) . $expert;
			}
			foreach ( $states as $option => $state ) {
				if ( null === self::map_of( $state ) ) {
					return __( 'A fee setting is stored in a format this page does not read.', 'woocommerce-jetpack' ) . $expert;
				}
			}
			$text = self::cell( $states[ self::EDIT['text'] ], $gw );
			if ( $text['present'] && ! self::plain( $text['value'] ) ) {
				return __( 'The fee name uses formatting, a shortcode or an encoded character.', 'woocommerce-jetpack' ) . $expert;
			}
			$type = self::cell( $states[ self::EDIT['type'] ], $gw );
			if ( $type['present'] && ! in_array( $type['value'], array( 'fixed', 'percent' ), true ) ) {
				return __( 'The fee type is not fixed or percent.', 'woocommerce-jetpack' ) . $expert;
			}
			foreach ( array( 'value', 'min', 'max' ) as $field ) {
				$cell = self::cell( $states[ self::EDIT[ $field ] ], $gw );
				if ( $cell['present'] && ! self::numeric( $cell['value'] ) ) {
					return __( 'An amount is not a plain number.', 'woocommerce-jetpack' ) . $expert;
				}
			}
			$precision = self::cell( $states[ self::READONLY['round_precision'] ], $gw );
			if ( $precision['present'] && ( ! self::numeric( $precision['value'] ) || abs( (float) $precision['value'] ) > 10 ) ) {
				return __( 'The rounding precision is not a plain number.', 'woocommerce-jetpack' ) . $expert;
			}
			if ( self::products( $states[ self::READONLY['include_products'] ], $gw ) || self::products( $states[ self::READONLY['exclude_products'] ], $gw ) ) {
				return __( 'This fee depends on products in the cart.', 'woocommerce-jetpack' ) . $expert;
			}
			$userwise = self::cell( $states[ self::USERWISE ], $gw );
			if ( $userwise['present'] && 'yes' === $userwise['value'] ) {
				return __( 'This fee is different for each customer role.', 'woocommerce-jetpack' ) . $expert;
			}
			return null;
		}

		/**
		 * Saved rule values as the page shows them. Absent cells are the module's defaults.
		 *
		 * @param string $gw Gateway.
		 * @param array  $states states().
		 * @return array
		 */
		private static function saved_rule( $gw, $states ) {
			$get = static function ( $field, $absent ) use ( $gw, $states ) {
				$cell = self::cell( $states[ self::EDIT[ $field ] ], $gw );
				return null !== $cell && $cell['present'] ? $cell['value'] : $absent;
			};
			$value = $get( 'value', 0 );
			$text  = $get( 'text', '' );
			$type  = $get( 'type', 'fixed' );
			$num   = self::numeric( $value ) ? (float) $value : 0.0;
			return array(
				'text'   => is_string( $text ) ? $text : '',
				'type'   => is_string( $type ) ? $type : '',
				'kind'   => $num < 0 ? 'discount' : 'fee',
				'amount' => self::numeric( $value ) ? ltrim( (string) $value, '-' ) : '',
				'min'    => self::numeric( $get( 'min', 0 ) ) ? (string) $get( 'min', 0 ) : '',
				'max'    => self::numeric( $get( 'max', 0 ) ) ? (string) $get( 'max', 0 ) : '',
			);
		}

		/**
		 * Read-only settings the evaluator uses for this method, as short facts.
		 *
		 * @param string $gw Gateway.
		 * @param array  $states states().
		 * @return array
		 */
		private static function kept( $gw, $states ) {
			$yes  = static function ( $key ) use ( $gw, $states ) {
				$cell = self::cell( $states[ self::READONLY[ $key ] ], $gw );
				return null !== $cell && $cell['present'] && 'yes' === $cell['value'];
			};
			$kept = array();
			if ( $yes( 'round' ) ) {
				$kept[] = __( 'Percent fees are rounded.', 'woocommerce-jetpack' );
			}
			if ( $yes( 'is_taxable' ) ) {
				$kept[] = __( 'The fee is taxable; tax is added at checkout and not shown here.', 'woocommerce-jetpack' );
			}
			if ( $yes( 'exclude_shipping' ) ) {
				$kept[] = __( 'Shipping is left out of the cart amount.', 'woocommerce-jetpack' );
			}
			if ( $yes( 'include_taxes' ) ) {
				$kept[] = __( 'Taxes count toward the cart amount; this example has no tax.', 'woocommerce-jetpack' );
			}
			return $kept;
		}

		/**
		 * Current state, bases and environment for a gateway.
		 *
		 * @param string|null $gw Gateway; read from the request when null.
		 * @param array|null  $snap snapshot(); read now when null.
		 * @return array
		 */
		public static function load( $gw = null, $snap = null ) {
			self::authorize();
			$gw             = null === $gw ? self::posted_gateway() : $gw;
			$snap           = null === $snap ? self::read_snapshot() : $snap;
			$out            = self::view( $gw, $snap, true );
			$receipt        = get_option( self::RECEIPT, null );
			$out['receipt'] = is_array( $receipt ) ? self::receipt_view( $receipt, $snap ) : null;
			return $out;
		}

		/**
		 * Rule, lock and environment from one snapshot; with $full also the stale-check bases and the picker.
		 * Preview leaves $full off and computes no fingerprints.
		 *
		 * @param string $gw Gateway.
		 * @param array  $snap snapshot().
		 * @param bool   $full Include base and methods.
		 * @return array
		 */
		private static function view( $gw, $snap, $full ) {
			$states   = self::states( $snap );
			$module   = self::module_env( $snap );
			$gateways = self::gateways();
			$locked   = self::lock_reason( $gw, $snap, $states );
			$out      = array(
				'gateway' => $gw,
				'rule'    => $locked ? null : self::saved_rule( $gw, $states ),
				'locked'  => $locked,
				'env'     => array(
					'title'          => isset( $gateways[ $gw ] ) ? $gateways[ $gw ]['title'] : $gw,
					'woo_enabled'    => isset( $gateways[ $gw ] ) && $gateways[ $gw ]['enabled'],
					'module_on'      => $module['on'],
					'module_saved'   => $module['saved'],
					'toggle_blocked' => self::toggle_block( $module, $snap, $states ),
					'roles_policy'   => $module['roles'],
					'force_default'  => 'yes' === WCJ_Job_Store::state_value( self::state_in( $snap, self::FORCE ) ),
					'klarna_alias'   => 'yes' === WCJ_Job_Store::state_value( self::state_in( $snap, self::KLARNA ) ),
					'kept'           => $locked ? array() : self::kept( $gw, $states ),
					'currency'       => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
					'decimals'       => function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2,
				),
			);
			if ( $full ) {
				$base = array( 'module' => WCJ_Job_Store::state_hash( $module['state'] ) );
				foreach ( self::EDIT as $field => $option ) {
					$base[ $field ] = WCJ_Job_Store::state_hash( $states[ $option ] );
				}
				$fp             = self::fingerprints( $snap, $gw );
				$base['deps']   = $fp['deps'];
				$base['others'] = $fp['others'];
				$out['base']    = $base;
				$out['methods'] = self::methods( $gateways, $states );
			}
			return $out;
		}

		/**
		 * Why the module switch cannot be turned on here, or null.
		 *
		 * @param array $module module_env().
		 * @param array $snap snapshot().
		 * @param array $states states().
		 * @return string|null
		 */
		private static function toggle_block( $module, $snap, $states ) {
			if ( $module['on'] ) {
				return null;
			}
			if ( ! $module['allowed'] ) {
				return __( 'A site setting limits which Booster modules can run, and it does not include Fees & Discounts.', 'woocommerce-jetpack' );
			}
			if ( $module['roles'] ) {
				return __( 'Modules by user roles is on, so whether Fees & Discounts runs depends on the shopper\'s role.', 'woocommerce-jetpack' );
			}
			if ( self::multicurrency( $snap ) ) {
				return __( 'Multicurrency is on.', 'woocommerce-jetpack' );
			}
			foreach ( $states as $state ) {
				if ( null === self::map_of( $state ) ) {
					return __( 'A fee setting is stored in a format this page does not read, so other methods\' fees cannot be checked.', 'woocommerce-jetpack' );
				}
			}
			return null;
		}

		/**
		 * Method picker entries: registered methods and whether a fee name is saved for each.
		 *
		 * @param array $gateways gateways().
		 * @param array $states states().
		 * @return array
		 */
		private static function methods( $gateways, $states ) {
			$out = array();
			foreach ( $gateways as $id => $g ) {
				$text  = self::cell( $states[ self::EDIT['text'] ], $id );
				$out[] = array(
					'id'      => $id,
					'title'   => $g['title'],
					'enabled' => $g['enabled'],
					'rule'    => null !== $text && $text['present'] && '' !== $text['value'],
				);
			}
			return $out;
		}

		// ---------------------------------------------------------------------
		// Draft validation and classification.
		// ---------------------------------------------------------------------

		/**
		 * Normalised positive decimal string, or null.
		 *
		 * @param mixed $value Posted value.
		 * @return string|null
		 */
		private static function decimal( $value ) {
			if ( ! is_string( $value ) ) {
				return null;
			}
			$value = trim( $value );
			if ( ! preg_match( '/^\d{1,10}(?:\.\d{1,6})?$/D', $value ) || (float) $value > 1000000000 ) {
				return null;
			}
			return preg_replace( '/^0+(?=\d)/', '', $value );
		}

		/**
		 * Validate a draft against current state and work out the cells it changes.
		 *
		 * @param string $gw Gateway.
		 * @param array  $draft Posted draft.
		 * @param array  $snap snapshot() the whole step uses.
		 * @param bool   $full Include stale-check bases in 'current'.
		 * @return array { issues, cells => [field => new value], module, rule (effective), current (view), class }
		 * @throws RuntimeException On unknown fields or a kept rule.
		 */
		private static function validate( $gw, $draft, $snap, $full ) {
			$current = self::view( $gw, $snap, $full );
			if ( $current['locked'] ) {
				throw new RuntimeException( esc_html( $current['locked'] ) );
			}
			$allowed = array( 'text', 'kind', 'amount', 'type', 'min', 'max', 'module' );
			foreach ( array_keys( $draft ) as $key ) {
				if ( ! in_array( $key, $allowed, true ) ) {
					throw new RuntimeException( esc_html__( 'Unknown fee setting. Nothing was changed.', 'woocommerce-jetpack' ) );
				}
			}
			$saved  = $current['rule'];
			$rule   = $saved;
			$issues = array();
			if ( array_key_exists( 'text', $draft ) ) {
				$text = is_string( $draft['text'] ) ? trim( $draft['text'] ) : null;
				if ( null === $text || ! self::plain( $text ) ) {
					$issues['text'] = __( 'Use plain text on one line, up to 200 characters, without < > [ ] or codes like &amp;.', 'woocommerce-jetpack' );
				} else {
					$rule['text'] = $text;
				}
			}
			if ( array_key_exists( 'type', $draft ) ) {
				if ( ! in_array( $draft['type'], array( 'fixed', 'percent' ), true ) ) {
					$issues['type'] = __( 'Choose a fixed amount or a percent.', 'woocommerce-jetpack' );
				} else {
					$rule['type'] = $draft['type'];
				}
			}
			if ( array_key_exists( 'kind', $draft ) || array_key_exists( 'amount', $draft ) ) {
				$kind   = array_key_exists( 'kind', $draft ) ? $draft['kind'] : $saved['kind'];
				$amount = array_key_exists( 'amount', $draft ) ? self::decimal( $draft['amount'] ) : self::decimal( $saved['amount'] );
				if ( ! in_array( $kind, array( 'fee', 'discount' ), true ) ) {
					$issues['kind'] = __( 'Choose a fee or a discount.', 'woocommerce-jetpack' );
				} elseif ( null === $amount || (float) $amount <= 0 ) {
					$issues['amount'] = __( 'Enter an amount above 0, for example 5 or 2.50.', 'woocommerce-jetpack' );
				} else {
					$rule['kind']   = $kind;
					$rule['amount'] = $amount;
				}
			}
			foreach ( array( 'min', 'max' ) as $field ) {
				if ( array_key_exists( $field, $draft ) ) {
					$limit = '' === $draft[ $field ] ? '0' : self::decimal( $draft[ $field ] );
					if ( null === $limit ) {
						$issues[ $field ] = __( 'Enter 0 or more. 0 means no limit.', 'woocommerce-jetpack' );
					} else {
						$rule[ $field ] = $limit;
					}
				}
			}
			if ( ! isset( $issues['min'] ) && ! isset( $issues['max'] ) && 0.0 !== (float) $rule['max'] && (float) $rule['max'] < (float) $rule['min'] ) {
				$issues['max'] = __( 'The most must be at least the least amount, or 0 for no limit.', 'woocommerce-jetpack' );
			}
			$module = false;
			if ( array_key_exists( 'module', $draft ) ) {
				if ( 'yes' !== $draft['module'] || $current['env']['module_on'] ) {
					throw new RuntimeException( esc_html__( 'Fees & Discounts can only be turned on here. Nothing was changed.', 'woocommerce-jetpack' ) );
				}
				if ( $current['env']['toggle_blocked'] ) {
					$issues['module'] = $current['env']['toggle_blocked'];
				} else {
					$module = true;
				}
			}
			// Only cells whose meaning changes are written; a drafted value equal to what runs now is not.
			$cells = array();
			if ( $rule['text'] !== $saved['text'] ) {
				$cells['text'] = $rule['text'];
			}
			if ( $rule['type'] !== $saved['type'] ) {
				$cells['type'] = $rule['type'];
			}
			$signed = ( 'discount' === $rule['kind'] ? -1 : 1 ) * (float) $rule['amount'];
			$before = ( 'discount' === $saved['kind'] ? -1 : 1 ) * (float) $saved['amount'];
			if ( $signed !== $before ) {
				$cells['value'] = ( 'discount' === $rule['kind'] ? '-' : '' ) . $rule['amount'];
			}
			foreach ( array( 'min', 'max' ) as $field ) {
				if ( (float) $rule[ $field ] !== (float) $saved[ $field ] ) {
					$cells[ $field ] = $rule[ $field ];
				}
			}
			$blank_flip = isset( $cells['text'] ) && ( '' === $saved['text'] ) !== ( '' === $rule['text'] );
			if ( $module ) {
				$class = 'global';
			} elseif ( array_diff( array_keys( $cells ), array( 'text' ) ) || $blank_flip ) {
				$class = 'money';
			} else {
				$class = $cells ? 'label' : 'none';
			}
			return array(
				'issues'  => $issues,
				'cells'   => $cells,
				'module'  => $module,
				'rule'    => $rule,
				'current' => $current,
				'class'   => $class,
			);
		}

		// ---------------------------------------------------------------------
		// Preview: the module's own evaluator on a clone whose options are the raw maps. Never reads a
		// session, selects a gateway, checks availability, touches a cart or calls order-pay methods.
		// ---------------------------------------------------------------------

		/**
		 * Explicit example facts.
		 *
		 * @return array
		 * @throws RuntimeException On invalid facts.
		 */
		private static function posted_context() {
			$ctx = self::posted_json( 'context' );
			$out = array(
				'tax'          => 0,
				'shipping_tax' => 0,
			);
			foreach ( array(
				'subtotal' => '25',
				'shipping' => '0',
			) as $key => $default ) {
				$value = isset( $ctx[ $key ] ) ? ( is_scalar( $ctx[ $key ] ) ? self::decimal( (string) $ctx[ $key ] ) : null ) : $default;
				if ( null === $value ) {
					throw new RuntimeException( esc_html__( 'Enter a cart amount and shipping of 0 or more.', 'woocommerce-jetpack' ) );
				}
				$out[ $key ] = (float) $value;
			}
			return $out;
		}

		/**
		 * One evaluator clone with raw maps, optionally with the selected method's cells replaced.
		 *
		 * @param array  $states states().
		 * @param string $gw Gateway whose cells are overlaid ('' for none).
		 * @param array  $cells Field => value overlay.
		 * @return object|null
		 */
		private static function evaluator( $states, $gw = '', $cells = array() ) {
			$module = self::module();
			if ( ! $module || ! method_exists( $module, 'evaluate_gateway_fee' ) || ! method_exists( $module, 'init_options' ) ) {
				return null;
			}
			$ev = clone $module;
			$ev->init_options();
			foreach ( self::EDIT as $field => $option ) {
				$map = self::map_of( $states[ $option ] );
				if ( null === $map ) {
					return null;
				}
				if ( '' !== $gw && array_key_exists( $field, $cells ) ) {
					$map[ $gw ] = $cells[ $field ];
				}
				$ev->options[ self::OPTION_KEY[ $field ] ] = $map;
			}
			foreach ( self::READONLY as $key => $option ) {
				$map = self::map_of( $states[ $option ] );
				if ( null === $map ) {
					return null;
				}
				if ( in_array( $key, array( 'include_products', 'exclude_products' ), true ) ) {
					$map = apply_filters( 'booster_option', array(), $map );
					$map = is_array( $map ) ? $map : array();
				}
				$ev->options[ $key ] = $map;
			}
			return $ev;
		}

		/**
		 * Evaluate one gateway on a clone; returns { state, reason, amount }.
		 *
		 * @param object|null $ev Evaluator.
		 * @param string      $gw Gateway.
		 * @param array       $facts Context facts.
		 * @return array
		 */
		private static function evaluate( $ev, $gw, $facts ) {
			if ( ! $ev || ! class_exists( 'WCJ_Rule_Explanation_Context' ) ) {
				return array(
					'state'  => 'unsupported',
					'reason' => 'producer_unavailable',
					'amount' => null,
				);
			}
			try {
				$r = $ev->evaluate_gateway_fee( $gw, new WCJ_Rule_Explanation_Context( $facts ), null, true );
			} catch ( Throwable $error ) {
				$r = null;
			}
			if ( ! is_array( $r ) || ! isset( $r['reason'] ) || ! is_string( $r['reason'] ) ) {
				return array(
					'state'  => 'unsupported',
					'reason' => 'evaluation_failed',
					'amount' => null,
				);
			}
			if ( 'eligible' === $r['reason'] && isset( $r['amount'] ) && is_numeric( $r['amount'] ) && is_finite( (float) $r['amount'] ) ) {
				return array(
					'state'  => (float) $r['amount'] < 0 ? 'discount' : 'fee',
					'reason' => 'eligible',
					'amount' => (float) $r['amount'],
				);
			}
			$none = array( 'not_configured', 'outside_cart_amount', 'zero_or_invalid_amount' );
			return array(
				'state'  => in_array( $r['reason'], $none, true ) ? 'none' : 'unsupported',
				'reason' => sanitize_key( $r['reason'] ),
				'amount' => null,
			);
		}

		/**
		 * Effective result: the module switch decides whether the rule runs. A raw figure for a switched-off
		 * module is returned separately as an inactive estimate.
		 *
		 * @param array $raw evaluate().
		 * @param bool  $on Module effective.
		 * @return array
		 */
		private static function effective( $raw, $on ) {
			if ( $on ) {
				return $raw;
			}
			return array(
				'state'    => 'off',
				'reason'   => 'module_disabled',
				'amount'   => null,
				'inactive' => $raw,
			);
		}

		/**
		 * Other methods with a saved fee name, each evaluated on the same example (conditional wording only).
		 *
		 * @param string $gw Selected gateway.
		 * @param array  $snap snapshot().
		 * @param array  $states states().
		 * @param array  $facts Context.
		 * @return array
		 */
		private static function other_rules( $gw, $snap, $states, $facts ) {
			$ev  = self::evaluator( $states );
			$out = array();
			foreach ( self::gateways() as $id => $g ) {
				if ( $id === $gw ) {
					continue;
				}
				$text = self::cell( $states[ self::EDIT['text'] ], $id );
				if ( null === $text || ! $text['present'] || '' === $text['value'] ) {
					continue;
				}
				$userwise = self::cell( $states[ self::USERWISE ], $id );
				$advanced = ( $userwise && $userwise['present'] && 'yes' === $userwise['value'] ) || self::products( $states[ self::READONLY['include_products'] ], $id ) || self::products( $states[ self::READONLY['exclude_products'] ], $id ) || self::aliased( $snap, $id );
				$out[]    = array(
					'title'   => $g['title'],
					'enabled' => $g['enabled'],
					'result'  => $advanced ? array(
						'state'  => 'unsupported',
						'reason' => 'advanced',
						'amount' => null,
					) : self::evaluate( $ev, $id, $facts ),
				);
			}
			return $out;
		}

		/**
		 * Units of an amount row: an amount uses its own side's type, cart limits are always store currency.
		 *
		 * @param string $field Field.
		 * @param string $before Type before ('fixed' or 'percent').
		 * @param string $after Type after.
		 * @return array { unit_before, unit_after }, null for rows without a unit.
		 */
		private static function units( $field, $before, $after ) {
			if ( 'value' === $field ) {
				return array( $before, $after );
			}
			return in_array( $field, array( 'min', 'max' ), true ) ? array( 'fixed', 'fixed' ) : array( null, null );
		}

		/**
		 * Change rows with old and new values for review, each side with its own unit.
		 *
		 * @param array $check validate().
		 * @return array
		 */
		private static function change_rows( $check ) {
			$saved = $check['current']['rule'];
			$rows  = array();
			foreach ( $check['cells'] as $field => $value ) {
				$unit   = self::units( $field, $saved['type'], $check['rule']['type'] );
				$rows[] = array(
					'field'       => $field,
					'before'      => 'value' === $field ? ( 'discount' === $saved['kind'] ? '-' : '' ) . $saved['amount'] : $saved[ $field ],
					'after'       => $value,
					'unit_before' => $unit[0],
					'unit_after'  => $unit[1],
				);
			}
			if ( $check['module'] ) {
				$rows[] = array(
					'field'       => 'module',
					'before'      => 'no',
					'after'       => 'yes',
					'unit_before' => null,
					'unit_after'  => null,
				);
			}
			return $rows;
		}

		/**
		 * Preview service: saved Now and draft After on one example. Writes nothing.
		 *
		 * @return array
		 */
		public static function preview() {
			self::authorize();
			$gw    = self::posted_gateway();
			$facts = self::posted_context();
			$snap  = self::read_snapshot();
			$check = self::validate( $gw, self::posted_json( 'draft' ), $snap, false );
			if ( $check['issues'] ) {
				return array(
					'error'  => __( 'Fix the highlighted fields to see the preview.', 'woocommerce-jetpack' ),
					'issues' => $check['issues'],
				);
			}
			$states   = self::states( $snap );
			$env      = $check['current']['env'];
			$after_on = $env['module_on'] || $check['module'];
			$now      = self::evaluate( self::evaluator( $states ), $gw, $facts );
			$after    = self::evaluate( self::evaluator( $states, $gw, $check['cells'] ), $gw, $facts );
			$result   = array(
				'now'     => self::effective( $now, $env['module_on'] ),
				'after'   => self::effective( $after, $after_on ),
				'class'   => $check['class'],
				'changes' => self::change_rows( $check ),
				'facts'   => array(
					'subtotal' => $facts['subtotal'],
					'shipping' => $facts['shipping'],
				),
			);
			if ( 'global' === $check['class'] || ( ! $env['module_on'] && ! $env['toggle_blocked'] ) ) {
				$result['others'] = self::other_rules( $gw, $snap, $states, $facts );
			}
			return $result;
		}

		// ---------------------------------------------------------------------
		// Writes. Each map write merges one cell into the map read just before, by exact compare-exchange.
		// ---------------------------------------------------------------------

		/**
		 * Map state with one cell set (or removed when $cell is absent), keeping every other key, its order
		 * and serialized type. Returns an absent state when a row this job created would become empty.
		 *
		 * @param array  $state Current state.
		 * @param string $gw Gateway.
		 * @param array  $cell { present, value }.
		 * @param bool   $created Whether this job created the row.
		 * @return array|null Null when the map is malformed.
		 */
		private static function with_cell( $state, $gw, $cell, $created = false ) {
			$map = self::map_of( $state );
			if ( null === $map ) {
				return null;
			}
			if ( $cell['present'] ) {
				$map[ $gw ] = $cell['value'];
			} else {
				unset( $map[ $gw ] );
				if ( $created && array() === $map ) {
					return array(
						'exists' => false,
						'raw'    => '',
					);
				}
			}
			return WCJ_Job_Store::value_state( $map );
		}

		/**
		 * Move one cell from $from to $to on the current map, only while it still holds $from.
		 * Neighbour cells written since are kept.
		 *
		 * @param string $option Option.
		 * @param string $gw Gateway.
		 * @param array  $from Expected cell.
		 * @param array  $to New cell.
		 * @param bool   $created Whether this job created the row (row may be deleted when emptied).
		 * @param array  $state State just read in a checked snapshot; read now when null.
		 * @return string done|same|moved
		 */
		private static function move_cell( $option, $gw, $from, $to, $created, $state = null ) {
			$state = null === $state ? WCJ_Job_Store::read_state( $option ) : $state;
			$cell  = self::cell( $state, $gw );
			if ( null === $cell ) {
				return 'moved';
			}
			if ( $cell === $to ) {
				return 'same';
			}
			if ( $cell !== $from ) {
				return 'moved';
			}
			$next = self::with_cell( $state, $gw, $to, $created );
			return null !== $next && WCJ_Job_Store::compare_exchange( $option, $state, $next ) ? 'done' : 'moved';
		}

		/**
		 * Put back own writes in reverse order, each only while it still holds what was written.
		 *
		 * @param array  $journal Entries keyed by option, in write order.
		 * @param string $gw Gateway.
		 * @param bool   $forward True to re-apply 'after' over 'before' (reversing an undo), false to restore 'before'.
		 * @return array { done => [option => entry], unresolved => [option => entry] }
		 */
		private static function reverse( $journal, $gw, $forward ) {
			$done       = array();
			$unresolved = array();
			foreach ( array_reverse( $journal, true ) as $option => $entry ) {
				if ( 'module' === $entry['field'] ) {
					$from = $forward ? $entry['before'] : $entry['after'];
					$to   = $forward ? $entry['after'] : $entry['before'];
					$cur  = WCJ_Job_Store::read_state( $option );
					$ok   = $cur === $to || ( $cur === $from && WCJ_Job_Store::compare_exchange( $option, $from, $to ) );
				} else {
					$from = $forward ? $entry['cell_before'] : $entry['cell_after'];
					$to   = $forward ? $entry['cell_after'] : $entry['cell_before'];
					$ok   = 'moved' !== self::move_cell( $option, $gw, $from, $to, ! $entry['before']['exists'] );
				}
				if ( $ok ) {
					$done[ $option ] = $entry;
				} else {
					$unresolved[ $option ] = $entry;
				}
			}
			return array(
				'done'       => $done,
				'unresolved' => $unresolved,
			);
		}

		/**
		 * Safe write order: clearing a title hides the rule first; filling it shows a complete rule last.
		 *
		 * @param array $cells Field => value.
		 * @return array Fields in order.
		 */
		private static function write_order( $cells ) {
			$order = array_keys( self::EDIT );
			if ( isset( $cells['text'] ) && '' === $cells['text'] ) {
				$order = array_merge( array( 'text' ), array_diff( $order, array( 'text' ) ) );
			}
			return array_values( array_filter( $order, static function ( $f ) use ( $cells ) {
				return array_key_exists( $f, $cells );
			} ) );
		}

		/**
		 * Whether the dependencies this apply or undo relies on are unchanged in a fresh snapshot.
		 *
		 * @param array|null $snap snapshot(); null (a failed read) never holds.
		 * @param string     $gw Gateway.
		 * @param array      $written Fields this group writes.
		 * @param array      $expect { deps, others } expected without those fields.
		 * @param bool       $global Whether the module switch is part of this group.
		 * @return bool
		 */
		private static function context_holds( $snap, $gw, $written, $expect, $global ) {
			if ( null === $snap ) {
				return false;
			}
			$now = self::fingerprints( $snap, $gw, $written );
			return hash_equals( $expect['deps'], $now['deps'] ) && ( ! $global || hash_equals( $expect['others'], $now['others'] ) );
		}

		/**
		 * Apply one consolidated draft for one method.
		 *
		 * @return array
		 * @throws RuntimeException On unconfirmed or concurrent requests.
		 */
		public static function apply() {
			self::authorize();
			$gw    = self::posted_gateway();
			$draft = self::posted_json( 'draft' );
			$base  = self::posted_json( 'base' );
			if ( ! $draft ) {
				throw new RuntimeException( esc_html__( 'The draft is empty. Nothing was changed.', 'woocommerce-jetpack' ) );
			}
			$lock = WCJ_Job_Store::lock( self::LOCK, __( 'Another payment fee apply or undo is running. Nothing was changed. Try again in a moment.', 'woocommerce-jetpack' ) );
			try {
				// One snapshot carries validation, the stale check, every planned 'before' and the expected
				// context. Nothing read later rebases this apply onto newer maps: a change after it fails
				// the exact compare-exchange or the context check and is kept.
				$snap  = self::read_snapshot();
				// Another author can turn the module on while this draft is open. Do not write or silently rebase.
				if ( isset( $draft['module'] ) && 'yes' === $draft['module'] && self::module_env( $snap )['on'] ) {
					$now = self::fingerprints( $snap, $gw );
					$can_keep = isset( $base['deps'] ) && is_string( $base['deps'] ) && hash_equals( $base['deps'], $now['deps'] );
					return array(
						'status' => 'stale',
						'rebase' => $can_keep,
						'error'  => $can_keep ? __( 'Fees & Discounts was turned on elsewhere. No fee was saved. Re-check your draft without turning the module on again.', 'woocommerce-jetpack' ) : __( 'The fee or its conditions also changed. Nothing was saved. Read the current settings before drafting again.', 'woocommerce-jetpack' ),
						'fresh'  => self::load( $gw, $snap ),
					);
				}
				$check = self::validate( $gw, $draft, $snap, true );
				if ( $check['issues'] ) {
					return array(
						'status' => 'invalid',
						'issues' => $check['issues'],
						'error'  => __( 'Some changes need attention. Nothing was changed.', 'woocommerce-jetpack' ),
					);
				}
				if ( 'none' === $check['class'] ) {
					return array(
						'status'  => 'unchanged',
						'message' => __( 'These values are already saved. Nothing was written.', 'woocommerce-jetpack' ),
						'fresh'   => self::load( $gw, $snap ),
					);
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle().
				if ( 'label' !== $check['class'] && ( ! isset( $_POST['confirm'] ) || '1' !== $_POST['confirm'] ) ) {
					throw new RuntimeException( esc_html__( 'Confirm the fee changes in the review before applying. Nothing was changed.', 'woocommerce-jetpack' ) );
				}
				// Whole-map staleness: any change to a map this apply writes, to the switch, or to what the
				// rule depends on refuses the apply before any write. Rebase is offered only when the selected
				// method itself is unchanged.
				$fresh = $check['current']['base'];
				$stale = array();
				foreach ( array_merge( array_keys( self::EDIT ), array( 'module', 'deps' ) ) as $key ) {
					if ( ! isset( $base[ $key ] ) || ! is_string( $base[ $key ] ) || ! hash_equals( $fresh[ $key ], $base[ $key ] ) ) {
						$stale[] = $key;
					}
				}
				if ( $check['module'] && ( ! isset( $base['others'] ) || ! is_string( $base['others'] ) || ! hash_equals( $fresh['others'], $base['others'] ) ) ) {
					$stale[] = 'others';
				}
				if ( $stale ) {
					$selected = ! in_array( 'deps', $stale, true );
					return array(
						'status' => 'stale',
						'rebase' => $selected,
						'error'  => $selected ? __( 'Other fee settings changed since you loaded this page. Nothing was changed. Re-check to keep your draft on top of what is saved now.', 'woocommerce-jetpack' ) : __( 'This payment method\'s fee changed since you loaded it. Nothing was changed. Compare what is saved now before drafting again.', 'woocommerce-jetpack' ),
						'fresh'  => self::load( $gw, $snap ),
					);
				}
				$plan = array();
				foreach ( self::write_order( $check['cells'] ) as $field ) {
					$option = self::EDIT[ $field ];
					$before = self::state_in( $snap, $option );
					$cell   = self::cell( $before, $gw );
					$to     = array(
						'present' => true,
						'value'   => $check['cells'][ $field ],
					);
					$after  = null === $cell ? null : self::with_cell( $before, $gw, $to );
					if ( null === $after ) {
						// A map this page cannot read is refused here and never handed to the store.
						throw new RuntimeException( esc_html__( 'A fee setting is stored in a format this page does not read. Nothing was changed.', 'woocommerce-jetpack' ) );
					}
					$plan[ $option ] = array(
						'field'       => $field,
						'before'      => $before,
						'after'       => $after,
						'cell_before' => $cell,
						'cell_after'  => $to,
					);
				}
				if ( $check['module'] ) {
					$plan[ self::MODULE ] = array(
						'field'  => 'module',
						'before' => self::state_in( $snap, self::MODULE ),
						'after'  => WCJ_Job_Store::value_state( 'yes' ),
					);
				}
				$fields  = array_keys( $check['cells'] );
				$expect  = self::fingerprints( $snap, $gw, $fields );
				$journal = array();
				$failed  = null;
				foreach ( $plan as $option => $entry ) {
					// Re-check at every write boundary, not only once before the first write.
					if ( ! self::context_holds( self::snapshot(), $gw, $fields, $expect, $check['module'] ) || ! WCJ_Job_Store::compare_exchange( $option, $entry['before'], $entry['after'] ) ) {
						$failed = $entry['field'];
						break;
					}
					$journal[ $option ] = $entry;
				}
				$done = null;
				if ( null === $failed ) {
					$done = self::snapshot();
					foreach ( $journal as $option => $entry ) {
						if ( null === $done || self::state_in( $done, $option ) !== $entry['after'] ) {
							$failed = $entry['field'];
							break;
						}
					}
					if ( null === $failed && ! self::context_holds( $done, $gw, $fields, $expect, $check['module'] ) ) {
						$failed = 'context';
					}
				}
				if ( null !== $failed ) {
					$back = self::reverse( $journal, $gw, false );
					if ( $back['unresolved'] ) {
						// Never lose the journal while own writes may still be in place. Not undoable here: no
						// verified context exists for a half-reversed group.
						self::save_receipt(
							array(
								'v'       => self::RECEIPT_V,
								'id'      => bin2hex( random_bytes( 8 ) ),
								'time'    => gmdate( 'c' ),
								'user'    => get_current_user_id(),
								'gateway' => $gw,
								'status'  => 'apply_incomplete',
								'entries' => $back['unresolved'],
							)
						);
					}
					return array(
						'status' => 'failed',
						'error'  => $back['unresolved'] ? __( 'Settings changed while applying. Some of this apply could not be put back because it changed again; it is listed and remains saved.', 'woocommerce-jetpack' ) : __( 'Settings changed while applying, so this apply was put back. Nothing from this draft remains saved; newer changes were kept.', 'woocommerce-jetpack' ),
						'kept'   => array_values( wp_list_pluck( $back['unresolved'], 'field' ) ),
						'fresh'  => self::load( $gw ),
					);
				}
				$entries = array();
				foreach ( $journal as $option => $entry ) {
					$entry['readback_ok'] = true;
					$entries[ $option ]   = $entry;
				}
				// The undo context comes from the read-back snapshot that just verified every write and the
				// expected context: 'unwritten' is what the rule depends on outside the written cells, so
				// undo never takes its expectation from live state later.
				$full = self::fingerprints( $done, $gw );
				$env  = $check['current']['env'];
				self::save_receipt(
					array(
						'v'       => self::RECEIPT_V,
						'id'      => bin2hex( random_bytes( 8 ) ),
						'time'    => gmdate( 'c' ),
						'user'    => get_current_user_id(),
						'gateway' => $gw,
						'status'  => 'applied',
						'entries' => $entries,
						'context' => array(
							'deps'      => $full['deps'],
							'others'    => $full['others'],
							'unwritten' => $expect['deps'],
							'fields'    => $fields,
						),
						// Units as they were on each side when applied; never inferred from later settings.
						'show'    => array(
							'type_before' => $check['current']['rule']['type'],
							'type_after'  => $check['rule']['type'],
							'currency'    => (string) $env['currency'],
							'decimals'    => (int) $env['decimals'],
						),
					)
				);
				return array(
					'status'  => 'applied',
					'message' => __( 'Saved and read back from the database.', 'woocommerce-jetpack' ),
					'fresh'   => self::load( $gw ),
				);
			} finally {
				WCJ_Job_Store::unlock( self::LOCK, $lock );
			}
		}

		// ---------------------------------------------------------------------
		// Receipt and undo.
		// ---------------------------------------------------------------------

		/**
		 * Receipt gateway, if well formed and still registered.
		 *
		 * @param array $receipt Stored receipt.
		 * @return string '' when unusable.
		 */
		private static function receipt_gateway( $receipt ) {
			$gw = isset( $receipt['gateway'] ) && is_string( $receipt['gateway'] ) ? $receipt['gateway'] : '';
			return preg_match( '/^[a-z0-9_\-]{1,64}$/D', $gw ) && isset( self::gateways()[ $gw ] ) ? $gw : '';
		}

		/**
		 * Receipt entries for an allowlisted option with an exact state and cell shape.
		 *
		 * @param mixed $entries Stored entries.
		 * @return array
		 */
		private static function valid_entries( $entries ) {
			$allowed = array_flip( self::EDIT );
			$allowed[ self::MODULE ] = 'module';
			$state = static function ( $s ) {
				return is_array( $s ) && isset( $s['exists'], $s['raw'] ) && is_bool( $s['exists'] ) && is_string( $s['raw'] );
			};
			$cell  = static function ( $c ) {
				return is_array( $c ) && array_key_exists( 'present', $c ) && array_key_exists( 'value', $c ) && is_bool( $c['present'] ) && ( null === $c['value'] || is_scalar( $c['value'] ) ) && array( 'present', 'value' ) === array_keys( $c );
			};
			$valid = array();
			foreach ( is_array( $entries ) ? $entries : array() as $option => $entry ) {
				$ok = isset( $allowed[ $option ] ) && is_array( $entry ) && isset( $entry['field'] ) && $allowed[ $option ] === $entry['field'] && isset( $entry['before'], $entry['after'] ) && $state( $entry['before'] ) && $state( $entry['after'] );
				if ( $ok && 'module' !== $entry['field'] ) {
					$ok = isset( $entry['cell_before'], $entry['cell_after'] ) && $cell( $entry['cell_before'] ) && $cell( $entry['cell_after'] ) && $entry['cell_after']['present'];
				}
				if ( $ok ) {
					$valid[ $option ] = $entry;
				}
			}
			return $valid;
		}

		/**
		 * Whether a record was written in this receipt format. Earlier records are shown, never undone.
		 *
		 * @param array $receipt Stored receipt.
		 * @return bool
		 */
		private static function current_format( $receipt ) {
			return isset( $receipt['v'] ) && self::RECEIPT_V === $receipt['v'];
		}

		/**
		 * The undo context persisted at apply, when complete and matching the record's written fields.
		 *
		 * @param array $receipt Stored receipt.
		 * @param array $valid valid_entries().
		 * @return array|null { deps, others, unwritten, fields }
		 */
		private static function receipt_context( $receipt, $valid ) {
			$ctx = self::current_format( $receipt ) && isset( $receipt['context'] ) && is_array( $receipt['context'] ) ? $receipt['context'] : null;
			if ( null === $ctx || ! isset( $ctx['fields'] ) || ! is_array( $ctx['fields'] ) ) {
				return null;
			}
			foreach ( array( 'deps', 'others', 'unwritten' ) as $key ) {
				if ( ! isset( $ctx[ $key ] ) || ! is_string( $ctx[ $key ] ) || ! preg_match( '/^[0-9a-f]{64}$/D', $ctx[ $key ] ) ) {
					return null;
				}
			}
			$stored = array_values( array_filter( $ctx['fields'], 'is_string' ) );
			$fields = array_values( array_diff( wp_list_pluck( $valid, 'field' ), array( 'module' ) ) );
			if ( count( $stored ) !== count( $ctx['fields'] ) ) {
				return null;
			}
			sort( $stored );
			sort( $fields );
			return $stored === $fields ? $ctx : null;
		}

		/**
		 * Units recorded at apply, or null for a record without them.
		 *
		 * @param array $receipt Stored receipt.
		 * @return array|null { type_before, type_after, currency, decimals }
		 */
		private static function receipt_show( $receipt ) {
			$show  = self::current_format( $receipt ) && isset( $receipt['show'] ) && is_array( $receipt['show'] ) ? $receipt['show'] : null;
			$types = array( 'fixed', 'percent' );
			if ( null === $show || ! isset( $show['type_before'], $show['type_after'], $show['currency'], $show['decimals'] ) || ! in_array( $show['type_before'], $types, true ) || ! in_array( $show['type_after'], $types, true ) || ! is_string( $show['currency'] ) || ! is_int( $show['decimals'] ) || $show['decimals'] < 0 || $show['decimals'] > 10 ) {
				return null;
			}
			return $show;
		}

		/**
		 * Store the latest record. Every earlier record that did not finish (an apply or undo not fully put
		 * back) is carried forward with its journal, never overwritten. Up to five are kept; anything older is
		 * counted and that count is shown on the page.
		 *
		 * @param array $receipt New record.
		 */
		private static function save_receipt( $receipt ) {
			$previous = get_option( self::RECEIPT, null );
			$list     = array();
			$dropped  = 0;
			if ( is_array( $previous ) ) {
				$list    = isset( $previous['incomplete'] ) && is_array( $previous['incomplete'] ) ? array_values( $previous['incomplete'] ) : array();
				$dropped = isset( $previous['incomplete_dropped'] ) && is_int( $previous['incomplete_dropped'] ) ? $previous['incomplete_dropped'] : 0;
				if ( isset( $previous['previous_incomplete'] ) && is_array( $previous['previous_incomplete'] ) ) {
					$list[] = $previous['previous_incomplete']; // Earlier builds nested one record here.
				}
				if ( ! empty( $previous['entries'] ) && isset( $previous['status'] ) && in_array( $previous['status'], array( 'apply_incomplete', 'undo_incomplete' ), true ) ) {
					unset( $previous['incomplete'], $previous['incomplete_dropped'], $previous['previous_incomplete'] );
					$list[] = $previous;
				}
			}
			if ( count( $list ) > 5 ) {
				$dropped += count( $list ) - 5;
				$list     = array_slice( $list, -5 );
			}
			if ( $list ) {
				$receipt['incomplete'] = $list;
			}
			if ( $dropped ) {
				$receipt['incomplete_dropped'] = $dropped;
			}
			update_option( self::RECEIPT, $receipt, false );
		}

		/**
		 * Receipt for display, with current match state from one snapshot. Amount units are the ones
		 * recorded at apply; a record without them shows its numbers unit-less and says so.
		 *
		 * @param array $receipt Stored receipt.
		 * @param array $snap snapshot().
		 * @return array
		 */
		private static function receipt_view( $receipt, $snap ) {
			$gw      = self::receipt_gateway( $receipt );
			$entries = isset( $receipt['entries'] ) && is_array( $receipt['entries'] ) ? $receipt['entries'] : array();
			$valid   = $gw ? self::valid_entries( $entries ) : array();
			$show    = self::receipt_show( $receipt );
			$status  = isset( $receipt['status'] ) && is_string( $receipt['status'] ) ? $receipt['status'] : '';
			$rows    = array();
			foreach ( $valid as $option => $entry ) {
				if ( 'module' === $entry['field'] ) {
					$ours   = self::state_in( $snap, $option ) === $entry['after'];
					$before = 'yes' === WCJ_Job_Store::state_value( $entry['before'] ) ? 'yes' : 'no';
					$after  = 'yes';
				} else {
					$ours   = self::cell( self::state_in( $snap, $option ), $gw ) === $entry['cell_after'];
					$before = $entry['cell_before']['present'] ? (string) $entry['cell_before']['value'] : null;
					$after  = (string) $entry['cell_after']['value'];
				}
				$unit   = $show ? self::units( $entry['field'], $show['type_before'], $show['type_after'] ) : array( null, null );
				$rows[] = array(
					'option'      => $option,
					'field'       => $entry['field'],
					'before'      => $before,
					'after'       => $after,
					'unit_before' => $unit[0],
					'unit_after'  => $unit[1],
					'created'     => ! $entry['before']['exists'],
					'still_ours'  => $ours,
				);
			}
			$unreadable = count( $entries ) - count( $valid );
			$undoable   = $rows && ! $unreadable && in_array( $status, array( 'applied', 'undo_reversed' ), true ) && null !== self::receipt_context( $receipt, $valid ) && ! in_array( false, wp_list_pluck( $rows, 'still_ours' ), true );
			$context_changed = false;
			if ( $undoable ) {
				// The button must use the same apply-time dependency guard as the writer, not just compare saved cells.
				$ctx    = self::receipt_context( $receipt, $valid );
				$now    = self::fingerprints( $snap, $gw );
				$fields = array_values( array_diff( wp_list_pluck( $valid, 'field' ), array( 'module' ) ) );
				$undoable = hash_equals( $ctx['deps'], $now['deps'] ) &&
					self::context_holds( $snap, $gw, $fields, array( 'deps' => $ctx['unwritten'], 'others' => $ctx['others'] ), isset( $valid[ self::MODULE ] ) );
				$context_changed = ! $undoable;
			}
			$unfinished = ( isset( $receipt['incomplete'] ) && is_array( $receipt['incomplete'] ) ? count( $receipt['incomplete'] ) : 0 ) + ( isset( $receipt['incomplete_dropped'] ) && is_int( $receipt['incomplete_dropped'] ) ? $receipt['incomplete_dropped'] : 0 ) + ( isset( $receipt['previous_incomplete'] ) && is_array( $receipt['previous_incomplete'] ) ? 1 : 0 );
			$gateways   = self::gateways();
			$stamp      = isset( $receipt['time'] ) && is_string( $receipt['time'] ) ? strtotime( $receipt['time'] ) : false;
			return array(
				'gateway'    => $gw,
				'title'      => $gw && isset( $gateways[ $gw ] ) ? $gateways[ $gw ]['title'] : '',
				'time'       => $stamp ? ( function_exists( 'wp_date' ) ? wp_date( 'M j, g:i a', $stamp ) : date_i18n( 'M j, g:i a', $stamp ) ) : '',
				'undo'       => $undoable,
				'context_changed' => $context_changed,
				'status'     => $status,
				'rows'       => $rows,
				'legacy'     => ! self::current_format( $receipt ),
				'unreadable' => $unreadable,
				'unfinished' => $unfinished,
				'currency'   => $show ? $show['currency'] : '',
				'decimals'   => $show ? $show['decimals'] : 2,
				'last'       => isset( $receipt['last_undo'] ) && is_array( $receipt['last_undo'] ) ? array(
					'unresolved' => array_values( array_filter( isset( $receipt['last_undo']['unresolved'] ) ? (array) $receipt['last_undo']['unresolved'] : array(), 'is_string' ) ),
				) : null,
			);
		}

		/**
		 * Undo the last apply as one group. The selected method's cells depend on each other (a blank name
		 * switches the rule off, a 0 amount charges nothing, type and limits change the amount), so they are
		 * put back only when the whole rule is exactly as the apply left it. The module switch goes back off
		 * only when this apply turned it on and no other fee setting changed since; it is switched off first.
		 * Each cell is merged into the current map, so newer edits to other methods are kept. Writes are
		 * journalled and the context is checked at every write and again afterwards; on any change the undo
		 * is reversed where its writes still hold. Native settings writes do not take this lock, so this is
		 * detection and conditional reversal, not a transaction.
		 *
		 * @return array
		 * @throws RuntimeException When nothing is recoverable.
		 */
		public static function recover() {
			self::authorize();
			$caller = self::posted_gateway();
			$lock   = WCJ_Job_Store::lock( self::LOCK, __( 'Another payment fee apply or undo is running. Nothing was changed. Try again in a moment.', 'woocommerce-jetpack' ) );
			try {
				$receipt = get_option( self::RECEIPT, null );
				if ( ! is_array( $receipt ) || empty( $receipt['entries'] ) || ! is_array( $receipt['entries'] ) ) {
					throw new RuntimeException( esc_html__( 'There is no applied change left to undo.', 'woocommerce-jetpack' ) );
				}
				// The group is undone whole or not at all: a record from an earlier build, one that did not
				// finish, or any entry or context that cannot be read refuses the undo before any write.
				if ( ! self::current_format( $receipt ) ) {
					throw new RuntimeException( esc_html__( 'This record was saved by an earlier version of this page and cannot be undone here. Nothing was changed; check all fee settings.', 'woocommerce-jetpack' ) );
				}
				if ( ! isset( $receipt['status'] ) || ! in_array( $receipt['status'], array( 'applied', 'undo_reversed' ), true ) ) {
					throw new RuntimeException( esc_html__( 'This change did not finish, so it cannot be undone here. Nothing was changed; check all fee settings.', 'woocommerce-jetpack' ) );
				}
				$gw    = self::receipt_gateway( $receipt );
				$valid = $gw ? self::valid_entries( $receipt['entries'] ) : array();
				$ctx   = self::receipt_context( $receipt, $valid );
				if ( ! $gw || ! $valid || count( $valid ) !== count( $receipt['entries'] ) || ! $ctx ) {
					throw new RuntimeException( esc_html__( 'The saved apply record cannot be read completely, so nothing was undone. Check the fee settings.', 'woocommerce-jetpack' ) );
				}
				$global = isset( $valid[ self::MODULE ] );
				$fields = array_values( array_diff( wp_list_pluck( $valid, 'field' ), array( 'module' ) ) );
				// Expected context while the written cells move back: persisted at apply, never captured now.
				$expect = array(
					'deps'   => $ctx['unwritten'],
					'others' => $ctx['others'],
				);
				$snap   = self::read_snapshot();
				$now    = self::fingerprints( $snap, $gw );
				$why    = '';
				if ( ! hash_equals( $ctx['deps'], $now['deps'] ) || ! self::context_holds( $snap, $gw, $fields, $expect, false ) ) {
					$why = __( 'This payment method\'s fee changed after your apply, so nothing was undone and it stays as it is now.', 'woocommerce-jetpack' );
				} elseif ( $global && ! hash_equals( $ctx['others'], $now['others'] ) ) {
					$why = __( 'Other fee settings changed after your apply. Turning Fees & Discounts back off would switch that newer work off too, so nothing was undone.', 'woocommerce-jetpack' );
				}
				if ( '' === $why ) {
					foreach ( $valid as $option => $entry ) {
						$holds = 'module' === $entry['field'] ? self::state_in( $snap, $option ) === $entry['after'] : self::cell( self::state_in( $snap, $option ), $gw ) === $entry['cell_after'];
						if ( ! $holds ) {
							$why = __( 'Part of your apply changed again since, so nothing was undone and it stays as it is now.', 'woocommerce-jetpack' );
							break;
						}
					}
				}
				if ( '' !== $why ) {
					return self::recover_result( 'partial', array(), array_values( wp_list_pluck( $valid, 'field' ) ), $why, $gw, $caller );
				}
				// Switch off first, then the rule in reverse apply order.
				$order = array_reverse( $valid, true );
				if ( $global ) {
					$module = $order[ self::MODULE ];
					unset( $order[ self::MODULE ] );
					$order = array( self::MODULE => $module ) + $order;
				}
				$journal = array();
				$stopped = false;
				foreach ( $order as $option => $entry ) {
					$cur = self::snapshot();
					if ( ! self::context_holds( $cur, $gw, $fields, $expect, $global ) ) {
						$stopped = true;
						break;
					}
					if ( 'module' === $entry['field'] ) {
						$ok = WCJ_Job_Store::compare_exchange( $option, $entry['after'], $entry['before'] );
					} else {
						$ok = 'done' === self::move_cell( $option, $gw, $entry['cell_after'], $entry['cell_before'], ! $entry['before']['exists'], self::state_in( $cur, $option ) );
					}
					if ( ! $ok ) {
						$stopped = true;
						break;
					}
					$journal[ $option ] = $entry;
				}
				if ( ! $stopped ) {
					$done = self::snapshot();
					foreach ( $journal as $option => $entry ) {
						$holds   = null !== $done && ( 'module' === $entry['field'] ? self::state_in( $done, $option ) === $entry['before'] : self::cell( self::state_in( $done, $option ), $gw ) === $entry['cell_before'] );
						$stopped = $stopped || ! $holds;
					}
					$stopped = $stopped || ! self::context_holds( $done, $gw, $fields, $expect, $global );
				}
				if ( ! $stopped ) {
					$receipt['entries'] = array();
					$receipt['status']  = 'restored';
					update_option( self::RECEIPT, $receipt, false );
					return self::recover_result( 'restored', array_values( wp_list_pluck( $journal, 'field' ) ), array(), '', $gw, $caller );
				}
				if ( ! $journal ) {
					// Changed before the first undo write: nothing was written, the record stays undoable as is.
					return self::recover_result( 'partial', array(), array_values( wp_list_pluck( $valid, 'field' ) ), __( 'Fee settings changed just before the undo started, so nothing was undone and everything stays as it is now.', 'woocommerce-jetpack' ), $gw, $caller );
				}
				// Something changed while undoing: put back what this undo wrote, in apply order, each only while
				// it still holds the value this undo wrote. The record is kept whatever happens.
				$back                 = self::reverse( $journal, $gw, true );
				$receipt['entries']   = $valid;
				$receipt['status']    = $back['unresolved'] ? 'undo_incomplete' : 'undo_reversed';
				$receipt['last_undo'] = array(
					'time'       => gmdate( 'c' ),
					'reapplied'  => array_keys( $back['done'] ),
					'unresolved' => array_values( wp_list_pluck( $back['unresolved'], 'field' ) ),
				);
				update_option( self::RECEIPT, $receipt, false );
				$why = $back['unresolved'] ? __( 'Settings changed while undoing. The undo was reversed where possible; the listed settings changed again and were left as they are now. Check this method in the full fee settings.', 'woocommerce-jetpack' ) : __( 'Settings changed while undoing, so the undo was reversed. This method is back as it was after your apply, and the newer change was kept.', 'woocommerce-jetpack' );
				return self::recover_result( 'partial', array(), array_values( wp_list_pluck( $back['unresolved'], 'field' ) ), $why, $gw, $caller );
			} finally {
				WCJ_Job_Store::unlock( self::LOCK, $lock );
			}
		}

		/**
		 * Recovery response with fresh state. 'ignored' is always 0: a record with any unreadable entry is
		 * refused whole before this point.
		 *
		 * @param string $status restored or partial.
		 * @param array  $restored Fields undone.
		 * @param array  $conflicts Fields kept.
		 * @param string $why Explanation for partial.
		 * @param string $gw Receipt gateway.
		 * @param string $caller Gateway the page shows.
		 * @return array
		 */
		private static function recover_result( $status, $restored, $conflicts, $why, $gw, $caller ) {
			$note = __( 'Orders were not touched.', 'woocommerce-jetpack' );
			return array(
				'status'    => $status,
				'restored'  => $restored,
				'conflicts' => array_values( array_unique( $conflicts ) ),
				'ignored'   => 0,
				'message'   => 'restored' === $status ? __( 'Undone and read from the database.', 'woocommerce-jetpack' ) . ' ' . $note : $why . ' ' . $note,
				'gateway'   => $gw,
				'fresh'     => self::load( $gw ? $gw : $caller ),
			);
		}

		/**
		 * AJAX adapter: POST, capability and per-action nonce on every action.
		 */
		public function handle() {
		if ( ! WCJ_Task_Finder::allowed( 'payment_gateways_fees' ) ) { wp_send_json_error( array( 'error' => esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ) ), 403 ); }
			try {
				self::authorize();
				$action = isset( $_POST['action'] ) && is_string( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$op     = str_replace( 'wcj_gateway_fee_job_', '', $action );
				if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! in_array( $op, self::OPS, true ) ) {
					throw new RuntimeException( esc_html__( 'Invalid request.', 'woocommerce-jetpack' ) );
				}
				check_ajax_referer( 'wcj_gateway_fee_job_' . $op, 'nonce' );
				$result = 'load' === $op ? self::load() : call_user_func( array( __CLASS__, $op ) );
				nocache_headers();
				if ( isset( $result['error'] ) ) {
					wp_send_json_error( $result, 409 );
				}
				wp_send_json_success( $result );
			} catch ( Throwable $error ) {
				nocache_headers();
				$message = $error instanceof RuntimeException ? $error->getMessage() : __( 'The request could not complete. No success is claimed; reload to see the saved values.', 'woocommerce-jetpack' );
				wp_send_json_error( array( 'error' => $message ), 400 );
			}
		}

		/**
		 * Render the page.
		 */
		public function render() {
			if ( ! WCJ_Task_Finder::allowed( 'payment_gateways_fees' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
				wp_die( esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) );
			}
			$gateways = self::gateways();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page selection, re-checked on every request.
			$gw = isset( $_GET['gateway'] ) && is_string( $_GET['gateway'] ) ? sanitize_key( wp_unslash( $_GET['gateway'] ) ) : '';
			if ( ! isset( $gateways[ $gw ] ) ) {
				$gw = '';
				foreach ( $gateways as $id => $g ) {
					if ( $g['enabled'] ) {
						$gw = $id;
						break;
					}
				}
				if ( '' === $gw && $gateways ) {
					$ids = array_keys( $gateways );
					$gw  = (string) $ids[0];
				}
			}
			wp_enqueue_style( 'wcj-invoice-job', wcj_plugin_url() . '/assets/css/admin/wcj-invoice-job.css', array(), '2.5' );
			wp_enqueue_style( 'wcj-gateway-fee-job', wcj_plugin_url() . '/assets/css/admin/wcj-gateway-fee-job.css', array( 'wcj-invoice-job' ), '1.2' );
			wp_enqueue_script( 'wcj-gateway-fee-job', wcj_plugin_url() . '/assets/js/admin/wcj-gateway-fee-job.js', array(), '1.4', true );
			$nonces = array();
			foreach ( self::OPS as $op ) {
				$nonces[ $op ] = wp_create_nonce( 'wcj_gateway_fee_job_' . $op );
			}
			wp_localize_script(
				'wcj-gateway-fee-job',
				'wcjGatewayFeeJob',
				array(
					'url'     => admin_url( 'admin-ajax.php' ),
					'nonces'  => $nonces,
					'gateway' => $gw,
					'user'    => get_current_user_id(),
					'page'    => self::url(),
					'native'  => self::native_url(),
					'explain' => self::explain_url(),
				)
			);
			include __DIR__ . '/views/gateway-fee-job.php';
		}
	}

endif;

new WCJ_Gateway_Fee_Job();
