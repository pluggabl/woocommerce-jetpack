<?php
/**
 * Booster for WooCommerce - bounded gateway-fee rule explanations.
 *
 * @version 9.0.0
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/class-wcj-rule-explanation-context.php';

if ( ! class_exists( 'WCJ_Rule_Explanations' ) ) :
	/** Shares one actual fee evaluator between merchant tools and the Ability. */
	class WCJ_Rule_Explanations {

		/** New diagnostic detail is Elite-only; existing fee entitlements are unchanged. */
		public function is_full_experience() {
			return defined( 'WCJ_PLUGIN_FILE' ) && 'booster-elite-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE );
		}

		/** Bounded identifiers for the merchant form, never fee titles or settings. */
		public function get_scenario_choices() {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return new WP_Error( 'booster_forbidden', __( 'You do not have permission to explain Booster rules.', 'woocommerce-jetpack' ) );
			}
			$gateways = array();
			foreach ( array_keys( (array) wcj_get_option( 'wcj_gateways_fees_text', array() ) ) as $id ) {
				if ( is_string( $id ) && preg_match( '/^[a-z0-9_\-]{1,64}$/D', $id ) ) {
					$gateways[] = $id;
				}
				if ( count( $gateways ) >= 50 ) {
					break;
				}
			}
			$roles = array( 'guest' );
			if ( $this->is_full_experience() ) {
				foreach ( array_keys( wp_roles()->roles ) as $role ) {
					if ( is_string( $role ) && preg_match( '/^[a-z0-9_\-]{1,64}$/D', $role ) ) {
						$roles[] = $role;
					}
					if ( count( $roles ) >= 100 ) {
						break;
					}
				}
			}
			return array(
				'gateways' => $gateways,
				'roles'    => array_values( array_unique( $roles ) ),
			);
		}

		/**
		 * Explains an explicit scenario or the last callback in this request.
		 * Never reads a shopper session, selects a gateway or applies a fee.
		 *
		 * @param array $input Bounded explicit facts, not a customer or order identifier.
		 * @return array|WP_Error Safe explanation or current authorization/input failure.
		 */
		public function explain( $input ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return new WP_Error( 'booster_forbidden', __( 'You do not have permission to explain Booster rules.', 'woocommerce-jetpack' ) );
			}
			$valid = $this->validate_input( $input );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
			$input  = $valid;
			$plugin = function_exists( 'w_c_j' ) ? w_c_j() : ( function_exists( 'WCJ' ) ? WCJ() : null );
			$module = isset( $plugin->all_modules['payment_gateways_fees'] ) ? $plugin->all_modules['payment_gateways_fees'] : null;
			if ( ! $module instanceof WCJ_Payment_Gateways_Fees ) {
				return $this->response( $input['mode'], 'unavailable', 'producer_unavailable' );
			}
			foreach ( array( 'is_enabled', 'init_options', 'wcj_get_option', 'evaluate_gateway_fee', 'get_last_fee_decision', 'resolve_fee_gateway' ) as $method ) {
				if ( ! method_exists( $module, $method ) ) {
					return $this->response( $input['mode'], 'unavailable', 'producer_unavailable' );
				}
			}
			if ( 'request_local' === $input['mode'] ) {
				return $module->is_enabled() ? $this->from_evaluation( $module->get_last_fee_decision(), 'request_local' ) : $this->response( 'request_local', 'not_evaluated', 'module_disabled' );
			}
			try {
				if ( ! $module->is_enabled() ) {
					return $this->response( 'scenario', 'not_evaluated', 'module_disabled' );
				}

				// Isolate producer options/cache from the live instance, without a constructor.
				$evaluator = clone $module;
				$evaluator->init_options();
				$gateway = $evaluator->resolve_fee_gateway( $input['gateway'] );
				if ( ! array_key_exists( $gateway, $evaluator->options['text'] ) ) {
					return $this->response( 'scenario', 'not_evaluated', 'unknown_gateway' );
				}
				$title = $evaluator->wcj_get_option( 'text', $gateway );
				if ( ! is_string( $title ) || false !== strpos( $title, '[' ) ) {
					return $this->response( 'scenario', 'unsupported', 'dynamic_title', $gateway );
				}
				$currency = isset( $plugin->all_modules['multicurrency'] ) ? $plugin->all_modules['multicurrency'] : null;
				if ( 'yes' === wcj_get_option( 'wcj_multicurrency_enabled', 'no' ) || ( $currency && $currency->is_enabled() ) ) {
					return $this->response( 'scenario', 'unsupported', 'multicurrency_context', $gateway );
				}
				if ( ! $this->is_full_experience() && ( $evaluator->wcj_get_option( 'include_products', $gateway ) || $evaluator->wcj_get_option( 'exclude_products', $gateway ) ) ) {
					return $this->response( 'scenario', 'unsupported', 'advanced_product_context', $gateway );
				}
				// Malformed stored settings must not trigger arithmetic errors in the tool.
				foreach ( array( 'value', 'min_cart_amount', 'max_cart_amount', 'round_precision' ) as $key ) {
					$value = $evaluator->wcj_get_option( $key, $gateway );
					if ( ! is_numeric( $value ) || ! is_finite( (float) $value ) || abs( (float) $value ) > 1000000000 ) {
						return $this->response( 'scenario', 'unsupported', 'invalid_configuration', $gateway );
					}
				}
				if ( ! in_array( $evaluator->wcj_get_option( 'type', $gateway ), array( 'fixed', 'percent' ), true ) || abs( (float) $evaluator->wcj_get_option( 'round_precision', $gateway ) ) > 10 ) {
					return $this->response( 'scenario', 'unsupported', 'invalid_configuration', $gateway );
				}
				if ( $this->is_full_experience() && ( $evaluator->wcj_get_option( 'include_products', $gateway ) || $evaluator->wcj_get_option( 'exclude_products', $gateway ) ) && ! array_key_exists( 'product_ids', $input ) ) {
					return $this->response( 'scenario', 'not_evaluated', 'product_fact_required', $gateway );
				}
				$role         = isset( $input['role'] ) ? $input['role'] : ( $this->is_full_experience() ? null : 'guest' );
				$role_enabled = (array) wcj_get_option( 'wcj_enable_payment_gateway_charge_discount_userwise', array() );
				if ( isset( $role_enabled[ $gateway ] ) && 'yes' === $role_enabled[ $gateway ] ) {
					if ( $this->is_full_experience() && ! array_key_exists( 'role', $input ) ) {
						return $this->response( 'scenario', 'not_evaluated', 'role_fact_required', $gateway );
					}
					$role_values = (array) wcj_get_option( 'wcj_gateways_fees_' . $role, array() );
					$value       = isset( $role_values[ $gateway ] ) ? $role_values[ $gateway ] : '';
					if ( $value && ( ! is_numeric( $value ) || ! is_finite( (float) $value ) || abs( (float) $value ) > 1000000000 ) ) {
						return $this->response( 'scenario', 'unsupported', 'invalid_configuration', $gateway );
					}
				}
				$evaluation = $evaluator->evaluate_gateway_fee( $gateway, new WCJ_Rule_Explanation_Context( $input ), $role, true );
				return $this->from_evaluation( $evaluation, 'scenario' );
			} catch ( Throwable $error ) {
				// No exception detail or raw options reach the merchant/API response.
				return $this->response( 'scenario', 'not_evaluated', 'evaluation_failed' );
			}
		}

		/** Validates direct calls as well as Ability calls.
		 *
		 * @param array $input Explicit request facts.
		 * @return array|WP_Error Normalized bounded facts or an input failure.
		 */
		private function validate_input( $input ) {
			$error = new WP_Error( 'booster_invalid_rule_context', __( 'Supply one valid, bounded merchant scenario or request-local snapshot request.', 'woocommerce-jetpack' ) );
			if ( ! is_array( $input ) || ! isset( $input['mode'] ) || ! in_array( $input['mode'], array( 'scenario', 'request_local' ), true ) ) {
				return $error;
			}
			if ( 'request_local' === $input['mode'] ) {
				return array( 'mode' ) === array_keys( $input ) ? $input : $error;
			}
			$required = array( 'mode', 'gateway', 'subtotal', 'shipping', 'tax', 'shipping_tax' );
			$allowed  = $this->is_full_experience() ? array_merge( $required, array( 'product_ids', 'role' ) ) : $required;
			if ( array_diff( $required, array_keys( $input ) ) || array_diff( array_keys( $input ), $allowed ) || ! is_string( $input['gateway'] ) || ! preg_match( '/^[a-z0-9_\-]{1,64}$/D', $input['gateway'] ) ) {
				return $error;
			}
			foreach ( array( 'subtotal', 'shipping', 'tax', 'shipping_tax' ) as $key ) {
				$value = $input[ $key ];
				// Older Ability GET requests retain numeric strings after core validation.
				if ( is_string( $value ) ) {
					if ( strlen( $value ) > 25 || ! preg_match( '/^\d+(?:\.\d+)?$/D', $value ) ) {
						return $error;
					}
					$value = (float) $value;
				}
				if ( ( ! is_int( $value ) && ! is_float( $value ) ) || ! is_finite( (float) $value ) || $value < 0 || $value > 1000000000 ) {
					return $error;
				}
				$input[ $key ] = $value;
			}
			if ( isset( $input['product_ids'] ) ) {
				// Older read-only Ability GET requests retain an explicit empty array as ''.
				if ( '' === $input['product_ids'] ) {
					$input['product_ids'] = array();
				}
				if ( ! is_array( $input['product_ids'] ) || count( $input['product_ids'] ) > 100 || ( array() !== $input['product_ids'] && array_keys( $input['product_ids'] ) !== range( 0, count( $input['product_ids'] ) - 1 ) ) ) {
					return $error;
				}
				foreach ( $input['product_ids'] as $index => $id ) {
					if ( is_string( $id ) ) {
						if ( strlen( $id ) > 25 || ! preg_match( '/^\d+$/D', $id ) || $id < 1 || $id > 2147483647 ) {
							return $error;
						}
						$id = (int) $id;
					}
					if ( ! is_int( $id ) || $id < 1 || $id > 2147483647 ) {
						return $error;
					}
					$input['product_ids'][ $index ] = $id;
				}
			}
			if ( array_key_exists( 'product_ids', $input ) && null === $input['product_ids'] ) {
				return $error;
			}
			if ( array_key_exists( 'role', $input ) && ( ! is_string( $input['role'] ) || ( 'guest' !== $input['role'] && ! isset( wp_roles()->roles[ $input['role'] ] ) ) || ! preg_match( '/^[a-z0-9_\-]{1,64}$/D', $input['role'] ) ) ) {
				return $error;
			}
			return $input;
		}

		/** Converts only versioned known producer facts.
		 *
		 * @param array  $evaluation Internal producer result.
		 * @param string $mode Scenario or request_local.
		 * @return array
		 */
		private function from_evaluation( $evaluation, $mode ) {
			if ( empty( $evaluation ) ) {
				return $this->response( $mode, 'unavailable', 'no_evaluation' );
			}
			$reasons = array( 'missing_cart_or_session', 'no_gateway_selected', 'not_configured', 'outside_cart_amount', 'product_condition_not_met', 'eligible', 'applied', 'duplicate_suppressed', 'zero_or_invalid_amount', 'evaluation_incomplete', 'dynamic_title', 'multicurrency_context', 'invalid_configuration', 'role_fact_required' );
			if ( ! is_array( $evaluation ) || ! isset( $evaluation['version'], $evaluation['reason'], $evaluation['gateway'], $evaluation['generation'], $evaluation['conditions'] ) || 1 !== $evaluation['version'] || ! in_array( $evaluation['reason'], $reasons, true ) || ! is_string( $evaluation['gateway'] ) || strlen( $evaluation['gateway'] ) > 64 || sanitize_key( $evaluation['gateway'] ) !== $evaluation['gateway'] || ! is_int( $evaluation['generation'] ) || ( 'request_local' === $mode && $evaluation['generation'] < 1 ) || ! is_array( $evaluation['conditions'] ) ) {
				return $this->response( $mode, 'unavailable', 'unknown_decision' );
			}
			$reason = $evaluation['reason'];
			if ( in_array( $reason, array( 'eligible', 'applied', 'duplicate_suppressed', 'zero_or_invalid_amount' ), true ) && ( ! isset( $evaluation['amount'] ) || array_diff( array( 'configuration', 'cart_amount', 'products', 'user_role', 'fee_amount' ), array_keys( $evaluation['conditions'] ) ) ) ) {
				return $this->response( $mode, 'unavailable', 'unknown_decision' );
			}
			$state = 'evaluation_incomplete' === $reason ? 'unavailable' : ( in_array( $reason, array( 'missing_cart_or_session', 'no_gateway_selected' ), true ) ? 'not_evaluated' : 'evaluated' );
			if ( in_array( $reason, array( 'dynamic_title', 'multicurrency_context', 'invalid_configuration' ), true ) ) {
				if ( 'scenario' !== $mode ) {
					return $this->response( $mode, 'unavailable', 'unknown_decision' );
				}
				$state = 'unsupported';
			}
			if ( 'role_fact_required' === $reason ) {
				if ( 'scenario' !== $mode ) {
					return $this->response( $mode, 'unavailable', 'unknown_decision' );
				}
				$state = 'not_evaluated';
			}
			$result               = $this->response( $mode, $state, $reason, $evaluation['gateway'] );
			$result['generation'] = $evaluation['generation'];
			if ( isset( $evaluation['amount'] ) ) {
				if ( ! is_numeric( $evaluation['amount'] ) || ! is_finite( (float) $evaluation['amount'] ) ) {
					return $this->response( $mode, 'unavailable', 'unknown_decision' );
				}
				$result['amount'] = (float) $evaluation['amount'];
			}
			foreach ( $evaluation['conditions'] as $rule => $condition ) {
				if ( ! in_array( $rule, array( 'configuration', 'cart_amount', 'products', 'user_role', 'fee_amount' ), true ) || ! in_array( $condition, array( 'not_evaluated', 'matched', 'unmatched', 'role_override', 'base_value', 'not_applicable', 'nonzero', 'zero_or_invalid' ), true ) ) {
					return $this->response( $mode, 'unavailable', 'unknown_decision' );
				}
				if ( $this->is_full_experience() ) {
					$result['conditions'][] = array(
						'rule'   => $rule,
						'result' => $condition,
					);
				}
			}
			return $result;
		}

		/** Stable response without raw configuration or customer facts.
		 *
		 * @param string $mode Context mode.
		 * @param string $state Evaluation state.
		 * @param string $reason Fixed reason code.
		 * @param string $gateway Configured gateway identifier.
		 * @return array
		 */
		private function response( $mode, $state, $reason, $gateway = '' ) {
			return array(
				'context'    => 'scenario' === $mode ? 'merchant_supplied_scenario' : 'last_callback_not_current_cart',
				'state'      => $state,
				'gateway'    => $gateway,
				'reason'     => $reason,
				'amount'     => null,
				'generation' => 0,
				'conditions' => array(),
			);
		}

		/** Ability input shape; the service also validates direct execution. */
		public function get_input_schema() {
			$number     = array(
				'type'    => 'number',
				'minimum' => 0,
				'maximum' => 1000000000,
			);
			$properties = array(
				'mode'         => array(
					'type' => 'string',
					'enum' => array( 'scenario', 'request_local' ),
				),
				'gateway'      => array(
					'type'      => 'string',
					'pattern'   => '^[a-z0-9_\-]{1,64}$',
					'maxLength' => 64,
				),
				'subtotal'     => array_merge( $number, array( 'description' => 'get_cart_contents_total: merchandise total after discounts, excluding tax.' ) ),
				'shipping'     => array_merge( $number, array( 'description' => 'get_shipping_total: shipping total excluding tax.' ) ),
				'tax'          => array_merge( $number, array( 'description' => 'get_subtotal_tax: merchandise subtotal tax before discounts.' ) ),
				'shipping_tax' => array_merge( $number, array( 'description' => 'get_shipping_tax: shipping tax, included even when shipping cost is excluded if include_taxes is enabled.' ) ),
			);
			if ( $this->is_full_experience() ) {
				$properties['product_ids'] = array(
					'description' => 'Explicit cart product facts: supply applicable parent product and variation IDs. An empty list is known empty, not omitted.',
					'type'        => 'array',
					'maxItems'    => 100,
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => 2147483647,
					),
				);
				$properties['role']        = array(
					'type'      => 'string',
					'maxLength' => 64,
					'pattern'   => '^[a-z0-9_\-]{1,64}$',
				);
			}
			return array(
				'type'                 => 'object',
				'description'          => $this->is_full_experience() ? 'One merchant-supplied fee scenario, not a shopper diagnosis. Supply role/product IDs explicitly when the configured rule depends on them. Explicit guest/empty product IDs differ from omitted facts.' : 'One merchant-supplied BASIC fee scenario, not a shopper diagnosis: guest role and no supplied product IDs. Product-dependent rules are unsupported. This preset never means an unknown shopper is guest.',
				'properties'           => $properties,
				'required'             => array( 'mode' ),
				'additionalProperties' => false,
			);
		}

		/** Output never contains the internal fee payload, title, IDs or role values. */
		public function get_output_schema() {
			$properties = array(
				'context'    => array(
					'type' => 'string',
					'enum' => array( 'merchant_supplied_scenario', 'last_callback_not_current_cart' ),
				),
				'state'      => array(
					'type' => 'string',
					'enum' => array( 'evaluated', 'not_evaluated', 'unsupported', 'unavailable' ),
				),
				'gateway'    => array(
					'type'      => 'string',
					'maxLength' => 64,
				),
				'reason'     => array(
					'type' => 'string',
					'enum' => array( 'producer_unavailable', 'module_disabled', 'unknown_gateway', 'dynamic_title', 'multicurrency_context', 'advanced_product_context', 'invalid_configuration', 'no_evaluation', 'unknown_decision', 'missing_cart_or_session', 'no_gateway_selected', 'not_configured', 'outside_cart_amount', 'product_condition_not_met', 'eligible', 'applied', 'duplicate_suppressed', 'zero_or_invalid_amount', 'evaluation_incomplete', 'role_fact_required', 'product_fact_required', 'evaluation_failed' ),
				),
				'amount'     => array( 'type' => array( 'number', 'null' ) ),
				'generation' => array(
					'type'    => 'integer',
					'minimum' => 0,
				),
				'conditions' => array(
					'type'     => 'array',
					'maxItems' => 5,
					'items'    => array(
						'type'                 => 'object',
						'properties'           => array(
							'rule'   => array(
								'type'      => 'string',
								'maxLength' => 32,
							),
							'result' => array(
								'type'      => 'string',
								'maxLength' => 32,
							),
						),
						'required'             => array( 'rule', 'result' ),
						'additionalProperties' => false,
					),
				),
			);
			return array(
				'type'                 => 'object',
				'properties'           => $properties,
				'required'             => array_keys( $properties ),
				'additionalProperties' => false,
			);
		}
		/**
		 * Request-local payment observer instance.
		 *
		 * @var WCJ_Rule_Explanations|null
		 */
		private static $payment_observer = null;
		/**
		 * Request-local payment observation window state.
		 *
		 * @var array
		 */
		private static $payment_window = array( 'state' => 'unarmed' );

		/**
		 * Request-local payment transport recognition state.
		 *
		 * @var array
		 */
		private static $payment_transport = array(
			'initialized' => false,
			'recognized'  => false,
			'failed'      => false,
		);

		/**
		 * Recognizes the request-local payment observation transport.
		 *
		 * @return mixed
		 */
		public static function initialize_payment_transport() {
			if ( self::$payment_transport['initialized'] ) {
				if ( self::$payment_transport['recognized'] ) {
					self::$payment_transport['failed'] = true;
				}
				return;
			}
			self::$payment_transport['initialized'] = true;
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Presence only; no values or admission here.
			self::$payment_transport['recognized'] = array_key_exists( 'wcj_payment_observe', $_GET ) || array_key_exists( 'wcj_payment_gateway', $_GET ) || array_key_exists( 'wcj_payment_nonce', $_GET );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( ! self::$payment_transport['recognized'] ) {
				return;
			}
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			if ( ! DONOTCACHEPAGE ) {
				self::$payment_transport['failed'] = true;
			}
			self::write_payment_transport_headers();
			add_filter( 'wp_headers', array( __CLASS__, 'payment_transport_headers' ), PHP_INT_MAX, 2 );
			add_action( 'template_redirect', array( __CLASS__, 'check_payment_transport' ), -1, 0 );
		}

		/**
		 * Fixed protection only; unrelated header values and the WP argument are untouched.
		 *
		 * @param mixed $headers Response header map.
		 * @param mixed $wp WordPress request argument, left untouched.
		 */
		public static function payment_transport_headers( $headers, $wp ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Retain the existing public/hook signature, including argument names and arity, for compatibility.
			if ( ! self::$payment_transport['recognized'] ) {
				return $headers;
			}
			foreach ( array_keys( $headers ) as $name ) {
				if ( is_string( $name ) && in_array( strtolower( $name ), array( 'referrer-policy', 'cache-control', 'expires', 'etag', 'last-modified' ), true ) ) {
					unset( $headers[ $name ] );
				}
			}
			$headers['Referrer-Policy'] = 'no-referrer';
			$headers['Cache-Control']   = 'private, no-store, no-cache, must-revalidate';
			$headers['Expires']         = 'Wed, 11 Jan 1984 05:00:00 GMT';
			$headers['Last-Modified']   = false;
			return $headers;
		}

		/** Bootstrap failure is recorded, never terminated, including non-template entry points. */
		private static function write_payment_transport_headers() {
			if ( headers_sent() ) {
				self::$payment_transport['failed'] = true;
				return false;
			}
			header( 'Referrer-Policy: no-referrer' );
			header( 'Cache-Control: private, no-store, no-cache, must-revalidate' );
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
			header_remove( 'ETag' );
			header_remove( 'Last-Modified' );
			return true;
		}

		/**
		 * Effective header confirmation, not a guarantee against a later writer or proxy.
		 *
		 * @param mixed $require_writable Whether headers must still be writable.
		 */
		private static function payment_transport_headers_valid( $require_writable = true ) {
			if ( ! self::$payment_transport['recognized'] || self::$payment_transport['failed'] || ( $require_writable && headers_sent() ) || ! defined( 'DONOTCACHEPAGE' ) || true !== DONOTCACHEPAGE ) {
				return false;
			}
			$headers = array();
			$counts  = array();
			foreach ( headers_list() as $header ) {
				$pair = explode( ':', $header, 2 );
				if ( 2 === count( $pair ) ) {
					$key             = strtolower( trim( $pair[0] ) );
					$headers[ $key ] = trim( $pair[1] );
					$counts[ $key ]  = isset( $counts[ $key ] ) ? $counts[ $key ] + 1 : 1;
				}
			}
			return isset( $headers['cache-control'], $headers['referrer-policy'], $headers['expires'] ) && 1 === $counts['cache-control'] && 1 === $counts['referrer-policy'] && 1 === $counts['expires'] && 'private, no-store, no-cache, must-revalidate' === $headers['cache-control'] && 'no-referrer' === $headers['referrer-policy'] && 'Wed, 11 Jan 1984 05:00:00 GMT' === $headers['expires'] && ! isset( $headers['etag'] ) && ! isset( $headers['last-modified'] );
		}

		/** Only the actual template_redirect path calls this; no bootstrap or wp_headers exit. */
		public static function check_payment_transport() {
			if ( ! self::$payment_transport['recognized'] ) {
				return;
			}
			if ( null === self::$payment_observer || self::$payment_transport['failed'] || 'unarmed' !== self::$payment_window['state'] ) {
				self::deny_payment_transport();
			}
			if ( ! self::write_payment_transport_headers() || ! self::payment_transport_headers_valid() ) {
				self::deny_payment_transport();
			}
		}

		/** Fixed resource-free denial; shutdown/buffer behavior still requires native qualification. */
		private static function deny_payment_transport() {
			self::$payment_window['state'] = 'invalid';
			if ( headers_sent() ) {
				exit;
			}
			self::write_payment_transport_headers();
			http_response_code( 403 );
			header( 'Content-Type: text/plain; charset=UTF-8' );
			header( 'X-Content-Type-Options: nosniff' );
			if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'HEAD' !== $_SERVER['REQUEST_METHOD'] ) {
				echo "Diagnostic observation unavailable.\n";
			}
			exit;
		}


		/** Register before either immediate or deferred wcj_loaded path fires. */
		public static function bind_payment_observer() {
			if ( null !== self::$payment_observer ) {
				return;
			}
			self::$payment_observer = new self();
			add_action( 'template_redirect', array( self::$payment_observer, 'arm_payment_observer' ), 0 );
			add_action( 'woocommerce_checkout_order_review', array( self::$payment_observer, 'begin_payment_window' ), 19 );
			add_action( 'woocommerce_checkout_order_review', array( self::$payment_observer, 'end_payment_window' ), 21 );
			add_action( 'woocommerce_checkout_after_order_review', array( self::$payment_observer, 'render_payment_observer' ), 20 );
		}

		/** Closed source order, not an inventory of third-party callbacks. */
		public static function payment_owners() {
			return array(
				'payment_gateways_per_category' => array( 'WCJ_Payment_Gateways_Per_Category', 'filter_available_payment_gateways_per_category', 100 ),
				'payment_gateways_by_user_role' => array( 'WCJ_Payment_Gateways_By_User_Role', 'available_payment_gateways', PHP_INT_MAX - 100 ),
				'payment_gateways_by_currency'  => array( 'WCJ_Payment_Gateways_By_Currency', 'available_payment_gateways', PHP_INT_MAX ),
				'payment_gateways_min_max'      => array( 'WCJ_Payment_Gateways_Min_Max', 'available_payment_gateways', PHP_INT_MAX ),
				'payment_gateways_by_country'   => array( 'WCJ_Payment_Gateways_By_Country', 'available_payment_gateways', PHP_INT_MAX ),
				'payment_gateways_by_shipping'  => array( 'WCJ_Payment_Gateways_By_Shipping', 'available_payment_gateways', PHP_INT_MAX ),
			);
		}

		/**
		 * Returns the currently loaded Booster module map.
		 *
		 * @return mixed
		 */
		private function payment_modules() {
			$plugin = function_exists( 'w_c_j' ) ? w_c_j() : ( function_exists( 'WCJ' ) ? WCJ() : null );
			return isset( $plugin->all_modules ) && is_array( $plugin->all_modules ) ? $plugin->all_modules : array();
		}

		/**
		 * Checks the expected payment callback registration and source identity.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $module Callback owner instance.
		 *
		 * @return mixed
		 */
		private function payment_registered( $owner, $module ) {
			$owners = self::payment_owners();
			$spec   = $owners[ $owner ];
			$class  = $spec[0];
			return $module instanceof $class && has_filter( 'woocommerce_available_payment_gateways', array( $module, $spec[1] ) ) === $spec[2];
		}

		/**
		 * Builds a fixed payment diagnostic error.
		 *
		 * @param mixed $code Fixed diagnostic error code.
		 *
		 * @return mixed
		 */
		private function payment_error( $code = 'booster_invalid_payment_context' ) {
			return new WP_Error( $code, __( 'Payment diagnostic context is not supported or is invalid.', 'woocommerce-jetpack' ) );
		}

		/**
		 * Checks the bounded gateway identifier grammar.
		 *
		 * @param mixed $gateway Gateway identifier.
		 *
		 * @return mixed
		 */
		private function payment_gateway_valid( $gateway ) {
			return is_string( $gateway ) && 1 === preg_match( '/^[a-zA-Z0-9_-]{1,64}$/D', $gateway );
		}

		/**
		 * Checks exact member names without requiring member order.
		 *
		 * @param mixed $value Value supplied to this method.
		 * @param mixed $keys Required member names.
		 *
		 * @return mixed
		 */
		private function payment_exact_keys( $value, $keys ) {
			return is_array( $value ) && count( $value ) === count( $keys ) && ! array_diff( $keys, array_keys( $value ) ) && ! array_diff( array_keys( $value ), $keys );
		}

		/**
		 * Checks for a zero-based consecutive list.
		 *
		 * @param mixed $value Value supplied to this method.
		 *
		 * @return mixed
		 */
		private function payment_dense( $value ) {
			return is_array( $value ) && ( array() === $value || range( 0, count( $value ) - 1 ) === array_keys( $value ) );
		}

		/**
		 * Strict native tagged facts, no query-string or boolean repair.
		 *
		 * @param mixed $tag Tagged fact to validate.
		 * @param mixed $kind Expected selector or tagged-fact kind.
		 * @param mixed $allow_empty Whether an empty tagged list is permitted.
		 */
		private function payment_tag( $tag, $kind, $allow_empty = false ) {
			if ( ! is_array( $tag ) || ! isset( $tag['state'] ) || ! is_string( $tag['state'] ) ) {
				return false;
			}
			if ( 'unknown' === $tag['state'] || ( $allow_empty && 'known_empty' === $tag['state'] ) ) {
				return $this->payment_exact_keys( $tag, array( 'state' ) );
			}
			if ( 'known' !== $tag['state'] || ! $this->payment_exact_keys( $tag, array( 'state', 'value' ) ) ) {
				return false;
			}
			$value = $tag['value'];
			if ( 'boolean' === $kind ) {
				return is_bool( $value );
			}
			if ( 'decimal' === $kind ) {
				return is_string( $value ) && strlen( $value ) <= 20 && 1 === preg_match( '/^(0|[1-9][0-9]{0,9})([.][0-9]{1,6})?$/D', $value ) && is_finite( (float) $value ) && (float) $value <= 1000000000;
			}
			if ( 'roles' === $kind || 'methods' === $kind ) {
				if ( ! $this->payment_dense( $value ) || count( $value ) < 1 || count( $value ) > 20 ) {
					return false;
				}
				foreach ( $value as $item ) {
					$pattern = 'roles' === $kind ? '/^[a-zA-Z0-9_-]{1,64}$/D' : '/^[a-zA-Z0-9_:-]{1,128}$/D';
					if ( ! is_string( $item ) || 1 !== preg_match( $pattern, $item ) ) {
						return false;
					}
				}
				if ( 'roles' === $kind ) {
					if ( count( array_unique( $value ) ) !== count( $value ) || ( in_array( 'guest', $value, true ) && array( 'guest' ) !== $value ) ) {
						return false;
					}
					$roles = wp_roles()->roles;
					foreach ( $value as $role ) {
						if ( 'guest' !== $role && ! isset( $roles[ $role ] ) ) {
							return false;
						}
					}
				}
				return true;
			}
			if ( ! is_string( $value ) ) {
				return false;
			}
			if ( 'currency' === $kind ) {
				return 1 === preg_match( '/^[A-Z]{3}$/D', $value );
			}
			if ( 'country' === $kind ) {
				return 1 === preg_match( '/^[A-Z]{2}$/D', $value );
			}
			return strlen( $value ) >= 1 && strlen( $value ) <= 64 && 0 === preg_match( '/[\x00-\x1f\x7f]/', $value );
		}

		/**
		 * Validates the closed, tagged payment facts supplied by a merchant.
		 *
		 * @param mixed $facts Explicit tagged facts.
		 *
		 * @return mixed
		 */
		private function validate_payment_facts( $facts ) {
			$keys = array( 'currency', 'roles', 'billing', 'shipping', 'contents_total', 'total_tax', 'shipping_total', 'needs_shipping', 'chosen_shipping_methods' );
			if ( ! $this->payment_exact_keys( $facts, $keys ) || ! $this->payment_tag( $facts['currency'], 'currency' ) || ! $this->payment_tag( $facts['roles'], 'roles' ) || ! $this->payment_tag( $facts['needs_shipping'], 'boolean' ) || ! $this->payment_tag( $facts['chosen_shipping_methods'], 'methods', true ) ) {
				return false;
			}
			foreach ( array( 'billing', 'shipping' ) as $address ) {
				if ( ! $this->payment_exact_keys( $facts[ $address ], array( 'country', 'state', 'postcode' ) ) ) {
					return false;
				}
				foreach ( array( 'country', 'state', 'postcode' ) as $key ) {
					if ( ! $this->payment_tag( $facts[ $address ][ $key ], $key, true ) ) {
						return false;
					}
				}
			}
			foreach ( array( 'contents_total', 'total_tax', 'shipping_total' ) as $key ) {
				if ( ! $this->payment_tag( $facts[ $key ], 'decimal' ) ) {
					return false;
				}
				if ( 'known' === $facts[ $key ]['state'] ) {
					$facts[ $key ]['value'] = (float) $facts[ $key ]['value'];
				}
			}
			return $facts;
		}

		/**
		 * Each generated row must match the full context-specific joint tuple.
		 *
		 * @param mixed $context Contract context identifier.
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $decision Decision tuple to match.
		 * @param mixed $provenance Optional observation provenance.
		 */
		private function payment_row( $context, $owner, $decision, $provenance = null ) {
			foreach ( self::payment_contract_tuples() as $tuple ) {
				if ( $context === $tuple['context'] && $owner === $tuple['owner'] && $decision[0] === $tuple['decision'] && $decision[1] === $tuple['reason'] && $decision[2] === $tuple['predicate'] && ( null === $provenance || $provenance === $tuple['provenance'] ) ) {
					return array_intersect_key( $tuple, array_flip( array( 'owner', 'decision', 'reason', 'predicate', 'provenance', 'generation', 'settings_section' ) ) );
				}
			}
			return null;
		}

		/**
		 * Validate full rows, context, uniqueness and order, not independent enums.
		 *
		 * @param mixed $rows Contract rows to validate or package.
		 * @param mixed $context Contract context identifier.
		 */
		public function validate_payment_rows( $rows, $context ) {
			if ( ! in_array( $context, array( 'explicit_merchant_scenario', 'initial_order_review_window' ), true ) || ! $this->payment_dense( $rows ) || 6 !== count( $rows ) ) {
				return false;
			}
			$owners = array_keys( self::payment_owners() );
			$stop   = null;
			foreach ( $rows as $index => $row ) {
				if ( ! $this->payment_exact_keys( $row, array( 'owner', 'decision', 'reason', 'predicate', 'provenance', 'generation', 'settings_section' ) ) || $owners[ $index ] !== $row['owner'] || $row['owner'] !== $row['settings_section'] ) {
					return false;
				}
				$expected = $this->payment_row( $context, $row['owner'], array( $row['decision'], $row['reason'], $row['predicate'] ), $row['provenance'] );
				if ( null === $expected ) {
					return false;
				}
				foreach ( $expected as $key => $value ) {
					if ( $value !== $row[ $key ] ) {
						return false;
					}
				}
				if ( 'explicit_merchant_scenario' === $context ) {
					if ( null !== $stop ) {
						if ( 'not_seen' !== $row['decision'] || $stop !== $row['reason'] ) {
							return false;
						}
					} else {
						if ( 'not_seen' === $row['decision'] ) {
							return false;
						}
						$effect = null;
						foreach ( self::payment_contract_tuples() as $tuple ) {
							if ( $context === $tuple['context'] && array_intersect_key( $tuple, array_flip( array_keys( $expected ) ) ) === $expected ) {
								$effect = $tuple['chain_effect'];
								break;
							}
						}
						if ( 'stop_removed' === $effect ) {
							$stop = 'earlier_owner_removed';
						} elseif ( 'stop_unresolved' === $effect ) {
							$stop = 'earlier_owner_unresolved';
						} elseif ( 'continue' !== $effect ) {
								return false;
						}
					}
				}
			}
			return true;
		}

		/**
		 * Builds a payment response only from valid contract rows.
		 *
		 * @param mixed $rows Contract rows to validate or package.
		 * @param mixed $context Contract context identifier.
		 *
		 * @return mixed
		 */
		private function payment_response( $rows, $context ) {
			if ( ! $this->validate_payment_rows( $rows, $context ) ) {
				return $this->payment_error( 'booster_invalid_payment_receipt' );
			}
			return array(
				'schema_version'                 => 1,
				'context'                        => $context,
				'scope'                          => 'six_booster_payment_filters_only',
				'final_availability'             => null,
				'observations_atomic'            => false,
				'gateway_intrinsic_availability' => 'not_checked',
				'custom_gateway_availability'    => 'not_checked',
				'third_party_availability'       => 'not_checked',
				'blocks_browser_availability'    => 'not_checked',
				'rows'                           => $rows,
			);
		}

		/**
		 * Elite scenario, sequential lazy owner admission, no availability calls.
		 *
		 * @param mixed $input Explicit diagnostic input.
		 */
		public function explain_payment_options( $input ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return $this->payment_error( 'booster_forbidden' );
			}
			if ( ! $this->is_full_experience() ) {
				return $this->payment_error( 'booster_elite_required' );
			}
			if ( ! $this->payment_exact_keys( $input, array( 'mode', 'gateway', 'facts' ) ) || 'scenario' !== $input['mode'] || ! $this->payment_gateway_valid( $input['gateway'] ) ) {
				return $this->payment_error();
			}
			$facts = $this->validate_payment_facts( $input['facts'] );
			if ( false === $facts ) {
				return $this->payment_error();
			}
			if ( wcj_is_module_enabled( 'modules_by_user_roles' ) ) {
				return $this->payment_error( 'booster_context_not_supported' );
			}
			$modules = $this->payment_modules();
			$rows    = array();
			$stop    = null;
			foreach ( self::payment_owners() as $owner => $spec ) {
				if ( null !== $stop ) {
					$decision = array( 'not_seen', $stop, null );
				} else {
					$module     = isset( $modules[ $owner ] ) ? $modules[ $owner ] : null;
					$allowed    = ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( $owner );
					$enabled    = $allowed && 'yes' === wcj_get_option( 'wcj_' . $owner . '_enabled', 'no' );
					$registered = $this->payment_registered( $owner, $module );
					if ( ! $allowed && ! $registered ) {
						$decision = array( 'not_checked', 'callback_not_registered', null );
					} elseif ( $allowed && ! $enabled && ! $registered ) {
						$decision = array( 'not_checked', 'module_disabled', null );
					} elseif ( ! $allowed || ! $enabled || ! $registered ) {
						$decision = array( 'not_checked', 'module_admission_denied', null );
					} elseif ( 'payment_gateways_per_category' === $owner ) {
						$decision = array( 'not_checked', 'unsupported_catalogue_scenario', 'catalogue' );
					} else {
						$decision = $module->explain_payment_gateway( $input['gateway'], $facts );
					}
				}
				$row = $this->payment_row( 'explicit_merchant_scenario', $owner, $decision );
				if ( null === $row ) {
					return $this->payment_error( 'booster_invalid_payment_receipt' );
				}
				$rows[] = $row;
				foreach ( self::payment_contract_tuples() as $tuple ) {
					if ( 'explicit_merchant_scenario' === $tuple['context'] && $tuple['owner'] === $owner && $tuple['decision'] === $decision[0] && $tuple['reason'] === $decision[1] && $tuple['predicate'] === $decision[2] ) {
						if ( 'stop_removed' === $tuple['chain_effect'] ) {
							$stop = 'earlier_owner_removed';
						} elseif ( 'stop_unresolved' === $tuple['chain_effect'] ) {
							$stop = 'earlier_owner_unresolved';
						}
						break;
					}
				}
			}
			return $this->payment_response( $rows, 'explicit_merchant_scenario' );
		}

		/**
		 * Compares two admitted, independent payment scenarios.
		 *
		 * @param mixed $input Explicit diagnostic input.
		 *
		 * @return mixed
		 */
		public function compare_payment_options( $input ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return $this->payment_error( 'booster_forbidden' );
			}
			if ( ! $this->is_full_experience() ) {
				return $this->payment_error( 'booster_elite_required' );
			}
			if ( ! $this->payment_exact_keys( $input, array( 'mode', 'gateway', 'scenarios' ) ) || 'compare' !== $input['mode'] || ! $this->payment_gateway_valid( $input['gateway'] ) || ! $this->payment_dense( $input['scenarios'] ) || 2 !== count( $input['scenarios'] ) ) {
				return $this->payment_error();
			}
			foreach ( $input['scenarios'] as $facts ) {
				if ( false === $this->validate_payment_facts( $facts ) ) {
					return $this->payment_error();
				}
			}
			if ( wcj_is_module_enabled( 'modules_by_user_roles' ) ) {
				return $this->payment_error( 'booster_context_not_supported' );
			}
			$results = array();
			foreach ( $input['scenarios'] as $facts ) {
				$result = $this->explain_payment_options(
					array(
						'mode'    => 'scenario',
						'gateway' => $input['gateway'],
						'facts'   => $facts,
					)
				);
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				$results[] = $result;
			}
			return array(
				'schema_version'      => 1,
				'context'             => 'explicit_merchant_scenario',
				'observations_atomic' => false,
				'results'             => $results,
			);
		}

		/**
		 * Blog, actor and selected key are one private nonce identity.
		 *
		 * @param mixed $gateway Gateway identifier.
		 */
		public function payment_observation_link( $gateway ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) || ! $this->payment_gateway_valid( $gateway ) ) {
				return $this->payment_error( 'booster_forbidden' );
			}
			$action = 'wcj_payment_observe:' . get_current_blog_id() . ':' . get_current_user_id() . ':' . $gateway;
			return add_query_arg(
				array(
					'wcj_payment_observe' => '1',
					'wcj_payment_gateway' => $gateway,
					'wcj_payment_nonce'   => wp_create_nonce( $action ),
				),
				wc_get_checkout_url()
			);
		}

		/**
		 * Rechecks the captured payment actor, blog, gateway and nonce.
		 *
		 * @return mixed
		 */
		private function payment_identity_valid() {
			$w = self::$payment_window;
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			return isset( $w['actor'], $w['blog'], $w['gateway'], $w['nonce'] ) && is_user_logged_in() && current_user_can( 'manage_woocommerce' ) && get_current_user_id() === $w['actor'] && get_current_blog_id() === $w['blog'] && wp_verify_nonce( $w['nonce'], 'wcj_payment_observe:' . $w['blog'] . ':' . $w['actor'] . ':' . $w['gateway'] );
		}

		/**
		 * Checks the effective payment observation response headers.
		 *
		 * @return mixed
		 */
		private function payment_headers_valid() {
			return self::payment_transport_headers_valid( false );
		}

		/**
		 * Admits the request-local payment observation window.
		 *
		 * @return mixed
		 */
		public function arm_payment_observer() {
			if ( ! self::$payment_transport['recognized'] ) {
				return;
			}
			if ( 'unarmed' !== self::$payment_window['state'] || self::$payment_observer !== $this ) {
				self::deny_payment_transport();
			}
			if ( ! self::write_payment_transport_headers() || ! self::payment_transport_headers_valid() ) {
				self::deny_payment_transport();
			}
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_checkout() || is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
				self::deny_payment_transport();
			}
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Exact bound nonce checked below before arming.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
			if ( ! isset( $_GET['wcj_payment_observe'], $_GET['wcj_payment_gateway'], $_GET['wcj_payment_nonce'] ) || '1' !== $_GET['wcj_payment_observe'] || ! $this->payment_gateway_valid( $_GET['wcj_payment_gateway'] ) || ! is_string( $_GET['wcj_payment_nonce'] ) || 1 !== preg_match( '/^[a-zA-Z0-9]{1,64}$/D', $_GET['wcj_payment_nonce'] ) ) {
				self::deny_payment_transport();
			}
			self::$payment_window = array(
				'state'   => 'unarmed',
				'actor'   => get_current_user_id(),
				'blog'    => get_current_blog_id(),
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
				'gateway' => $_GET['wcj_payment_gateway'],
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
				'nonce'   => $_GET['wcj_payment_nonce'],
				'counts'  => array(),
				'rows'    => array(),
				'entry'   => array(),
				'renders' => 0,
			);
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( ! $this->payment_identity_valid() || headers_sent() || ( defined( 'DONOTCACHEPAGE' ) && ! DONOTCACHEPAGE ) || wcj_is_module_enabled( 'modules_by_user_roles' ) ) {
				self::deny_payment_transport();
			}
			if ( ! self::write_payment_transport_headers() || ! self::payment_transport_headers_valid() ) {
				self::deny_payment_transport();
			}
			self::$payment_window['modules'] = $this->payment_modules();
			self::$payment_window['state']   = 'armed';
		}

		/**
		 * Opens an admitted initial order-review observation window.
		 *
		 * @return mixed
		 */
		public function begin_payment_window() {
			if ( 'unarmed' === self::$payment_window['state'] ) {
				return;
			}
			if ( 'armed' !== self::$payment_window['state'] || ! $this->payment_identity_valid() ) {
				self::$payment_window['state'] = 'invalid';
				return;
			}
			self::$payment_window['state'] = 'open';
		}

		/**
		 * Closes an admitted initial order-review observation window.
		 *
		 * @return mixed
		 */
		public function end_payment_window() {
			if ( 'unarmed' === self::$payment_window['state'] ) {
				return;
			}
			if ( 'open' !== self::$payment_window['state'] || ! $this->payment_identity_valid() ) {
				self::$payment_window['state'] = 'invalid';
				return;
			}
			self::$payment_window['state'] = 'closed';
		}

		/**
		 * The unarmed hot path is one request-local state check only.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $gateways Gateway map supplied by the callback.
		 */
		public static function payment_start( $module, $gateways ) {
			if ( 'open' !== self::$payment_window['state'] ) {
				return null;
			}
			$owner = $module->id;
			if ( ! isset( self::$payment_window['modules'][ $owner ] ) || self::$payment_window['modules'][ $owner ] !== $module ) {
				return null;
			}
			$count                                    = isset( self::$payment_window['counts'][ $owner ] ) ? self::$payment_window['counts'][ $owner ] : array( 0, 0 );
			$count[0]                                 = min( 2, $count[0] + 1 );
			self::$payment_window['counts'][ $owner ] = $count;
			self::$payment_window['entry'][ $owner ]  = array_key_exists( self::$payment_window['gateway'], $gateways );
			if ( $count[0] > 1 ) {
				unset( self::$payment_window['rows'][ $owner ] );
			}
			return self::$payment_window['gateway'];
		}

		/**
		 * Records a completed payment owner callback within the open window.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $ticket Request-local observation ticket.
		 * @param mixed $row Value supplied to this method.
		 * @param mixed $whole Complete callback result, when supplied.
		 *
		 * @return mixed
		 */
		public static function payment_finish( $module, $ticket, $row, $whole ) {
			if ( null === $ticket || 'open' !== self::$payment_window['state'] ) {
				return;
			}
			$owner                                    = $module->id;
			$count                                    = self::$payment_window['counts'][ $owner ];
			$count[1]                                 = min( 2, $count[1] + 1 );
			self::$payment_window['counts'][ $owner ] = $count;
			if ( 1 !== $count[0] || 1 !== $count[1] ) {
				unset( self::$payment_window['rows'][ $owner ] );
				return;
			}
			if ( ! $whole && ! self::$payment_window['entry'][ $owner ] ) {
				$row = array( 'not_checked', 'selected_gateway_not_seen', null );
			}
			self::$payment_window['rows'][ $owner ] = $row;
		}

		/** Registration sampled once per owner only after the finite epoch closes. */
		public function get_payment_observation() {
			if ( 'closed' !== self::$payment_window['state'] || ! $this->payment_identity_valid() ) {
				return $this->payment_error();
			}
			$rows = array();
			foreach ( self::payment_owners() as $owner => $spec ) {
				$module     = isset( self::$payment_window['modules'][ $owner ] ) ? self::$payment_window['modules'][ $owner ] : null;
				$registered = $this->payment_registered( $owner, $module );
				$count      = isset( self::$payment_window['counts'][ $owner ] ) ? self::$payment_window['counts'][ $owner ] : array( 0, 0 );
				$provenance = 'not_observed';
				if ( $count[0] > 1 || $count[1] > 1 ) {
					$decision = array( 'not_checked', 'ambiguous_window_calls', null );
				} elseif ( $count[0] > $count[1] ) {
					$decision = array( 'not_checked', 'incomplete_window_call', null );
				} elseif ( 1 === $count[0] && 1 === $count[1] ) {
					$decision   = self::$payment_window['rows'][ $owner ];
					$provenance = 'normal_request_callback';
				} else {
					$decision = array( 'not_checked', $registered ? 'no_observation' : 'callback_not_registered', null );
				}
				if ( ! is_array( $decision ) || 3 !== count( $decision ) ) {
					return $this->payment_error( 'booster_invalid_payment_receipt' );
				}
				$row = $this->payment_row( 'initial_order_review_window', $owner, $decision, $provenance );
				if ( null === $row ) {
					return $this->payment_error( 'booster_invalid_payment_receipt' );
				}
				$rows[] = $row;
			}
			return $this->payment_response( $rows, 'initial_order_review_window' );
		}

		/**
		 * Renders a closed payment observation after current admission checks.
		 *
		 * @return mixed
		 */
		public function render_payment_observer() {
			if ( 'unarmed' === self::$payment_window['state'] ) {
				return;
			}
			if ( 'closed' !== self::$payment_window['state'] || ! $this->payment_identity_valid() || ! $this->payment_headers_valid() || ! isset( self::$payment_window['renders'] ) || 0 !== self::$payment_window['renders'] ) {
				self::$payment_window['state'] = 'invalid';
				return;
			}
			self::$payment_window['renders'] = 1;
			$result                          = $this->get_payment_observation();
			if ( is_wp_error( $result ) ) {
				self::$payment_window['state'] = 'invalid';
				return;
			}
			echo '<section class="wcj-payment-observation">';
			foreach ( self::payment_observation_labels() as $label ) {
				echo '<p>' . esc_html( $label ) . '</p>';
			}
			$this->render_payment_rows( $result );
			echo '</section>';
		}

		/**
		 * Renders only validated payment diagnostic rows.
		 *
		 * @param mixed $result Callback or diagnostic result.
		 *
		 * @return mixed
		 */
		public function render_payment_rows( $result ) {
			if ( ! is_array( $result ) || ! isset( $result['rows'], $result['context'] ) || ! $this->validate_payment_rows( $result['rows'], $result['context'] ) ) {
				return;
			}
			echo '<table><tbody>';
			foreach ( $result['rows'] as $row ) {
				$link = add_query_arg(
					array(
						'page'    => 'wc-settings',
						'tab'     => 'jetpack',
						'section' => $row['settings_section'],
					),
					admin_url( 'admin.php' )
				);
				echo '<tr><td>' . esc_html( $row['owner'] ) . '</td><td>' . esc_html( $row['decision'] ) . '</td><td>' . esc_html( $row['reason'] ) . '</td><td><a href="' . esc_url( $link ) . '">' . esc_html__( 'Settings', 'woocommerce-jetpack' ) . '</a></td></tr>';
			}
			echo '</tbody></table>';
		}

		/**
		 * Returns the fixed limitations shown with payment observations.
		 *
		 * @return mixed
		 */
		public static function payment_observation_labels() {
			return array(
				'Initial order-review window observation only. It is not current payment availability and does not update after checkout refreshes.',
				'Each row is a separate observed call; rows are not one combined availability decision.',
				'Only six named Booster filters are covered. Intrinsic, custom gateway, third-party and Blocks availability are not checked.',
				'Not configured does not mean installed, enabled or available.',
				'Observed for your own logged-in account; customers with other roles may differ.',
			);
		}

		/** Exact adopted contract06 data, not merchant settings. */
		private static function payment_contract_tuples() {
			static $data = null;
			if ( null === $data ) {
				$data = json_decode(
					<<<'WCJ_PAYMENT_CONTRACT'
[{"context":"explicit_merchant_scenario","owner":"payment_gateways_per_category","decision":"not_checked","reason":"unsupported_catalogue_scenario","predicate":"catalogue","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_per_category","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_per_category","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_per_category","decision":"not_checked","reason":"module_admission_denied","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_per_category","decision":"not_checked","reason":"module_disabled","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_per_category","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_per_category","decision":"not_seen","reason":"earlier_owner_removed","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_per_category","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_per_category","decision":"not_seen","reason":"earlier_owner_unresolved","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"removed","reason":"include_miss","predicate":"role","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"removed","reason":"exclude_match","predicate":"role","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"not_configured","reason":"no_rules","predicate":"role","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"retained","reason":"rules_pass","predicate":"role","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"not_checked","reason":"missing_fact","predicate":"role","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"unsupported","reason":"dynamic_role_policy","predicate":"role","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"not_checked","reason":"module_admission_denied","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"not_checked","reason":"module_disabled","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"not_seen","reason":"earlier_owner_removed","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_user_role","decision":"not_seen","reason":"earlier_owner_unresolved","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_currency","decision":"removed","reason":"include_miss","predicate":"currency","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_currency","decision":"removed","reason":"exclude_match","predicate":"currency","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_currency","decision":"not_configured","reason":"no_rules","predicate":"currency","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_currency","decision":"retained","reason":"rules_pass","predicate":"currency","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_currency","decision":"not_checked","reason":"missing_fact","predicate":"currency","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_currency","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_currency","decision":"not_checked","reason":"module_admission_denied","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_currency","decision":"not_checked","reason":"module_disabled","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_currency","decision":"not_seen","reason":"earlier_owner_removed","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_currency","decision":"not_seen","reason":"earlier_owner_unresolved","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"removed","reason":"below_min","predicate":"total","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"removed","reason":"above_max","predicate":"total","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"not_configured","reason":"no_limits","predicate":"total","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"retained","reason":"limits_pass","predicate":"total","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"not_checked","reason":"missing_fact","predicate":"total","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"unsupported","reason":"live_conversion","predicate":"total","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"not_checked","reason":"module_admission_denied","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"not_checked","reason":"module_disabled","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"not_seen","reason":"earlier_owner_removed","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_min_max","decision":"not_seen","reason":"earlier_owner_unresolved","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"not_checked","reason":"empty_location_facts","predicate":null,"provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"retained","reason":"reached_location_rules_pass","predicate":null,"provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"not_configured","reason":"no_rules","predicate":null,"provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"unsupported","reason":"by_ip_location","predicate":"country","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"removed","reason":"include_miss","predicate":"country","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"removed","reason":"exclude_match","predicate":"country","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"not_checked","reason":"missing_fact","predicate":"country","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"removed","reason":"include_miss","predicate":"state","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"removed","reason":"exclude_match","predicate":"state","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"not_checked","reason":"missing_fact","predicate":"state","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"removed","reason":"include_miss","predicate":"postcode","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"removed","reason":"exclude_match","predicate":"postcode","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"not_checked","reason":"missing_fact","predicate":"postcode","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"not_checked","reason":"module_admission_denied","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"not_checked","reason":"module_disabled","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"not_seen","reason":"earlier_owner_removed","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_country","decision":"not_seen","reason":"earlier_owner_unresolved","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"not_configured","reason":"no_rules","predicate":"shipping","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"retained","reason":"shipping_not_required","predicate":"shipping","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"removed","reason":"no_shipping_selection","predicate":"shipping","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"removed","reason":"multiple_shipping_selections","predicate":"shipping","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"removed","reason":"invalid_shipping_selection","predicate":"shipping","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"removed","reason":"invalid_shipping_instance","predicate":"shipping","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"retained","reason":"shipping_method_match","predicate":"shipping","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"removed","reason":"shipping_method_mismatch","predicate":"shipping","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"missing_fact","predicate":"needs_shipping","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"missing_fact","predicate":"chosen_shipping_methods","provenance":"explicit_merchant_scenario","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"module_admission_denied","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"module_disabled","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"continue","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"not_seen","reason":"earlier_owner_removed","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"ordered_scenario_only"},{"context":"explicit_merchant_scenario","owner":"payment_gateways_by_shipping","decision":"not_seen","reason":"earlier_owner_unresolved","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_unresolved","chain_application":"ordered_scenario_only"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"not_checked","reason":"no_gateways","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"not_checked","reason":"missing_cart","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"not_checked","reason":"no_products","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"not_configured","reason":"no_rules","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"removed","reason":"include_miss","predicate":"category","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"removed","reason":"exclude_match","predicate":"category","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"removed","reason":"include_miss","predicate":"product","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"removed","reason":"exclude_match","predicate":"product","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"retained","reason":"rules_pass","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"not_checked","reason":"category_terms_uncertain","predicate":"category","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"not_checked","reason":"selected_gateway_not_seen","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_per_category","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"not_checked","reason":"no_observation","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"not_checked","reason":"ambiguous_window_calls","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_per_category","decision":"not_checked","reason":"incomplete_window_call","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_per_category","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_user_role","decision":"removed","reason":"include_miss","predicate":"role","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_user_role","decision":"removed","reason":"exclude_match","predicate":"role","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_user_role","decision":"not_configured","reason":"no_rules","predicate":"role","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_user_role","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_user_role","decision":"retained","reason":"rules_pass","predicate":"role","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_user_role","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_user_role","decision":"not_checked","reason":"selected_gateway_not_seen","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_user_role","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_user_role","decision":"not_checked","reason":"no_observation","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_user_role","decision":"not_checked","reason":"ambiguous_window_calls","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_user_role","decision":"not_checked","reason":"incomplete_window_call","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_user_role","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_currency","decision":"removed","reason":"include_miss","predicate":"currency","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_currency","decision":"removed","reason":"exclude_match","predicate":"currency","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_currency","decision":"not_configured","reason":"no_rules","predicate":"currency","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_currency","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_currency","decision":"retained","reason":"rules_pass","predicate":"currency","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_currency","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_currency","decision":"not_checked","reason":"selected_gateway_not_seen","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_currency","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_currency","decision":"not_checked","reason":"no_observation","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_currency","decision":"not_checked","reason":"ambiguous_window_calls","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_currency","decision":"not_checked","reason":"incomplete_window_call","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_currency","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_min_max","decision":"not_checked","reason":"missing_cart","predicate":"total","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_min_max","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_min_max","decision":"removed","reason":"below_min","predicate":"total","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_min_max","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_min_max","decision":"removed","reason":"above_max","predicate":"total","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_min_max","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_min_max","decision":"not_configured","reason":"no_limits","predicate":"total","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_min_max","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_min_max","decision":"retained","reason":"limits_pass","predicate":"total","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_min_max","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_min_max","decision":"not_checked","reason":"selected_gateway_not_seen","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_min_max","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_min_max","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_min_max","decision":"not_checked","reason":"no_observation","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_min_max","decision":"not_checked","reason":"ambiguous_window_calls","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_min_max","decision":"not_checked","reason":"incomplete_window_call","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_min_max","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"not_checked","reason":"empty_location_facts","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_country","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"retained","reason":"reached_location_rules_pass","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_country","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"not_configured","reason":"no_rules","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_country","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"not_checked","reason":"selected_gateway_not_seen","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_country","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"removed","reason":"include_miss","predicate":"country","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"removed","reason":"exclude_match","predicate":"country","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"removed","reason":"include_miss","predicate":"state","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"removed","reason":"exclude_match","predicate":"state","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"removed","reason":"include_miss","predicate":"postcode","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"removed","reason":"exclude_match","predicate":"postcode","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_country","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"not_checked","reason":"no_observation","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"not_checked","reason":"ambiguous_window_calls","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_country","decision":"not_checked","reason":"incomplete_window_call","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_country","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"not_configured","reason":"no_rules","predicate":"shipping","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_shipping","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"retained","reason":"shipping_not_required","predicate":"shipping","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_shipping","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"removed","reason":"no_shipping_selection","predicate":"shipping","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"removed","reason":"multiple_shipping_selections","predicate":"shipping","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"removed","reason":"invalid_shipping_selection","predicate":"shipping","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"removed","reason":"invalid_shipping_instance","predicate":"shipping","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"retained","reason":"shipping_method_match","predicate":"shipping","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_shipping","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"removed","reason":"shipping_method_mismatch","predicate":"shipping","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_removed","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"selected_gateway_not_seen","predicate":null,"provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"invalid_shipping_selection_shape","predicate":"shipping","provenance":"normal_request_callback","generation":1,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"callback_not_registered","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"continue","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"no_observation","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"ambiguous_window_calls","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"},{"context":"initial_order_review_window","owner":"payment_gateways_by_shipping","decision":"not_checked","reason":"incomplete_window_call","predicate":null,"provenance":"not_observed","generation":null,"settings_section":"payment_gateways_by_shipping","chain_effect":"stop_unresolved","chain_application":"local_outcome_only_never_gate_other_observer_rows"}]
WCJ_PAYMENT_CONTRACT
					,
					true
				);
			}
			return $data;
		}

		/** Exact adopted contract06 data, not merchant settings. */
		private static function payment_contract_schemas() {
			static $data = null;
			if ( null === $data ) {
				$data = json_decode(
					<<<'WCJ_PAYMENT_CONTRACT'
{"booster/explain-payment-options":{"entitlement":"Elite only before any config reads","input_schema":{"type":"object","additionalProperties":false,"required":["mode","gateway","facts"],"properties":{"mode":{"type":"string","enum":["scenario"]},"gateway":{"type":"string","minLength":1,"maxLength":64,"pattern":"^[a-zA-Z0-9_-]+$"},"facts":{"type":"object","additionalProperties":false,"required":["currency","roles","billing","shipping","contents_total","total_tax","shipping_total","needs_shipping","chosen_shipping_methods"],"properties":{"currency":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","minLength":3,"maxLength":3,"pattern":"^[A-Z]{3}$"}}}]},"roles":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"array","minItems":1,"maxItems":20,"uniqueItems":true,"items":{"type":"string","minLength":1,"maxLength":64,"pattern":"^[a-zA-Z0-9_-]+$"}}}}]},"billing":{"type":"object","additionalProperties":false,"required":["country","state","postcode"],"properties":{"country":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","pattern":"^[A-Z]{2}$","minLength":2,"maxLength":2}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]},"state":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","minLength":1,"maxLength":64}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]},"postcode":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","minLength":1,"maxLength":64}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]}}},"shipping":{"type":"object","additionalProperties":false,"required":["country","state","postcode"],"properties":{"country":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","pattern":"^[A-Z]{2}$","minLength":2,"maxLength":2}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]},"state":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","minLength":1,"maxLength":64}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]},"postcode":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","minLength":1,"maxLength":64}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]}}},"contents_total":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","maxLength":20,"pattern":"^(0|[1-9][0-9]{0,9})([.][0-9]{1,6})?$"}}}]},"total_tax":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","maxLength":20,"pattern":"^(0|[1-9][0-9]{0,9})([.][0-9]{1,6})?$"}}}]},"shipping_total":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","maxLength":20,"pattern":"^(0|[1-9][0-9]{0,9})([.][0-9]{1,6})?$"}}}]},"needs_shipping":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"boolean"}}}]},"chosen_shipping_methods":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"array","minItems":1,"maxItems":20,"uniqueItems":false,"items":{"type":"string","minLength":1,"maxLength":128,"pattern":"^[a-zA-Z0-9_:-]+$"}}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]}}}}},"output_schema":{"type":"object","additionalProperties":false,"required":["schema_version","context","scope","final_availability","observations_atomic","gateway_intrinsic_availability","custom_gateway_availability","third_party_availability","blocks_browser_availability","rows"],"properties":{"schema_version":{"type":"integer","enum":[1]},"context":{"type":"string","enum":["explicit_merchant_scenario"]},"scope":{"type":"string","enum":["six_booster_payment_filters_only"]},"final_availability":{"type":"null"},"observations_atomic":{"type":"boolean","enum":[false]},"gateway_intrinsic_availability":{"type":"string","enum":["not_checked"]},"custom_gateway_availability":{"type":"string","enum":["not_checked"]},"third_party_availability":{"type":"string","enum":["not_checked"]},"blocks_browser_availability":{"type":"string","enum":["not_checked"]},"rows":{"type":"array","minItems":6,"maxItems":6,"items":{"type":"object","additionalProperties":false,"required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category","payment_gateways_by_user_role","payment_gateways_by_currency","payment_gateways_min_max","payment_gateways_by_country","payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked","not_seen","removed","not_configured","retained","unsupported"]},"reason":{"type":"string","enum":["unsupported_catalogue_scenario","callback_not_registered","module_admission_denied","module_disabled","earlier_owner_removed","earlier_owner_unresolved","include_miss","exclude_match","no_rules","rules_pass","missing_fact","dynamic_role_policy","below_min","above_max","no_limits","limits_pass","live_conversion","empty_location_facts","reached_location_rules_pass","by_ip_location","shipping_not_required","no_shipping_selection","multiple_shipping_selections","invalid_shipping_selection","invalid_shipping_instance","shipping_method_match","shipping_method_mismatch"]},"predicate":{"anyOf":[{"type":"null"},{"type":"string","enum":["catalogue","role","currency","total","country","state","postcode","shipping","needs_shipping","chosen_shipping_methods"]}]},"provenance":{"type":"string","enum":["explicit_merchant_scenario","not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category","payment_gateways_by_user_role","payment_gateways_by_currency","payment_gateways_min_max","payment_gateways_by_country","payment_gateways_by_shipping"]}},"oneOf":[{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["unsupported_catalogue_scenario"]},"predicate":{"type":"string","enum":["catalogue"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["include_miss"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["exclude_match"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_configured"]},"reason":{"type":"string","enum":["no_rules"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["rules_pass"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["unsupported"]},"reason":{"type":"string","enum":["dynamic_role_policy"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["include_miss"]},"predicate":{"type":"string","enum":["currency"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["exclude_match"]},"predicate":{"type":"string","enum":["currency"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_configured"]},"reason":{"type":"string","enum":["no_rules"]},"predicate":{"type":"string","enum":["currency"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["rules_pass"]},"predicate":{"type":"string","enum":["currency"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["currency"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["below_min"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["above_max"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_configured"]},"reason":{"type":"string","enum":["no_limits"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["limits_pass"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["unsupported"]},"reason":{"type":"string","enum":["live_conversion"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["empty_location_facts"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["reached_location_rules_pass"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_configured"]},"reason":{"type":"string","enum":["no_rules"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["unsupported"]},"reason":{"type":"string","enum":["by_ip_location"]},"predicate":{"type":"string","enum":["country"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["include_miss"]},"predicate":{"type":"string","enum":["country"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["exclude_match"]},"predicate":{"type":"string","enum":["country"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["country"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["include_miss"]},"predicate":{"type":"string","enum":["state"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["exclude_match"]},"predicate":{"type":"string","enum":["state"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["state"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["include_miss"]},"predicate":{"type":"string","enum":["postcode"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["exclude_match"]},"predicate":{"type":"string","enum":["postcode"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["postcode"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_configured"]},"reason":{"type":"string","enum":["no_rules"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["shipping_not_required"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["no_shipping_selection"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["multiple_shipping_selections"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["invalid_shipping_selection"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["invalid_shipping_instance"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["shipping_method_match"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["shipping_method_mismatch"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["needs_shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["chosen_shipping_methods"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}}]}}}}},"booster/compare-payment-options":{"entitlement":"Elite only before any config reads","input_schema":{"type":"object","additionalProperties":false,"required":["mode","gateway","scenarios"],"properties":{"mode":{"type":"string","enum":["compare"]},"gateway":{"type":"string","minLength":1,"maxLength":64,"pattern":"^[a-zA-Z0-9_-]+$"},"scenarios":{"type":"array","minItems":2,"maxItems":2,"items":{"type":"object","additionalProperties":false,"required":["currency","roles","billing","shipping","contents_total","total_tax","shipping_total","needs_shipping","chosen_shipping_methods"],"properties":{"currency":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","minLength":3,"maxLength":3,"pattern":"^[A-Z]{3}$"}}}]},"roles":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"array","minItems":1,"maxItems":20,"uniqueItems":true,"items":{"type":"string","minLength":1,"maxLength":64,"pattern":"^[a-zA-Z0-9_-]+$"}}}}]},"billing":{"type":"object","additionalProperties":false,"required":["country","state","postcode"],"properties":{"country":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","pattern":"^[A-Z]{2}$","minLength":2,"maxLength":2}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]},"state":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","minLength":1,"maxLength":64}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]},"postcode":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","minLength":1,"maxLength":64}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]}}},"shipping":{"type":"object","additionalProperties":false,"required":["country","state","postcode"],"properties":{"country":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","pattern":"^[A-Z]{2}$","minLength":2,"maxLength":2}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]},"state":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","minLength":1,"maxLength":64}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]},"postcode":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","minLength":1,"maxLength":64}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]}}},"contents_total":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","maxLength":20,"pattern":"^(0|[1-9][0-9]{0,9})([.][0-9]{1,6})?$"}}}]},"total_tax":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","maxLength":20,"pattern":"^(0|[1-9][0-9]{0,9})([.][0-9]{1,6})?$"}}}]},"shipping_total":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"string","maxLength":20,"pattern":"^(0|[1-9][0-9]{0,9})([.][0-9]{1,6})?$"}}}]},"needs_shipping":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"boolean"}}}]},"chosen_shipping_methods":{"oneOf":[{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["unknown"]}}},{"type":"object","additionalProperties":false,"required":["state","value"],"properties":{"state":{"type":"string","enum":["known"]},"value":{"type":"array","minItems":1,"maxItems":20,"uniqueItems":false,"items":{"type":"string","minLength":1,"maxLength":128,"pattern":"^[a-zA-Z0-9_:-]+$"}}}},{"type":"object","additionalProperties":false,"required":["state"],"properties":{"state":{"type":"string","enum":["known_empty"]}}}]}}}}}},"output_schema":{"type":"object","additionalProperties":false,"required":["schema_version","context","observations_atomic","results"],"properties":{"schema_version":{"type":"integer","enum":[1]},"context":{"type":"string","enum":["explicit_merchant_scenario"]},"observations_atomic":{"type":"boolean","enum":[false]},"results":{"type":"array","minItems":2,"maxItems":2,"items":{"type":"object","additionalProperties":false,"required":["schema_version","context","scope","final_availability","observations_atomic","gateway_intrinsic_availability","custom_gateway_availability","third_party_availability","blocks_browser_availability","rows"],"properties":{"schema_version":{"type":"integer","enum":[1]},"context":{"type":"string","enum":["explicit_merchant_scenario"]},"scope":{"type":"string","enum":["six_booster_payment_filters_only"]},"final_availability":{"type":"null"},"observations_atomic":{"type":"boolean","enum":[false]},"gateway_intrinsic_availability":{"type":"string","enum":["not_checked"]},"custom_gateway_availability":{"type":"string","enum":["not_checked"]},"third_party_availability":{"type":"string","enum":["not_checked"]},"blocks_browser_availability":{"type":"string","enum":["not_checked"]},"rows":{"type":"array","minItems":6,"maxItems":6,"items":{"type":"object","additionalProperties":false,"required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category","payment_gateways_by_user_role","payment_gateways_by_currency","payment_gateways_min_max","payment_gateways_by_country","payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked","not_seen","removed","not_configured","retained","unsupported"]},"reason":{"type":"string","enum":["unsupported_catalogue_scenario","callback_not_registered","module_admission_denied","module_disabled","earlier_owner_removed","earlier_owner_unresolved","include_miss","exclude_match","no_rules","rules_pass","missing_fact","dynamic_role_policy","below_min","above_max","no_limits","limits_pass","live_conversion","empty_location_facts","reached_location_rules_pass","by_ip_location","shipping_not_required","no_shipping_selection","multiple_shipping_selections","invalid_shipping_selection","invalid_shipping_instance","shipping_method_match","shipping_method_mismatch"]},"predicate":{"anyOf":[{"type":"null"},{"type":"string","enum":["catalogue","role","currency","total","country","state","postcode","shipping","needs_shipping","chosen_shipping_methods"]}]},"provenance":{"type":"string","enum":["explicit_merchant_scenario","not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category","payment_gateways_by_user_role","payment_gateways_by_currency","payment_gateways_min_max","payment_gateways_by_country","payment_gateways_by_shipping"]}},"oneOf":[{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["unsupported_catalogue_scenario"]},"predicate":{"type":"string","enum":["catalogue"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_per_category"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_per_category"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["include_miss"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["exclude_match"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_configured"]},"reason":{"type":"string","enum":["no_rules"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["rules_pass"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["unsupported"]},"reason":{"type":"string","enum":["dynamic_role_policy"]},"predicate":{"type":"string","enum":["role"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_user_role"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_user_role"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["include_miss"]},"predicate":{"type":"string","enum":["currency"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["exclude_match"]},"predicate":{"type":"string","enum":["currency"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_configured"]},"reason":{"type":"string","enum":["no_rules"]},"predicate":{"type":"string","enum":["currency"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["rules_pass"]},"predicate":{"type":"string","enum":["currency"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["currency"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_currency"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_currency"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["below_min"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["above_max"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_configured"]},"reason":{"type":"string","enum":["no_limits"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["limits_pass"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["unsupported"]},"reason":{"type":"string","enum":["live_conversion"]},"predicate":{"type":"string","enum":["total"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_min_max"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_min_max"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["empty_location_facts"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["reached_location_rules_pass"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_configured"]},"reason":{"type":"string","enum":["no_rules"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["unsupported"]},"reason":{"type":"string","enum":["by_ip_location"]},"predicate":{"type":"string","enum":["country"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["include_miss"]},"predicate":{"type":"string","enum":["country"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["exclude_match"]},"predicate":{"type":"string","enum":["country"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["country"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["include_miss"]},"predicate":{"type":"string","enum":["state"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["exclude_match"]},"predicate":{"type":"string","enum":["state"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["state"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["include_miss"]},"predicate":{"type":"string","enum":["postcode"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["exclude_match"]},"predicate":{"type":"string","enum":["postcode"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["postcode"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_country"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_country"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_configured"]},"reason":{"type":"string","enum":["no_rules"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["shipping_not_required"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["no_shipping_selection"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["multiple_shipping_selections"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["invalid_shipping_selection"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["invalid_shipping_instance"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["retained"]},"reason":{"type":"string","enum":["shipping_method_match"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["removed"]},"reason":{"type":"string","enum":["shipping_method_mismatch"]},"predicate":{"type":"string","enum":["shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["needs_shipping"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["missing_fact"]},"predicate":{"type":"string","enum":["chosen_shipping_methods"]},"provenance":{"type":"string","enum":["explicit_merchant_scenario"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["callback_not_registered"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_admission_denied"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_checked"]},"reason":{"type":"string","enum":["module_disabled"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_removed"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}},{"type":"object","required":["owner","decision","reason","predicate","provenance","generation","settings_section"],"properties":{"owner":{"type":"string","enum":["payment_gateways_by_shipping"]},"decision":{"type":"string","enum":["not_seen"]},"reason":{"type":"string","enum":["earlier_owner_unresolved"]},"predicate":{"type":"null"},"provenance":{"type":"string","enum":["not_observed"]},"generation":{"type":"null"},"settings_section":{"type":"string","enum":["payment_gateways_by_shipping"]}}}]}}}}}}}}}
WCJ_PAYMENT_CONTRACT
					,
					true
				);
			}
			return $data;
		}

		/**
		 * Returns the single or comparison payment input schema.
		 *
		 * @param mixed $compare Whether the comparison schema is requested.
		 *
		 * @return mixed
		 */
		public function get_payment_input_schema( $compare = false ) {
			$schemas = self::payment_contract_schemas();
			return $schemas[ $compare ? 'booster/compare-payment-options' : 'booster/explain-payment-options' ]['input_schema'];
		}
		/**
		 * Returns the single or comparison payment output schema.
		 *
		 * @param mixed $compare Whether the comparison schema is requested.
		 *
		 * @return mixed
		 */
		public function get_payment_output_schema( $compare = false ) {
			$schemas = self::payment_contract_schemas();
			return $schemas[ $compare ? 'booster/compare-payment-options' : 'booster/explain-payment-options' ]['output_schema'];
		}

		/**
		 * Request-local shipping observer instance.
		 *
		 * @var WCJ_Rule_Explanations|null
		 */
		private static $shipping_observer = null;
		/**
		 * Request-local shipping generation state.
		 *
		 * @var array
		 */
		private static $shipping_generation = array( 'state' => 'unarmed' );
		/**
		 * Request-local shipping transport recognition state.
		 *
		 * @var array
		 */
		private static $shipping_transport = array(
			'initialized' => false,
			'recognized'  => false,
			'failed'      => false,
		);

		/**
		 * Returns the v1 shipping owner class map.
		 *
		 * @return mixed
		 */
		public static function shipping_owners() {
			return array(
				'shipping_by_order_amount' => 'WCJ_Shipping_By_Order_Amount',
				'shipping_by_order_qty'    => 'WCJ_Shipping_By_Order_Qty',
			);
		}
		/**
		 * Builds a fixed shipping diagnostic error.
		 *
		 * @param mixed $code Fixed diagnostic error code.
		 *
		 * @return mixed
		 */
		private function shipping_error( $code = 'booster_shipping_invalid_input' ) {
			return new WP_Error( $code, __( 'Shipping diagnostic unavailable for this context.', 'woocommerce-jetpack' ) );
		}
		/**
		 * Checks exact member names without requiring member order.
		 *
		 * @param mixed $value Value supplied to this method.
		 * @param mixed $keys Required member names.
		 *
		 * @return mixed
		 */
		private function shipping_exact( $value, $keys ) {
			return is_array( $value ) && count( $value ) === count( $keys ) && ! array_diff( $keys, array_keys( $value ) ) && ! array_diff( array_keys( $value ), $keys );
		}
		/**
		 * Checks for a zero-based consecutive list.
		 *
		 * @param mixed $value Value supplied to this method.
		 *
		 * @return mixed
		 */
		private function shipping_dense( $value ) {
			return is_array( $value ) && ( array() === $value || range( 0, count( $value ) - 1 ) === array_keys( $value ) );
		}
		/**
		 * PHP integer-key coercion is tested lexically, never by an overflowing cast.
		 *
		 * @param mixed $key Explicit rule or rate key.
		 */
		public function shipping_typed_key_valid( $key ) {
			if ( ! $this->shipping_exact( $key, array( 'kind', 'value' ) ) ) {
				return false;
			}
			if ( 'int' === $key['kind'] ) {
				return is_int( $key['value'] ) && $key['value'] >= 0 && $key['value'] <= 2147483647;
			}
			$v = $key['value'];
			if ( 'string' !== $key['kind'] || ! is_string( $v ) || strlen( $v ) < 1 || strlen( $v ) > 96 || 1 !== preg_match( '/\A[A-Za-z0-9_-]+(?::[A-Za-z0-9_-]+)*\z/', $v ) ) {
				return false;
			}
			if ( 1 === preg_match( '/\A(?:0|-?[1-9][0-9]*)\z/', $v ) ) {
				$negative = '-' === $v[0];
				$digits   = $negative ? substr( $v, 1 ) : $v;
				$limit    = $negative ? substr( (string) PHP_INT_MIN, 1 ) : (string) PHP_INT_MAX;
				if ( strlen( $digits ) < strlen( $limit ) || ( strlen( $digits ) === strlen( $limit ) && strcmp( $digits, $limit ) <= 0 ) ) {
					return false;
				}
			}
			return true;
		}
		/**
		 * Checks the method or instance rule-key grammar.
		 *
		 * @param mixed $key Explicit rule or rate key.
		 *
		 * @return mixed
		 */
		private function shipping_rule_key_valid( $key ) {
			return $this->shipping_exact( $key, array( 'kind', 'value' ) ) && ( ( 'method' === $key['kind'] && is_string( $key['value'] ) && 1 === preg_match( '/\A[A-Za-z0-9_-]{1,64}\z/', $key['value'] ) ) || ( 'instance' === $key['kind'] && is_int( $key['value'] ) && $key['value'] >= 0 && $key['value'] <= 2147483647 ) );
		}
		/**
		 * Checks a bounded numeric shipping rule value.
		 *
		 * @param mixed $v Value to validate.
		 *
		 * @return mixed
		 */
		private function shipping_bound_valid( $v ) {
			if ( is_int( $v ) || is_float( $v ) ) {
				return is_finite( (float) $v ) && abs( $v ) <= 1000000000;
			}
			return is_string( $v ) && strlen( $v ) <= 64 && 1 === preg_match( '/\A[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)\z/', $v ) && is_finite( (float) $v ) && abs( (float) $v ) <= 1000000000;
		}
		/**
		 * Validates the closed v1 cart facts.
		 *
		 * @param mixed $f Explicit facts.
		 *
		 * @return mixed
		 */
		private function shipping_facts_valid( $f ) {
			if ( ! $this->shipping_exact( $f, array( 'cart_present', 'cart_empty', 'contents_total', 'contents_count' ) ) || ! is_bool( $f['cart_present'] ) || ! is_bool( $f['cart_empty'] ) || ( ! is_int( $f['contents_total'] ) && ! is_float( $f['contents_total'] ) ) || ! is_finite( (float) $f['contents_total'] ) || $f['contents_total'] < 0 || $f['contents_total'] > 1000000000 || ! is_int( $f['contents_count'] ) || $f['contents_count'] < 0 || $f['contents_count'] > 1000000 ) {
				return false;
			}
			return ( $f['cart_present'] || $f['cart_empty'] ) && ( ! $f['cart_empty'] || ( ( 0 === $f['contents_total'] || 0.0 === $f['contents_total'] ) && 0 === $f['contents_count'] ) );
		}
		/**
		 * Validates an explicit v1 shipping scenario.
		 *
		 * @param mixed $in Explicit diagnostic input.
		 *
		 * @return mixed
		 */
		private function shipping_scenario_valid( $in ) {
			if ( ! $this->shipping_exact( $in, array( 'mode', 'owners', 'facts' ) ) || 'scenario' !== $in['mode'] || ! $this->shipping_exact( $in['owners'], array_keys( self::shipping_owners() ) ) || ! $this->shipping_facts_valid( $in['facts'] ) ) {
				return false;
			}
			foreach ( $in['owners'] as $v ) {
				if ( ! $this->shipping_exact( $v, array( 'rule_key' ) ) || ! $this->shipping_rule_key_valid( $v['rule_key'] ) ) {
					return false;
				}
			}
			return true;
		}
		/**
		 * Returns the currently loaded Booster module map.
		 *
		 * @return mixed
		 */
		private function shipping_modules() {
			$plugin = function_exists( 'w_c_j' ) ? w_c_j() : ( function_exists( 'WCJ' ) ? WCJ() : null );
			return isset( $plugin->all_modules ) && is_array( $plugin->all_modules ) ? $plugin->all_modules : array();
		}
		/**
		 * Checks an expected shipping owner class and method source.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $module Callback owner instance.
		 *
		 * @return mixed
		 */
		private function shipping_source_valid( $owner, $module ) {
			$owners = self::shipping_owners();
			if ( ! isset( $owners[ $owner ] ) || ! is_object( $module ) || get_class( $module ) !== $owners[ $owner ] ) {
				return false;
			}
			$c    = new ReflectionClass( $module );
			$m    = new ReflectionMethod( $module, 'available_shipping_methods' );
			$file = realpath( __DIR__ . '/class-wcj-' . str_replace( '_', '-', $owner ) . '.php' );
			return false !== $file && $c->getFileName() === $file && $m->getFileName() === $file && $m->getDeclaringClass()->getName() === $owners[ $owner ] && 2 === $m->getNumberOfParameters();
		}
		/**
		 * Checks the expected v1 shipping callback registration.
		 *
		 * @param mixed $module Callback owner instance.
		 *
		 * @return mixed
		 */
		private function shipping_registered( $module ) {
			global $wp_filter;
			if ( ! is_object( $module ) || PHP_INT_MAX !== has_filter( 'woocommerce_package_rates', array( $module, 'available_shipping_methods' ) ) || ! isset( $wp_filter['woocommerce_package_rates']->callbacks[ PHP_INT_MAX ] ) ) {
				return false;
			}
			foreach ( $wp_filter['woocommerce_package_rates']->callbacks[ PHP_INT_MAX ] as $cb ) {
				if ( isset( $cb['function'], $cb['accepted_args'] ) && array( $module, 'available_shipping_methods' ) === $cb['function'] ) {
					return 2 === $cb['accepted_args'];
				}
			}
			return false;
		}
		/**
		 * Builds one matching v1 shipping contract row.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $context Contract context identifier.
		 * @param mixed $reason Contract reason identifier.
		 * @param mixed $observed Optional captured callback facts.
		 *
		 * @return mixed
		 */
		private function shipping_row( $owner, $context, $reason, $observed = null ) {
			foreach ( self::shipping_contract_tuples() as $t ) {
				if ( $owner !== $t['owner'] || $context !== $t['context'] || $reason !== $t['reason'] ) {
					continue;
				}
				if ( null === $observed && 'normal_shipping_callback' === $t['provenance'] ) {
					continue;
				}
				if ( null !== $observed ) {
					if ( 'normal_shipping_callback' !== $t['provenance'] || $t['entry_before'] !== $observed['entry_before'] || $t['entry_after'] !== $observed['entry_after'] ) {
						continue;
					}
					$t['call_ordinal'] = $observed['call_ordinal'];
				}
				return $t;
			}
			return null;
		}
		/**
		 * Validates the context, order and joint values of v1 rows.
		 *
		 * @param mixed $rows Contract rows to validate or package.
		 * @param mixed $context Contract context identifier.
		 *
		 * @return mixed
		 */
		public function validate_shipping_rows( $rows, $context ) {
			if ( ! in_array( $context, array( 'explicit_merchant_scenario', 'own_actor_observation' ), true ) || ! $this->shipping_dense( $rows ) || 2 !== count( $rows ) ) {
				return false;
			}
			$ordinals = array();
			$owners   = array_keys( self::shipping_owners() );
			foreach ( $rows as $i => $row ) {
				$matched = false;
				foreach ( self::shipping_contract_tuples() as $t ) {
					if ( ! $this->shipping_exact( $row, array_keys( $t ) ) || $row['owner'] !== $owners[ $i ] || $row['context'] !== $context ) {
						continue;
					}
					$equal = true;
					foreach ( $t as $k => $v ) {
						if ( 'call_ordinal' === $k && is_array( $v ) ) {
							if ( ! is_int( $row[ $k ] ) || ! in_array( $row[ $k ], $v, true ) ) {
								$equal = false;
							}
						} elseif ( $row[ $k ] !== $v ) {
							$equal = false;
						}
					}
					if ( $equal ) {
						$matched = true;
						break;
					}
				}
				if ( ! $matched ) {
					return false;
				}
				if ( null !== $row['call_ordinal'] ) {
					if ( in_array( $row['call_ordinal'], $ordinals, true ) ) {
						return false;
					}
					$ordinals[] = $row['call_ordinal'];
				}
			}
			return true;
		}
		/**
		 * Builds a v1 shipping response from validated rows.
		 *
		 * @param mixed $rows Contract rows to validate or package.
		 * @param mixed $context Contract context identifier.
		 *
		 * @return mixed
		 */
		private function shipping_response( $rows, $context ) {
			if ( ! $this->validate_shipping_rows( $rows, $context ) ) {
				return $this->shipping_error( 'booster_shipping_diagnostic_unavailable' );
			}
			return array(
				'schema_version'      => 1,
				'scope'               => 'amount_quantity_first_slice',
				'context'             => $context,
				'rows'                => $rows,
				'observations_atomic' => false,
				'final_availability'  => null,
				'label'               => 'explicit_merchant_scenario' === $context ? 'Independent supplied-fact predicates. Not an atomic shipping calculation or final availability.' : 'Own-actor callback observations. Rows are independent; retained does not mean a shipping rate is finally available.',
			);
		}
		/**
		 * Validates a complete shipping response for its selected version.
		 *
		 * @param mixed $result Callback or diagnostic result.
		 * @param mixed $compare Whether the comparison schema is requested.
		 *
		 * @return mixed
		 */
		public function validate_shipping_output( $result, $compare = false ) {
			if ( is_array( $result ) && isset( $result['schema_version'] ) && 2 === $result['schema_version'] ) {
				return $this->shipping_v2_output_valid( $result, $compare );
			}
			if ( $compare ) {
				if ( ! $this->shipping_exact( $result, array( 'schema_version', 'context', 'observations_atomic', 'final_availability', 'label', 'results' ) ) || 1 !== $result['schema_version'] || 'explicit_merchant_scenario' !== $result['context'] || false !== $result['observations_atomic'] || null !== $result['final_availability'] || 'Two independently evaluated scenarios. Comparison is not atomic and does not establish final shipping availability.' !== $result['label'] || ! $this->shipping_dense( $result['results'] ) || 2 !== count( $result['results'] ) ) {
					return false;
				}
				foreach ( $result['results'] as $single ) {
					if ( ! $this->validate_shipping_output( $single ) || 'explicit_merchant_scenario' !== $single['context'] ) {
						return false;
					}
				}
				return true;
			}
			if ( ! $this->shipping_exact( $result, array( 'schema_version', 'scope', 'context', 'rows', 'observations_atomic', 'final_availability', 'label' ) ) || ! $this->validate_shipping_rows( $result['rows'], $result['context'] ) ) {
				return false;
			}
			$expected = $this->shipping_response( $result['rows'], $result['context'] );
			foreach ( $expected as $key => $value ) {
				if ( $result[ $key ] !== $value ) {
					return false;
				}
			}
			return true;
		}
		/**
		 * Evaluates the selected amount or quantity predicate.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $value Value supplied to this method.
		 * @param mixed $min Minimum bound.
		 * @param mixed $max Maximum bound.
		 *
		 * @return mixed
		 */
		private function shipping_predicate( $owner, $value, $min, $max ) {
			$offmin = 'shipping_by_order_amount' === $owner ? 0.0 === (float) $min : ( 0 === $min || '0' === $min );
			$offmax = 'shipping_by_order_amount' === $owner ? 0.0 === (float) $max : ( 0 === $max || '0' === $max );
			if ( ! $offmin && $value < $min ) {
				return 'below_min';
			}
			if ( ! $offmax && $value > $max ) {
				return 'above_max';
			}
			return $offmin && $offmax ? 'no_limit' : 'within_limits';
		}
		/**
		 * Independent scenario owners, not an availability callback simulation.
		 *
		 * @param mixed $input Explicit diagnostic input.
		 */
		public function explain_shipping_options( $input ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return $this->shipping_error( 'booster_shipping_capability_required' );
			}
			if ( ! $this->is_full_experience() ) {
				return $this->shipping_error( 'booster_shipping_elite_required' );
			}
			if ( is_array( $input ) && array_key_exists( 'contract_version', $input ) ) {
				return 2 === $input['contract_version'] ? $this->shipping_v2_explain( $input ) : $this->shipping_error();
			}
			if ( ! $this->shipping_scenario_valid( $input ) ) {
				return $this->shipping_error();
			}
			if ( wcj_is_module_enabled( 'modules_by_user_roles' ) ) {
				return $this->shipping_error( 'booster_shipping_context_not_supported' );
			}
			$modules = $this->shipping_modules();
			$rows    = array();
			foreach ( self::shipping_owners() as $owner => $class ) {
				$module     = isset( $modules[ $owner ] ) ? $modules[ $owner ] : null;
				$allowed    = ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( $owner );
				$enabled    = $allowed && 'yes' === wcj_get_option( 'wcj_' . $owner . '_enabled', 'no' );
				$registered = $this->shipping_registered( $module );
				$key        = $input['owners'][ $owner ]['rule_key'];
				$facts      = $input['facts'];
				if ( ( ! $allowed || ! $enabled ) && ! $registered ) {
					$reason = 'module_not_admitted';
				} elseif ( ! $allowed || ! $enabled || ! $registered || ! $this->shipping_source_valid( $owner, $module ) ) {
					$reason = 'module_admission_denied';
				} elseif ( ! isset( $module->use_shipping_instances ) || ! is_bool( $module->use_shipping_instances ) || ( 'instance' === $key['kind'] ) !== $module->use_shipping_instances ) {
					$reason = 'unsupported_rate_identity';
				} elseif ( ! $facts['cart_present'] ) {
					$reason = 'no_cart';
				} elseif ( $facts['cart_empty'] ) {
					$reason = 'empty_cart';
				} else {
					if ( 'shipping_by_order_amount' === $owner ) {
						$min   = $module->use_shipping_instances ? get_option( 'wcj_shipping_by_order_amount_min_instance_' . $key['value'], 0 ) : wcj_get_option( 'wcj_shipping_by_order_amount_min_' . $key['value'], 0 );
						$max   = $module->use_shipping_instances ? get_option( 'wcj_shipping_by_order_amount_max_instance_' . $key['value'], 0 ) : wcj_get_option( 'wcj_shipping_by_order_amount_max_' . $key['value'], 0 );
						$value = $facts['contents_total'];
					} else {
						if ( ! is_array( $module->min_qty ) || ! is_array( $module->max_qty ) ) {
							$rows[] = $this->shipping_row( $owner, 'explicit_merchant_scenario', 'unsupported_rule_values' );
							continue;
						}
						$min   = isset( $module->min_qty[ $key['value'] ] ) ? $module->min_qty[ $key['value'] ] : 0;
						$max   = isset( $module->max_qty[ $key['value'] ] ) ? $module->max_qty[ $key['value'] ] : 0;
						$value = $facts['contents_count'];
					}
					$reason = $this->shipping_bound_valid( $min ) && $this->shipping_bound_valid( $max ) ? $this->shipping_predicate( $owner, $value, $min, $max ) : 'unsupported_rule_values';
				}
				$rows[] = $this->shipping_row( $owner, 'explicit_merchant_scenario', $reason );
			}
			return $this->shipping_response( $rows, 'explicit_merchant_scenario' );
		}
		/**
		 * Compares two independently admitted shipping scenarios.
		 *
		 * @param mixed $input Explicit diagnostic input.
		 *
		 * @return mixed
		 */
		public function compare_shipping_options( $input ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return $this->shipping_error( 'booster_shipping_capability_required' );
			}
			if ( ! $this->is_full_experience() ) {
				return $this->shipping_error( 'booster_shipping_elite_required' );
			}
			if ( is_array( $input ) && array_key_exists( 'contract_version', $input ) ) {
				return 2 === $input['contract_version'] ? $this->shipping_v2_compare( $input ) : $this->shipping_error();
			}
			if ( ! $this->shipping_exact( $input, array( 'mode', 'scenarios' ) ) || 'compare' !== $input['mode'] || ! $this->shipping_dense( $input['scenarios'] ) || 2 !== count( $input['scenarios'] ) ) {
				return $this->shipping_error();
			}
			foreach ( $input['scenarios'] as $single ) {
				if ( ! $this->shipping_scenario_valid( $single ) ) {
					return $this->shipping_error();
				}
			}
			$results = array();
			foreach ( $input['scenarios'] as $single ) {
				$result = $this->explain_shipping_options( $single );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				$results[] = $result;
			}
			$result = array(
				'schema_version'      => 1,
				'context'             => 'explicit_merchant_scenario',
				'observations_atomic' => false,
				'final_availability'  => null,
				'label'               => 'Two independently evaluated scenarios. Comparison is not atomic and does not establish final shipping availability.',
				'results'             => $results,
			);
			return $this->validate_shipping_output( $result, true ) ? $result : $this->shipping_error( 'booster_shipping_diagnostic_unavailable' );
		}
		/**
		 * Binds the request-local shipping observer to its hooks.
		 *
		 * @return mixed
		 */
		public static function bind_shipping_observer() {
			if ( null !== self::$shipping_observer ) {
				return;
			}
			self::$shipping_observer = new self();
			add_action( 'template_redirect', array( self::$shipping_observer, 'arm_shipping_observer' ), 0 );
			add_filter( 'woocommerce_cart_shipping_packages', array( self::$shipping_observer, 'open_shipping_generation' ), PHP_INT_MAX, 1 );
			add_filter( 'woocommerce_package_rates', array( self::$shipping_observer, 'begin_shipping_pass' ), PHP_INT_MIN, 2 );
			add_filter( 'woocommerce_package_rates', array( self::$shipping_observer, 'end_shipping_pass' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_shipping_packages', array( self::$shipping_observer, 'close_shipping_generation' ), PHP_INT_MAX, 1 );
			add_action( 'woocommerce_checkout_after_order_review', array( self::$shipping_observer, 'render_shipping_observer' ), 21 );
			add_action( 'woocommerce_before_get_rates_for_package', array( self::$shipping_observer, 'shipping_v2_before_method' ), PHP_INT_MIN, 2 );
			add_action( 'woocommerce_after_get_rates_for_package', array( self::$shipping_observer, 'shipping_v2_after_method' ), PHP_INT_MAX, 2 );
			add_filter( 'woocommerce_shipping_free_shipping_is_available', array( self::$shipping_observer, 'shipping_v2_availability' ), PHP_INT_MIN, 3 );
		}
		/**
		 * Builds the nonce action for an actor, blog, package and rate.
		 *
		 * @param mixed $package Selected shipping package.
		 * @param mixed $rate Selected shipping rate.
		 * @param mixed $blog Captured blog identifier.
		 * @param mixed $actor Captured actor identifier.
		 *
		 * @return mixed
		 */
		private function shipping_nonce_action( $package, $rate, $blog, $actor ) {
			if ( ! $this->shipping_typed_key_valid( $package ) || ! $this->shipping_typed_key_valid( $rate ) || ! is_int( $blog ) || $blog < 1 || ! is_int( $actor ) || $actor < 1 ) {
				return false;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Canonical authenticated payload/nonce bytes must reject invalid UTF-8 rather than use the repairing fallback in wp_json_encode.
			$json = json_encode( array( $package['kind'], $package['value'], $rate['kind'], $rate['value'] ) );
			return false === $json ? false : 'wcj_shipping_observe:' . $blog . ':' . $actor . ':' . hash( 'sha256', $json );
		}
		/**
		 * Builds an admitted private v1 shipping observation link.
		 *
		 * @param mixed $package Selected shipping package.
		 * @param mixed $rate Selected shipping rate.
		 *
		 * @return mixed
		 */
		public function shipping_observation_link( $package, $rate ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
				return $this->shipping_error( 'booster_shipping_capability_required' );
			}
			$action = $this->shipping_nonce_action( $package, $rate, get_current_blog_id(), get_current_user_id() );
			if ( false === $action ) {
				return $this->shipping_error();
			}
			return add_query_arg(
				array(
					'wcj_shipping_observe'      => '1',
					'wcj_shipping_package_kind' => $package['kind'],
					'wcj_shipping_package_key'  => (string) $package['value'],
					'wcj_shipping_rate_kind'    => $rate['kind'],
					'wcj_shipping_rate_key'     => (string) $rate['value'],
					'wcj_shipping_nonce'        => wp_create_nonce( $action ),
				),
				wc_get_checkout_url()
			);
		}
		/**
		 * Parses a typed selector without repairing its value.
		 *
		 * @param mixed $kind Expected selector or tagged-fact kind.
		 * @param mixed $value Value supplied to this method.
		 *
		 * @return mixed
		 */
		public function shipping_transport_selector( $kind, $value ) {
			if ( ! is_string( $kind ) || ! is_string( $value ) ) {
				return false;
			}
			if ( 'int' === $kind ) {
				if ( 1 !== preg_match( '/\A(?:0|[1-9][0-9]{0,9})\z/', $value ) || strlen( $value ) > 10 || ( 10 === strlen( $value ) && strcmp( $value, '2147483647' ) > 0 ) ) {
					return false;
				}
				$value = (int) $value;
			}
			$key = array(
				'kind'  => $kind,
				'value' => $value,
			);
			return $this->shipping_typed_key_valid( $key ) ? $key : false;
		}
		/**
		 * Rechecks the captured shipping actor, blog, selectors and nonce.
		 *
		 * @return mixed
		 */
		private function shipping_identity_valid() {
			$g = self::$shipping_generation;
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! isset( $g['actor'], $g['blog'], $g['package'], $g['rate'], $g['nonce'] ) || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) || get_current_user_id() !== $g['actor'] || get_current_blog_id() !== $g['blog'] ) {
				return false;
			}
			$action = isset( $g['version'] ) ? $this->shipping_v2_nonce_action( $g['package'], $g['rate'], $g['free_instance'], $g['blog'], $g['actor'] ) : $this->shipping_nonce_action( $g['package'], $g['rate'], $g['blog'], $g['actor'] );
			return false !== $action && wp_verify_nonce( $g['nonce'], $action );
		}
		/**
		 * Checks the pinned core sources needed by the shipping observer.
		 *
		 * @return mixed
		 */
		private function shipping_core_sources_valid() {
			if ( ! defined( 'WC_ABSPATH' ) || ! defined( 'WPINC' ) ) {
				return false;
			}
			$specs = self::shipping_core_sources();
			foreach ( $specs as $class => $s ) {
				$file = realpath( ( 'WP_Hook' === $class ? ABSPATH . WPINC . '/' : WC_ABSPATH . 'includes/' ) . $s[0] );
				if ( false === $file || ! class_exists( $class ) ) {
					return false;
				}
				$class_file = ( new ReflectionClass( $class ) )->getFileName();
				if ( $file !== $class_file || hash_file( 'sha256', $file ) !== $s[1] ) {
					return false;
				}
			}
			return self::shipping_wp_plugin_hash() === hash_file( 'sha256', ABSPATH . WPINC . '/plugin.php' );
		}
		/**
		 * Admits the selected shipping observation generation.
		 *
		 * @return mixed
		 */
		public function arm_shipping_observer() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
			if ( array_key_exists( 'wcj_shipping_contract_version', $_GET ) || array_key_exists( 'wcj_shipping_scope', $_GET ) || array_key_exists( 'wcj_shipping_free_instance', $_GET ) ) {
				$this->arm_shipping_v2();
				return;
			}
			if ( ! self::$shipping_transport['recognized'] ) {
				return;
			}
			if ( 'unarmed' !== self::$shipping_generation['state'] || self::$shipping_observer !== $this || ! self::write_shipping_transport_headers() || ! self::shipping_transport_headers_valid() ) {
				self::deny_shipping_transport();
			}
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_checkout() || is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
				self::deny_shipping_transport();
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
			if ( ! isset( $_GET['wcj_shipping_observe'], $_GET['wcj_shipping_package_kind'], $_GET['wcj_shipping_package_key'], $_GET['wcj_shipping_rate_kind'], $_GET['wcj_shipping_rate_key'], $_GET['wcj_shipping_nonce'] ) || '1' !== $_GET['wcj_shipping_observe'] || ! is_string( $_GET['wcj_shipping_nonce'] ) || 1 !== preg_match( '/\A[A-Za-z0-9]{1,64}\z/', $_GET['wcj_shipping_nonce'] ) ) {
				self::deny_shipping_transport();
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
			$package = $this->shipping_transport_selector( $_GET['wcj_shipping_package_kind'], $_GET['wcj_shipping_package_key'] );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
			$rate = $this->shipping_transport_selector( $_GET['wcj_shipping_rate_kind'], $_GET['wcj_shipping_rate_key'] );
			if ( false === $package || false === $rate ) {
				self::deny_shipping_transport();
			}
			self::$shipping_generation = array(
				'state'    => 'unarmed',
				'actor'    => get_current_user_id(),
				'blog'     => get_current_blog_id(),
				'package'  => $package,
				'rate'     => $rate,
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
				'nonce'    => $_GET['wcj_shipping_nonce'],
				'passes'   => 0,
				'ordinal'  => 0,
				'counts'   => array(),
				'rows'     => array(),
				'captures' => array(),
				'renders'  => 0,
			);
			if ( ! $this->shipping_identity_valid() || wcj_is_module_enabled( 'modules_by_user_roles' ) || ! $this->shipping_core_sources_valid() ) {
				self::deny_shipping_transport();
			}
			$modules                              = $this->shipping_modules();
			self::$shipping_generation['modules'] = array();
			foreach ( self::shipping_owners() as $owner => $class ) {
				$m = isset( $modules[ $owner ] ) ? $modules[ $owner ] : null;
				if ( null !== $m && ! $this->shipping_source_valid( $owner, $m ) ) {
					self::deny_shipping_transport();
				}
				self::$shipping_generation['modules'][ $owner ] = $m;
			}
			if ( ! self::write_shipping_transport_headers() || ! self::shipping_transport_headers_valid() ) {
				self::deny_shipping_transport();
			}
			self::$shipping_generation['state'] = 'armed';
		}
		/**
		 * Checks the expected bounded shipping callback stack.
		 *
		 * @param mixed $trace Captured argument-free callback stack.
		 * @param mixed $callback Expected callback name.
		 * @param mixed $sequence Expected bounded call sequence.
		 *
		 * @return mixed
		 */
		private function shipping_trace( $trace, $callback, $sequence ) {
			if ( ! is_array( $trace ) || count( $trace ) > 8 || ! isset( $trace[0]['function'], $trace[0]['object'] ) || $trace[0]['function'] !== $callback || $trace[0]['object'] !== $this ) {
				return false;
			}
			$out      = array();
			$wpfile   = realpath( ABSPATH . WPINC . '/plugin.php' );
			$hookfile = realpath( ABSPATH . WPINC . '/class-wp-hook.php' );
			foreach ( array_slice( $trace, 1 ) as $frame ) {
				$f = isset( $frame['function'] ) ? $frame['function'] : '';
				$c = isset( $frame['class'] ) ? $frame['class'] : '';
				if ( 'call_user_func_array' === $f && '' === $c && ! isset( $frame['object'] ) ) {
					continue;
				}
				if ( 'WP_Hook' === $c && 'apply_filters' === $f && isset( $frame['object'] ) && 'WP_Hook' === get_class( $frame['object'] ) && ( new ReflectionMethod( 'WP_Hook', 'apply_filters' ) )->getFileName() === $hookfile ) {
					continue;
				}
				if ( '' === $c && 'apply_filters' === $f && ( new ReflectionFunction( 'apply_filters' ) )->getFileName() === $wpfile ) {
					continue;
				}
				$out[] = $frame;
				if ( count( $out ) === count( $sequence ) ) {
					break;
				}
			}
			if ( count( $out ) !== count( $sequence ) ) {
				return false;
			}
			foreach ( $sequence as $i => $expected ) {
				$f = $out[ $i ];
				if ( ! isset( $f['class'], $f['function'], $f['object'] ) || $expected[0] !== $f['class'] || $expected[1] !== $f['function'] || get_class( $f['object'] ) !== $expected[0] ) {
					return false;
				}
				$method = new ReflectionMethod( $f['object'], $expected[1] );
				if ( $method->getDeclaringClass()->getName() !== $expected[0] || $method->getFileName() !== realpath( WC_ABSPATH . 'includes/' . ( 'WC_Cart' === $expected[0] ? 'class-wc-cart.php' : 'class-wc-shipping.php' ) ) ) {
					return false;
				}
				if ( isset( $out[ $i + 1 ] ) && ( ! isset( $f['file'] ) || realpath( $f['file'] ) !== ( new ReflectionClass( $out[ $i + 1 ]['object'] ) )->getFileName() ) ) {
					return false;
				}
			}
			return $out;
		}
		/**
		 * Resolves a strict package identifier or its supplied fallback.
		 *
		 * @param mixed $package Selected shipping package.
		 * @param mixed $fallback Package identifier fallback.
		 *
		 * @return mixed
		 */
		private function shipping_package_id( $package, $fallback ) {
			$v   = isset( $package['package_id'] ) ? $package['package_id'] : $fallback;
			$key = array(
				'kind'  => is_int( $v ) ? 'int' : 'string',
				'value' => $v,
			);
			return $this->shipping_typed_key_valid( $key ) ? $key : false;
		}
		/**
		 * Checks package identities for the selected generation phase.
		 *
		 * @param mixed $packages Value supplied to this method.
		 * @param mixed $opening Whether the generation is opening.
		 *
		 * @return mixed
		 */
		private function shipping_packages_valid( $packages, $opening = false ) {
			$g   = self::$shipping_generation;
			$key = $g['package']['value'];
			if ( ! is_array( $packages ) || 1 !== count( $packages ) || ! array_key_exists( $key, $packages ) || ! is_array( $packages[ $key ] ) || array() === $packages[ $key ] ) {
				return false;
			}
			$id = $this->shipping_package_id( $packages[ $key ], $key );
			return false !== $id && ( $opening || $id === $g['package_id'] ) ? $id : false;
		}
		/**
		 * Opens an admitted request-local shipping generation.
		 *
		 * @param mixed $packages Value supplied to this method.
		 *
		 * @return mixed
		 */
		public function open_shipping_generation( $packages ) {
			if ( 'unarmed' === self::$shipping_generation['state'] ) {
				return $packages;
			}
			$trace  = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 8 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Bounded argument-free callback provenance is an admission check, never a debug log or response.
			$frames = $this->shipping_trace( $trace, __FUNCTION__, array( array( 'WC_Cart', 'get_shipping_packages' ), array( 'WC_Cart', 'calculate_shipping' ) ) );
			if ( 'armed' !== self::$shipping_generation['state'] || false === $frames || $frames[0]['object'] !== $frames[1]['object'] ) {
				self::$shipping_generation['state'] = 'invalid';
				return $packages;
			}
			$id = $this->shipping_packages_valid( $packages, true );
			if ( false === $id ) {
				self::$shipping_generation['state'] = 'invalid';
				return $packages;
			}
			self::$shipping_generation['cart']       = $frames[0]['object'];
			self::$shipping_generation['package_id'] = $id;
			self::$shipping_generation['state']      = 'open';
			return $packages;
		}
		/**
		 * Begins the selected package-rates observation pass.
		 *
		 * @param mixed $rates Rate map supplied by the callback.
		 * @param mixed $package Selected shipping package.
		 *
		 * @return mixed
		 */
		public function begin_shipping_pass( $rates, $package ) {
			if ( isset( self::$shipping_generation['version'] ) && isset( self::$shipping_generation['free_window'] ) && 'closed' !== self::$shipping_generation['free_window'] ) {
				self::$shipping_generation['state'] = 'invalid';
				return $rates;
			}
			if ( 'unarmed' === self::$shipping_generation['state'] ) {
				return $rates;
			}
			$trace                               = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 8 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Bounded argument-free callback provenance is an admission check, never a debug log or response.
			$frames                              = $this->shipping_trace( $trace, __FUNCTION__, array( array( 'WC_Shipping', 'calculate_shipping_for_package' ), array( 'WC_Shipping', 'calculate_shipping' ), array( 'WC_Cart', 'calculate_shipping' ) ) );
			self::$shipping_generation['passes'] = min( 2, self::$shipping_generation['passes'] + 1 );
			if ( 'open' !== self::$shipping_generation['state'] || 1 !== self::$shipping_generation['passes'] || false === $frames || $frames[0]['object'] !== $frames[1]['object'] || $frames[2]['object'] !== self::$shipping_generation['cart'] || ! is_array( $rates ) || ! is_array( $package ) || ! array_key_exists( 'package_id', $package ) || $this->shipping_package_id( $package, null ) !== self::$shipping_generation['package_id'] ) {
				self::$shipping_generation['state'] = 'invalid';
				return $rates;
			}
			self::$shipping_generation['shipping'] = $frames[0]['object'];
			self::$shipping_generation['state']    = 'pass';
			return $rates;
		}
		/**
		 * Ends the selected package-rates observation pass.
		 *
		 * @param mixed $rates Rate map supplied by the callback.
		 * @param mixed $package Selected shipping package.
		 *
		 * @return mixed
		 */
		public function end_shipping_pass( $rates, $package ) {
			if ( 'unarmed' === self::$shipping_generation['state'] ) {
				return $rates;
			}
			$trace  = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 8 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Bounded argument-free callback provenance is an admission check, never a debug log or response.
			$frames = $this->shipping_trace( $trace, __FUNCTION__, array( array( 'WC_Shipping', 'calculate_shipping_for_package' ), array( 'WC_Shipping', 'calculate_shipping' ), array( 'WC_Cart', 'calculate_shipping' ) ) );
			if ( 'pass' !== self::$shipping_generation['state'] || ! is_array( $rates ) || false === $frames || $frames[0]['object'] !== $frames[1]['object'] || $frames[0]['object'] !== self::$shipping_generation['shipping'] || $frames[2]['object'] !== self::$shipping_generation['cart'] || ! is_array( $package ) || ! array_key_exists( 'package_id', $package ) || $this->shipping_package_id( $package, null ) !== self::$shipping_generation['package_id'] || ! empty( self::$shipping_generation['captures'] ) ) {
				self::$shipping_generation['state'] = 'invalid';
				return $rates;
			}
			self::$shipping_generation['state'] = 'pass_closed';
			return $rates;
		}
		/**
		 * Closes the request-local shipping generation.
		 *
		 * @param mixed $packages Value supplied to this method.
		 *
		 * @return mixed
		 */
		public function close_shipping_generation( $packages ) {
			if ( 'unarmed' === self::$shipping_generation['state'] ) {
				return $packages;
			}
			$trace  = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 8 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Bounded argument-free callback provenance is an admission check, never a debug log or response.
			$frames = $this->shipping_trace( $trace, __FUNCTION__, array( array( 'WC_Shipping', 'calculate_shipping' ), array( 'WC_Cart', 'calculate_shipping' ) ) );
			$g      = self::$shipping_generation;
			if ( ! in_array( $g['state'], array( 'open', 'pass_closed' ), true ) || false === $frames || $frames[1]['object'] !== $g['cart'] || ( 1 === $g['passes'] && $frames[0]['object'] !== $g['shipping'] ) || $g['passes'] > 1 || false === $this->shipping_packages_valid( $packages ) ) {
				self::$shipping_generation['state'] = 'invalid';
				return $packages;
			}
			self::$shipping_generation['state'] = 'closed';
			return $packages;
		}
		/**
		 * Starts a v1 owner capture only within an open generation.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $rates Rate map supplied by the callback.
		 *
		 * @return mixed
		 */
		public static function shipping_start( $module, $rates ) {
			if ( 'unarmed' === self::$shipping_generation['state'] ) {
				return null;
			}
			$g     =& self::$shipping_generation;
			$owner = null;
			foreach ( self::shipping_owners() as $id => $class ) {
				if ( isset( $g['modules'][ $id ] ) && $g['modules'][ $id ] === $module ) {
					$owner = $id;
					break;
				}
			}
			if ( 'pass' !== $g['state'] || null === $owner || ! is_array( $rates ) ) {
				$g['state'] = 'invalid';
				return null;
			}
			if ( isset( $g['version'] ) && ( ! self::$shipping_observer->shipping_v2_registered( $owner, $module ) || ! self::$shipping_observer->shipping_v2_position_valid( $owner, $module ) ) ) {
				$g['state'] = 'invalid';
				return null;
			}
			if ( ! isset( $g['counts'][ $owner ] ) ) {
				$g['counts'][ $owner ] = array( 0, 0 );
			}
			$g['counts'][ $owner ][0] = min( 2, $g['counts'][ $owner ][0] + 1 );
			$g['ordinal']             = min( isset( $g['version'] ) ? 10 : 3, $g['ordinal'] + 1 );
			if ( 1 !== $g['counts'][ $owner ][0] || $g['ordinal'] > ( isset( $g['version'] ) ? 9 : 2 ) ) {
				$g['state'] = 'invalid';
				return null;
			}
			$key                     = $g['rate']['value'];
			$present                 = array_key_exists( $key, $rates );
			$g['captures'][ $owner ] = array(
				'owner'        => $owner,
				'call_ordinal' => $g['ordinal'],
				'entry_before' => $present,
				'object'       => $present ? $rates[ $key ] : null,
				'keys'         => array(),
				'modes'        => array(),
				'bounds'       => array(),
			);
			return $owner;
		}
		/**
		 * Records the rule key used by the selected v1 callback.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $rate Selected shipping rate.
		 * @param mixed $phase Include or exclude phase.
		 * @param mixed $mode Rule-key mode.
		 * @param mixed $key Explicit rule or rate key.
		 *
		 * @return mixed
		 */
		public static function shipping_capture_rule_key( $module, $rate, $phase, $mode, $key ) {
			if ( 'pass' !== self::$shipping_generation['state'] ) {
				return $key;
			}
			foreach ( self::$shipping_generation['captures'] as $owner => &$capture ) {
				if ( self::$shipping_generation['modules'][ $owner ] === $module && $capture['object'] === $rate ) {
					if ( array_key_exists( $phase, $capture['keys'] ) ) {
						self::$shipping_generation['state'] = 'invalid';
						unset( $capture );
						return $key;
					}
					$capture['keys'][ $phase ]  = $key;
					$capture['modes'][ $phase ] = $mode;
					break;
				}
			}
			unset( $capture );
			return $key;
		}
		/**
		 * Records the bounds and branch used by the selected v1 callback.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $rate Selected shipping rate.
		 * @param mixed $min Minimum bound.
		 * @param mixed $max Maximum bound.
		 * @param mixed $value Value supplied to this method.
		 * @param mixed $branch Observed callback branch.
		 *
		 * @return mixed
		 */
		public static function shipping_capture_bounds( $module, $rate, $min, $max, $value, $branch ) {
			if ( 'pass' !== self::$shipping_generation['state'] ) {
				return;
			}
			foreach ( self::$shipping_generation['captures'] as $owner => &$capture ) {
				if ( self::$shipping_generation['modules'][ $owner ] === $module && $capture['object'] === $rate ) {
					if ( ! empty( $capture['bounds'] ) ) {
						self::$shipping_generation['state'] = 'invalid';
						unset( $capture );
						return;
					}
					$capture['bounds'] = array( $min, $max );
					$capture['value']  = $value;
					$capture['branch'] = $branch;
					break;
				}
			}
			unset( $capture );
		}
		/**
		 * Completes a v1 shipping owner capture.
		 *
		 * @param mixed $ticket Request-local observation ticket.
		 * @param mixed $rates Rate map supplied by the callback.
		 * @param mixed $whole Complete callback result, when supplied.
		 *
		 * @return mixed
		 */
		public static function shipping_finish( $ticket, $rates, $whole = null ) {
			if ( null === $ticket || 'pass' !== self::$shipping_generation['state'] ) {
				return;
			}
			$g   =& self::$shipping_generation;
			$c   = $g['captures'][ $ticket ];
			$key = $g['rate']['value'];
			if ( ! is_array( $rates ) ) {
				$g['state'] = 'invalid';
				return;
			}
			$c['entry_after'] = array_key_exists( $key, $rates );
			if ( $c['entry_after'] && $rates[ $key ] !== $c['object'] ) {
				$g['state'] = 'invalid';
				return;
			}
			$s = self::$shipping_observer;
			if ( null !== $whole ) {
				$reason = $whole;
			} elseif ( ! $c['entry_before'] ) {
				$reason = 'selected_rate_not_seen';
			} elseif (
				! is_object( $c['object'] ) || 'WC_Shipping_Rate' !== get_class( $c['object'] ) || ! isset( $c['keys']['min'], $c['keys']['max'], $c['modes']['min'], $c['modes']['max'] ) || ! is_bool( $c['modes']['min'] ) || $c['modes']['min'] !== $c['modes']['max'] || $c['keys']['min'] !== $c['keys']['max'] || ! $s->shipping_rule_key_valid(
					array(
						'kind'  => $c['modes']['min'] ? 'instance' : 'method',
						'value' => $c['keys']['min'],
					)
				)
			) {
				$reason = 'unsupported_rate_identity';
			} elseif ( 2 !== count( $c['bounds'] ) || ! $s->shipping_bound_valid( $c['bounds'][0] ) || ! $s->shipping_bound_valid( $c['bounds'][1] ) ) {
				$reason = 'unsupported_rule_values';
			} elseif ( ! isset( $c['value'] ) || ( 'shipping_by_order_amount' === $ticket ? ! $s->shipping_bound_valid( $c['value'] ) : ! is_int( $c['value'] ) ) || ! is_finite( (float) $c['value'] ) || $c['value'] < 0 || $c['value'] > ( 'shipping_by_order_amount' === $ticket ? 1000000000 : 1000000 ) ) {
				$reason = 'unsupported_observed_facts';
			} else {
				$reason = $c['branch'];
				if ( null === $reason ) {
					$offmin = 'shipping_by_order_amount' === $ticket ? 0.0 === (float) $c['bounds'][0] : ( 0 === $c['bounds'][0] || '0' === $c['bounds'][0] );
					$offmax = 'shipping_by_order_amount' === $ticket ? 0.0 === (float) $c['bounds'][1] : ( 0 === $c['bounds'][1] || '0' === $c['bounds'][1] );
					$reason = $offmin && $offmax ? 'no_limit' : 'within_limits';
				}
			}
			$g['counts'][ $ticket ][1] = min( 2, $g['counts'][ $ticket ][1] + 1 );
			if ( isset( $g['version'] ) && ( ! $s->shipping_v2_registered( $ticket, $g['modules'][ $ticket ] ) || ! $s->shipping_v2_position_valid( $ticket, $g['modules'][ $ticket ] ) ) ) {
				$g['state'] = 'invalid';
				return;
			}
			$row = isset( $g['version'] ) ? $s->shipping_v2_row( $ticket, 'own_actor_observation', $reason, null, $c ) : $s->shipping_row( $ticket, 'own_actor_observation', $reason, $c );
			unset( $g['captures'][ $ticket ] );
			if ( null === $row || array( 1, 1 ) !== $g['counts'][ $ticket ] ) {
				$g['state'] = 'invalid';
				return;
			}
			$g['rows'][ $ticket ] = $row;
		}
		/**
		 * Returns a closed, admitted v1 shipping observation.
		 *
		 * @return mixed
		 */
		public function get_shipping_observation() {
			if ( isset( self::$shipping_generation['version'] ) ) {
				return $this->get_shipping_v2_observation();
			}
			$g = self::$shipping_generation;
			if ( ! in_array( $g['state'], array( 'armed', 'closed' ), true ) || ! $this->shipping_identity_valid() || ! self::shipping_transport_headers_valid( false ) ) {
				return $this->shipping_error( 'booster_shipping_observation_unavailable' );
			}
			$rows = array();
			foreach ( self::shipping_owners() as $owner => $class ) {
				if ( isset( $g['rows'][ $owner ] ) ) {
					if ( array( 1, 1 ) !== $g['counts'][ $owner ] ) {
						return $this->shipping_error( 'booster_shipping_observation_unavailable' );
					}
					$rows[] = $g['rows'][ $owner ];
				} else {
					if ( isset( $g['counts'][ $owner ] ) ) {
						return $this->shipping_error( 'booster_shipping_observation_unavailable' );
					}
					$rows[] = $this->shipping_row( $owner, 'own_actor_observation', $this->shipping_registered( $g['modules'][ $owner ] ) ? 'not_observed' : 'callback_not_registered' );
				}
			}
			return $this->shipping_response( $rows, 'own_actor_observation' );
		}
		/**
		 * Renders the admitted shipping observation or fixed error.
		 *
		 * @return mixed
		 */
		public function render_shipping_observer() {
			if ( 'unarmed' === self::$shipping_generation['state'] ) {
				return;
			}
			if ( ! isset( self::$shipping_generation['renders'] ) || 0 !== self::$shipping_generation['renders'] ) {
				self::$shipping_generation['state'] = 'invalid';
				return;
			}
			self::$shipping_generation['renders'] = 1;
			$result                               = $this->get_shipping_observation();
			if ( is_wp_error( $result ) ) {
				self::$shipping_generation['state'] = 'invalid';
				return;
			}
			echo '<section class="wcj-shipping-observation">';
			$this->render_shipping_rows( $result );
			echo '</section>';
			self::$shipping_generation['state'] = 'rendered';
		}
		/**
		 * Renders validated shipping rows and fixed limitations.
		 *
		 * @param mixed $result Callback or diagnostic result.
		 *
		 * @return mixed
		 */
		public function render_shipping_rows( $result ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! current_user_can( 'manage_woocommerce' ) || ! $this->validate_shipping_output( $result ) ) {
				return;
			}
			echo '<p>' . esc_html( $result['label'] ) . '</p><table><tbody>';
			foreach ( $result['rows'] as $row ) {
				echo '<tr><td>' . esc_html( $row['owner'] ) . '</td><td>' . esc_html( $row['decision'] ) . '</td><td>' . esc_html( $row['reason'] ) . '</td><td>' . esc_html( null === $row['call_ordinal'] ? '' : (string) $row['call_ordinal'] ) . '</td><td>';
				if ( null !== $row['settings_section'] ) {
					echo '<a href="' . esc_url(
						add_query_arg(
							array(
								'page'    => 'wc-settings',
								'tab'     => 'jetpack',
								'section' => $row['settings_section'],
							),
							admin_url( 'admin.php' )
						)
					) . '">' . esc_html__( 'Settings', 'woocommerce-jetpack' ) . '</a>';
				}
				echo '</td></tr>';
			}
			echo '</tbody></table>';
			if ( 2 === $result['schema_version'] ) {
				foreach ( $result['rows'] as $row ) {
					foreach ( $row['sections'] as $section ) {
						echo '<p>' . esc_html( $row['owner'] . ' / ' . $section['section'] . ': ' . $section['include_state'] . ' / ' . $section['exclude_state'] . ' (' . (string) $section['include_path'] . ' / ' . (string) $section['exclude_path'] . ')' ) . '</p>';
					}
				}
			}
		}
		/**
		 * Recognizes the request-local shipping observation transport.
		 *
		 * @return mixed
		 */
		public static function initialize_shipping_transport() {
			if ( self::$shipping_transport['initialized'] ) {
				if ( self::$shipping_transport['recognized'] ) {
					self::$shipping_transport['failed'] = true;
				}
				return;
			}
			self::$shipping_transport['initialized'] = true;
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Presence only; no values or admission here.
			self::$shipping_transport['recognized'] = array_key_exists( 'wcj_shipping_contract_version', $_GET ) || array_key_exists( 'wcj_shipping_scope', $_GET ) || array_key_exists( 'wcj_shipping_free_instance', $_GET ) || array_key_exists( 'wcj_shipping_observe', $_GET ) || array_key_exists( 'wcj_shipping_package_kind', $_GET ) || array_key_exists( 'wcj_shipping_package_key', $_GET ) || array_key_exists( 'wcj_shipping_rate_kind', $_GET ) || array_key_exists( 'wcj_shipping_rate_key', $_GET ) || array_key_exists( 'wcj_shipping_nonce', $_GET );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( ! self::$shipping_transport['recognized'] ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
			if ( array_key_exists( 'wcj_payment_observe', $_GET ) || array_key_exists( 'wcj_payment_gateway', $_GET ) || array_key_exists( 'wcj_payment_nonce', $_GET ) ) {
				self::$shipping_transport['failed'] = true;
			}
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			if ( ! DONOTCACHEPAGE ) {
				self::$shipping_transport['failed'] = true;
			}
			self::write_shipping_transport_headers();
			add_filter( 'wp_headers', array( __CLASS__, 'shipping_transport_headers' ), PHP_INT_MAX, 2 );
			add_action( 'template_redirect', array( __CLASS__, 'check_shipping_transport' ), -1, 0 );
		}

		/**
		 * Fixed protection only; unrelated header values and the WP argument are untouched.
		 *
		 * @param mixed $headers Response header map.
		 * @param mixed $wp WordPress request argument, left untouched.
		 */
		public static function shipping_transport_headers( $headers, $wp ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Retain the existing public/hook signature, including argument names and arity, for compatibility.
			if ( ! self::$shipping_transport['recognized'] ) {
				return $headers;
			}
			foreach ( array_keys( $headers ) as $name ) {
				if ( is_string( $name ) && in_array( strtolower( $name ), array( 'referrer-policy', 'cache-control', 'expires', 'etag', 'last-modified' ), true ) ) {
					unset( $headers[ $name ] );
				}
			}
			$headers['Referrer-Policy'] = 'no-referrer';
			$headers['Cache-Control']   = 'private, no-store, no-cache, must-revalidate';
			$headers['Expires']         = 'Wed, 11 Jan 1984 05:00:00 GMT';
			$headers['Last-Modified']   = false;
			return $headers;
		}

		/** Bootstrap failure is recorded, never terminated, including non-template entry points. */
		private static function write_shipping_transport_headers() {
			if ( headers_sent() ) {
				self::$shipping_transport['failed'] = true;
				return false;
			}
			header( 'Referrer-Policy: no-referrer' );
			header( 'Cache-Control: private, no-store, no-cache, must-revalidate' );
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
			header_remove( 'ETag' );
			header_remove( 'Last-Modified' );
			return true;
		}

		/**
		 * Effective header confirmation, not a guarantee against a later writer or proxy.
		 *
		 * @param mixed $require_writable Whether headers must still be writable.
		 */
		private static function shipping_transport_headers_valid( $require_writable = true ) {
			if ( ! self::$shipping_transport['recognized'] || self::$shipping_transport['failed'] || ( $require_writable && headers_sent() ) || ! defined( 'DONOTCACHEPAGE' ) || true !== DONOTCACHEPAGE ) {
				return false;
			}
			$headers = array();
			$counts  = array();
			foreach ( headers_list() as $header ) {
				$pair = explode( ':', $header, 2 );
				if ( 2 === count( $pair ) ) {
					$key             = strtolower( trim( $pair[0] ) );
					$headers[ $key ] = trim( $pair[1] );
					$counts[ $key ]  = isset( $counts[ $key ] ) ? $counts[ $key ] + 1 : 1;
				}
			}
			return isset( $headers['cache-control'], $headers['referrer-policy'], $headers['expires'] ) && 1 === $counts['cache-control'] && 1 === $counts['referrer-policy'] && 1 === $counts['expires'] && 'private, no-store, no-cache, must-revalidate' === $headers['cache-control'] && 'no-referrer' === $headers['referrer-policy'] && 'Wed, 11 Jan 1984 05:00:00 GMT' === $headers['expires'] && ! isset( $headers['etag'] ) && ! isset( $headers['last-modified'] );
		}

		/** Only the actual template_redirect path calls this; no bootstrap or wp_headers exit. */
		public static function check_shipping_transport() {
			if ( ! self::$shipping_transport['recognized'] ) {
				return;
			}
			if ( null === self::$shipping_observer || self::$shipping_transport['failed'] || 'unarmed' !== self::$shipping_generation['state'] ) {
				self::deny_shipping_transport();
			}
			if ( ! self::write_shipping_transport_headers() || ! self::shipping_transport_headers_valid() ) {
				self::deny_shipping_transport();
			}
		}

		/** Fixed resource-free denial; shutdown/buffer behavior still requires native qualification. */
		private static function deny_shipping_transport() {
			self::$shipping_generation['state'] = 'invalid';
			if ( headers_sent() ) {
				exit;
			}
			self::write_shipping_transport_headers();
			http_response_code( 403 );
			header( 'Content-Type: text/plain; charset=UTF-8' );
			header( 'X-Content-Type-Options: nosniff' );
			if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'HEAD' !== $_SERVER['REQUEST_METHOD'] ) {
				echo "Diagnostic observation unavailable.\n";
			}
			exit;
		}

		/**
		 * Returns the fixed v1 shipping row tuples.
		 *
		 * @return mixed
		 */
		private static function shipping_contract_tuples() {
			$json = <<<'WCJ_SHIPPING_CONTRACT_TUPLES'
[{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"module_not_admitted","predicate":null,"context":"explicit_merchant_scenario","provenance":"not_evaluated","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"module_admission_denied","predicate":null,"context":"explicit_merchant_scenario","provenance":"not_evaluated","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"unsupported_rate_identity","predicate":null,"context":"explicit_merchant_scenario","provenance":"not_evaluated","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"unsupported_rule_values","predicate":null,"context":"explicit_merchant_scenario","provenance":"not_evaluated","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"retained","reason":"no_cart","predicate":null,"context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"retained","reason":"empty_cart","predicate":null,"context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"retained","reason":"no_limit","predicate":"amount","context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":"shipping_by_order_amount"},{"owner":"shipping_by_order_amount","decision":"retained","reason":"within_limits","predicate":"amount","context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":"shipping_by_order_amount"},{"owner":"shipping_by_order_amount","decision":"removed","reason":"below_min","predicate":"amount","context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":"shipping_by_order_amount"},{"owner":"shipping_by_order_amount","decision":"removed","reason":"above_max","predicate":"amount","context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":"shipping_by_order_amount"},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"callback_not_registered","predicate":null,"context":"own_actor_observation","provenance":"not_observed","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"not_observed","predicate":null,"context":"own_actor_observation","provenance":"not_observed","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"selected_rate_not_seen","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":false,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"retained","reason":"no_cart","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"no_cart","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":false,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"retained","reason":"empty_cart","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"empty_cart","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":false,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"unsupported_rate_identity","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"unsupported_rate_identity","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"unsupported_rule_values","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"unsupported_rule_values","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"unsupported_observed_facts","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"not_checked","reason":"unsupported_observed_facts","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_amount","decision":"retained","reason":"no_limit","predicate":"amount","context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":"shipping_by_order_amount"},{"owner":"shipping_by_order_amount","decision":"retained","reason":"within_limits","predicate":"amount","context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":"shipping_by_order_amount"},{"owner":"shipping_by_order_amount","decision":"removed","reason":"below_min","predicate":"amount","context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":false,"started":1,"completed":1,"settings_section":"shipping_by_order_amount"},{"owner":"shipping_by_order_amount","decision":"removed","reason":"above_max","predicate":"amount","context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":false,"started":1,"completed":1,"settings_section":"shipping_by_order_amount"},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"module_not_admitted","predicate":null,"context":"explicit_merchant_scenario","provenance":"not_evaluated","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"module_admission_denied","predicate":null,"context":"explicit_merchant_scenario","provenance":"not_evaluated","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"unsupported_rate_identity","predicate":null,"context":"explicit_merchant_scenario","provenance":"not_evaluated","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"unsupported_rule_values","predicate":null,"context":"explicit_merchant_scenario","provenance":"not_evaluated","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"retained","reason":"no_cart","predicate":null,"context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"retained","reason":"empty_cart","predicate":null,"context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"retained","reason":"no_limit","predicate":"quantity","context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":"shipping_by_order_qty"},{"owner":"shipping_by_order_qty","decision":"retained","reason":"within_limits","predicate":"quantity","context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":"shipping_by_order_qty"},{"owner":"shipping_by_order_qty","decision":"removed","reason":"below_min","predicate":"quantity","context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":"shipping_by_order_qty"},{"owner":"shipping_by_order_qty","decision":"removed","reason":"above_max","predicate":"quantity","context":"explicit_merchant_scenario","provenance":"explicit_merchant_scenario","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":"shipping_by_order_qty"},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"callback_not_registered","predicate":null,"context":"own_actor_observation","provenance":"not_observed","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"not_observed","predicate":null,"context":"own_actor_observation","provenance":"not_observed","generation":null,"call_ordinal":null,"entry_before":null,"entry_after":null,"started":0,"completed":0,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"selected_rate_not_seen","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":false,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"retained","reason":"no_cart","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"no_cart","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":false,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"retained","reason":"empty_cart","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"empty_cart","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":false,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"unsupported_rate_identity","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"unsupported_rate_identity","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"unsupported_rule_values","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"unsupported_rule_values","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"unsupported_observed_facts","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"not_checked","reason":"unsupported_observed_facts","predicate":null,"context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":false,"started":1,"completed":1,"settings_section":null},{"owner":"shipping_by_order_qty","decision":"retained","reason":"no_limit","predicate":"quantity","context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":"shipping_by_order_qty"},{"owner":"shipping_by_order_qty","decision":"retained","reason":"within_limits","predicate":"quantity","context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":true,"started":1,"completed":1,"settings_section":"shipping_by_order_qty"},{"owner":"shipping_by_order_qty","decision":"removed","reason":"below_min","predicate":"quantity","context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":false,"started":1,"completed":1,"settings_section":"shipping_by_order_qty"},{"owner":"shipping_by_order_qty","decision":"removed","reason":"above_max","predicate":"quantity","context":"own_actor_observation","provenance":"normal_shipping_callback","generation":1,"call_ordinal":[1,2],"entry_before":true,"entry_after":false,"started":1,"completed":1,"settings_section":"shipping_by_order_qty"}]
WCJ_SHIPPING_CONTRACT_TUPLES;
			return json_decode( $json, true );
		}
		/**
		 * Returns the fixed v1 shipping schemas.
		 *
		 * @return mixed
		 */
		private static function shipping_contract_schemas() {
			$json = <<<'WCJ_SHIPPING_CONTRACT_SCHEMAS'
{"status":"NORMATIVE05a_TYPED_END_ANCHORED_METADATA_NOT_NATIVE_TRANSPORT_PROOF","input_schemas":{"booster/explain-shipping-options":{"type":"object","properties":{"mode":{"enum":["scenario"],"type":"string"},"owners":{"type":"object","properties":{"shipping_by_order_amount":{"type":"object","properties":{"rule_key":{"oneOf":[{"type":"object","properties":{"kind":{"enum":["method"],"type":"string"},"value":{"type":"string","minLength":1,"maxLength":64,"pattern":"^[A-Za-z0-9_-]+(?![\\s\\S])"}},"required":["kind","value"],"additionalProperties":false},{"type":"object","properties":{"kind":{"enum":["instance"],"type":"string"},"value":{"type":"integer","minimum":0,"maximum":2147483647}},"required":["kind","value"],"additionalProperties":false}],"type":"object"}},"required":["rule_key"],"additionalProperties":false},"shipping_by_order_qty":{"type":"object","properties":{"rule_key":{"oneOf":[{"type":"object","properties":{"kind":{"enum":["method"],"type":"string"},"value":{"type":"string","minLength":1,"maxLength":64,"pattern":"^[A-Za-z0-9_-]+(?![\\s\\S])"}},"required":["kind","value"],"additionalProperties":false},{"type":"object","properties":{"kind":{"enum":["instance"],"type":"string"},"value":{"type":"integer","minimum":0,"maximum":2147483647}},"required":["kind","value"],"additionalProperties":false}],"type":"object"}},"required":["rule_key"],"additionalProperties":false}},"required":["shipping_by_order_amount","shipping_by_order_qty"],"additionalProperties":false},"facts":{"type":"object","properties":{"cart_present":{"type":"boolean"},"cart_empty":{"type":"boolean"},"contents_total":{"type":"number","minimum":0,"maximum":1000000000},"contents_count":{"type":"integer","minimum":0,"maximum":1000000}},"required":["cart_present","cart_empty","contents_total","contents_count"],"additionalProperties":false}},"required":["mode","owners","facts"],"additionalProperties":false},"booster/compare-shipping-options":{"type":"object","properties":{"mode":{"enum":["compare"],"type":"string"},"scenarios":{"type":"array","items":{"type":"object","properties":{"mode":{"enum":["scenario"],"type":"string"},"owners":{"type":"object","properties":{"shipping_by_order_amount":{"type":"object","properties":{"rule_key":{"oneOf":[{"type":"object","properties":{"kind":{"enum":["method"],"type":"string"},"value":{"type":"string","minLength":1,"maxLength":64,"pattern":"^[A-Za-z0-9_-]+(?![\\s\\S])"}},"required":["kind","value"],"additionalProperties":false},{"type":"object","properties":{"kind":{"enum":["instance"],"type":"string"},"value":{"type":"integer","minimum":0,"maximum":2147483647}},"required":["kind","value"],"additionalProperties":false}],"type":"object"}},"required":["rule_key"],"additionalProperties":false},"shipping_by_order_qty":{"type":"object","properties":{"rule_key":{"oneOf":[{"type":"object","properties":{"kind":{"enum":["method"],"type":"string"},"value":{"type":"string","minLength":1,"maxLength":64,"pattern":"^[A-Za-z0-9_-]+(?![\\s\\S])"}},"required":["kind","value"],"additionalProperties":false},{"type":"object","properties":{"kind":{"enum":["instance"],"type":"string"},"value":{"type":"integer","minimum":0,"maximum":2147483647}},"required":["kind","value"],"additionalProperties":false}],"type":"object"}},"required":["rule_key"],"additionalProperties":false}},"required":["shipping_by_order_amount","shipping_by_order_qty"],"additionalProperties":false},"facts":{"type":"object","properties":{"cart_present":{"type":"boolean"},"cart_empty":{"type":"boolean"},"contents_total":{"type":"number","minimum":0,"maximum":1000000000},"contents_count":{"type":"integer","minimum":0,"maximum":1000000}},"required":["cart_present","cart_empty","contents_total","contents_count"],"additionalProperties":false}},"required":["mode","owners","facts"],"additionalProperties":false},"minItems":2,"maxItems":2}},"required":["mode","scenarios"],"additionalProperties":false}},"output_schemas":{"booster/explain-shipping-options":{"type":"object","properties":{"schema_version":{"enum":[1],"type":"integer"},"scope":{"enum":["amount_quantity_first_slice"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"rows":{"type":"array","items":{"oneOf":[{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["module_not_admitted"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["module_admission_denied"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rate_identity"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rule_values"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["empty_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_limit"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["within_limits"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["below_min"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["above_max"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["module_not_admitted"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["module_admission_denied"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rate_identity"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rule_values"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["empty_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_limit"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["within_limits"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["below_min"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["above_max"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false}],"type":"object"},"minItems":2,"maxItems":2},"observations_atomic":{"enum":[false],"type":"boolean"},"final_availability":{"enum":[null],"type":"null"},"label":{"enum":["Independent supplied-fact predicates. Not an atomic shipping calculation or final availability."],"type":"string"}},"required":["schema_version","scope","context","rows","observations_atomic","final_availability","label"],"additionalProperties":false},"booster/compare-shipping-options":{"type":"object","properties":{"schema_version":{"enum":[1],"type":"integer"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"observations_atomic":{"enum":[false],"type":"boolean"},"final_availability":{"enum":[null],"type":"null"},"label":{"enum":["Two independently evaluated scenarios. Comparison is not atomic and does not establish final shipping availability."],"type":"string"},"results":{"type":"array","items":{"type":"object","properties":{"schema_version":{"enum":[1],"type":"integer"},"scope":{"enum":["amount_quantity_first_slice"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"rows":{"type":"array","items":{"oneOf":[{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["module_not_admitted"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["module_admission_denied"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rate_identity"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rule_values"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["empty_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_limit"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["within_limits"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["below_min"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["above_max"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["module_not_admitted"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["module_admission_denied"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rate_identity"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rule_values"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["not_evaluated"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["empty_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_limit"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["within_limits"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["below_min"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["above_max"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["explicit_merchant_scenario"],"type":"string"},"provenance":{"enum":["explicit_merchant_scenario"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false}],"type":"object"},"minItems":2,"maxItems":2},"observations_atomic":{"enum":[false],"type":"boolean"},"final_availability":{"enum":[null],"type":"null"},"label":{"enum":["Independent supplied-fact predicates. Not an atomic shipping calculation or final availability."],"type":"string"}},"required":["schema_version","scope","context","rows","observations_atomic","final_availability","label"],"additionalProperties":false},"minItems":2,"maxItems":2}},"required":["schema_version","context","observations_atomic","final_availability","label","results"],"additionalProperties":false},"private-own-actor-observer":{"type":"object","properties":{"schema_version":{"enum":[1],"type":"integer"},"scope":{"enum":["amount_quantity_first_slice"],"type":"string"},"context":{"enum":["own_actor_observation"],"type":"string"},"rows":{"type":"array","items":{"oneOf":[{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["callback_not_registered"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["not_observed"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["not_observed"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["not_observed"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["selected_rate_not_seen"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[false],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["no_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[false],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["empty_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["empty_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[false],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rate_identity"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rate_identity"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rule_values"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rule_values"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_observed_facts"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_observed_facts"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_limit"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["within_limits"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["below_min"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_amount"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["above_max"],"type":"string"},"predicate":{"enum":["amount"],"type":"string"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":["shipping_by_order_amount"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["callback_not_registered"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["not_observed"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["not_observed"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["not_observed"],"type":"string"},"generation":{"enum":[null],"type":"null"},"call_ordinal":{"enum":[null],"type":"null"},"entry_before":{"enum":[null],"type":"null"},"entry_after":{"enum":[null],"type":"null"},"started":{"enum":[0],"type":"integer"},"completed":{"enum":[0],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["selected_rate_not_seen"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[false],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["no_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[false],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["empty_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["empty_cart"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[false],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rate_identity"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rate_identity"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rule_values"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_rule_values"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_observed_facts"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["not_checked"],"type":"string"},"reason":{"enum":["unsupported_observed_facts"],"type":"string"},"predicate":{"enum":[null],"type":"null"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":[null],"type":"null"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["no_limit"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["retained"],"type":"string"},"reason":{"enum":["within_limits"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[true],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["below_min"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false},{"type":"object","properties":{"owner":{"enum":["shipping_by_order_qty"],"type":"string"},"decision":{"enum":["removed"],"type":"string"},"reason":{"enum":["above_max"],"type":"string"},"predicate":{"enum":["quantity"],"type":"string"},"context":{"enum":["own_actor_observation"],"type":"string"},"provenance":{"enum":["normal_shipping_callback"],"type":"string"},"generation":{"enum":[1],"type":"integer"},"call_ordinal":{"enum":[1,2],"type":"integer"},"entry_before":{"enum":[true],"type":"boolean"},"entry_after":{"enum":[false],"type":"boolean"},"started":{"enum":[1],"type":"integer"},"completed":{"enum":[1],"type":"integer"},"settings_section":{"enum":["shipping_by_order_qty"],"type":"string"}},"required":["owner","decision","reason","predicate","context","provenance","generation","call_ordinal","entry_before","entry_after","started","completed","settings_section"],"additionalProperties":false}],"type":"object"},"minItems":2,"maxItems":2},"observations_atomic":{"enum":[false],"type":"boolean"},"final_availability":{"enum":[null],"type":"null"},"label":{"enum":["Own-actor callback observations. Rows are independent; retained does not mean a shipping rate is finally available."],"type":"string"}},"required":["schema_version","scope","context","rows","observations_atomic","final_availability","label"],"additionalProperties":false}},"native_transport":"Strict readonly GET boolean/query serialization, POST/MCP behavior UNQUALIFIED, no workaround/coercion or source-only PASS.","strict_PHP_order_joint_types_required":true}
WCJ_SHIPPING_CONTRACT_SCHEMAS;
			return json_decode( $json, true );
		}
		/**
		 * Returns the pinned core source identities used for observation.
		 *
		 * @return mixed
		 */
		private static function shipping_core_sources() {
			$json = <<<'WCJ_SHIPPING_CORE_SOURCES'
{"WC_Cart":["class-wc-cart.php","9a892ba6cfc03830df766a0287323ae7b9765ed42a16a409fb42f51781c2fbc1"],"WC_Shipping":["class-wc-shipping.php","c274d473d3292208c4f9355643938e4dc419dd1d5647062bfb229ac0c0a57e24"],"WC_Shipping_Rate":["class-wc-shipping-rate.php","4d39531e7e2ed2ffa47868e03f9d2cbac213c508cbd708f5530ade25146238eb"],"WP_Hook":["class-wp-hook.php","7d5f86b7d25073a821dc971ce85febedff554175f861c7e0e8ee3bb2dfb48b94"]}
WCJ_SHIPPING_CORE_SOURCES;
			return json_decode( $json, true );
		}
		/**
		 * Returns the pinned WordPress hook implementation hash.
		 *
		 * @return mixed
		 */
		private static function shipping_wp_plugin_hash() {
			return '0e50769b8e2a82aa9687e89852c1a51d5682a95636ff38ec80435fe956bb7236';
		}
		/**
		 * Returns the v1 single or comparison shipping input schema.
		 *
		 * @param mixed $compare Whether the comparison schema is requested.
		 *
		 * @return mixed
		 */
		public function get_shipping_input_schema( $compare = false ) {
			$s = self::shipping_contract_schemas();
			return $s['input_schemas'][ $compare ? 'booster/compare-shipping-options' : 'booster/explain-shipping-options' ];
		}
		/**
		 * Returns the v1 single or comparison shipping output schema.
		 *
		 * @param mixed $compare Whether the comparison schema is requested.
		 *
		 * @return mixed
		 */
		public function get_shipping_output_schema( $compare = false ) {
			$s = self::shipping_contract_schemas();
			return $s['output_schemas'][ $compare ? 'booster/compare-shipping-options' : 'booster/explain-shipping-options' ];
		}


		/** Closed shipping v2 metadata. Version1 remains a separate contract. */
		private static function shipping_v2_metadata() {
			static $attempted = false;
			static $data      = false;
			if ( $attempted ) {
				return $data;
			}
			$attempted = true;
			try {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Pinned local metadata is read with an exact byte bound and checked length/hash; failure is handled without leaking filesystem details.
				$json = @file_get_contents( __DIR__ . '/data/wcj-shipping-v2-metadata.json', false, null, 0, 1985527 );
				if ( false === $json || ! is_string( $json ) || 1985526 !== strlen( $json ) || '4308dc2237678cefb1ffb90b56c3970b2eaa508b916d3d8bf088195de30326c9' !== hash( 'sha256', $json ) ) {
					return false;
				}
				$candidate = json_decode( $json, true );
				if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $candidate ) || array( 'schemas', 'profiles', 'phase_pairs' ) !== array_keys( $candidate ) || ! is_array( $candidate['schemas'] ) || ! is_array( $candidate['profiles'] ) || ! is_array( $candidate['phase_pairs'] ) ) {
					return false;
				}
			} catch ( Throwable $error ) {
				return false;
			}
			$data = $candidate;
			return $data;
		}
		/**
		 * Returns the version-dispatch shipping input schema.
		 *
		 * @param mixed $compare Whether the comparison schema is requested.
		 *
		 * @return mixed
		 */
		public function get_shipping_dispatch_input_schema( $compare = false ) {
			$m = self::shipping_v2_metadata();
			if ( false === $m ) {
				return $this->get_shipping_input_schema( $compare );
			}
			return array( 'oneOf' => array( $this->get_shipping_input_schema( $compare ), $m['schemas']['input_schemas'][ $compare ? 'compare_v2' : 'explain_v2' ] ) );
		}
		/**
		 * Returns the version-dispatch shipping output schema.
		 *
		 * @param mixed $compare Whether the comparison schema is requested.
		 *
		 * @return mixed
		 */
		public function get_shipping_dispatch_output_schema( $compare = false ) {
			$m = self::shipping_v2_metadata();
			if ( false === $m ) {
				return $this->get_shipping_output_schema( $compare );
			}
			return array( 'oneOf' => array( $this->get_shipping_output_schema( $compare ), $m['schemas']['output_schemas'][ $compare ? 'comparison_v2' : 'single_v2' ] ) );
		}
		/**
		 * Only the finite keywords in the bound v2 schema are accepted.
		 *
		 * @param mixed $value Value supplied to this method.
		 * @param mixed $schema Closed schema to check.
		 */
		private function shipping_v2_shape( $value, $schema ) {
			if ( isset( $schema['oneOf'] ) ) {
				$matches = 0;
				foreach ( $schema['oneOf'] as $branch ) {
					if ( $this->shipping_v2_shape( $value, $branch ) ) {
						++$matches;
					}
				}
				return 1 === $matches;
			}
			$type = isset( $schema['type'] ) ? $schema['type'] : null;
			if ( 'object' === $type ) {
				if ( ! is_array( $value ) || ! isset( $schema['properties'], $schema['required'] ) || ! $this->shipping_exact( $value, $schema['required'] ) ) {
					return false;
				}
				foreach ( $value as $k => $v ) {
					if ( ! isset( $schema['properties'][ $k ] ) || ! $this->shipping_v2_shape( $v, $schema['properties'][ $k ] ) ) {
						return false;
					}
				}
			} elseif ( 'array' === $type ) {
				if ( ! $this->shipping_dense( $value ) || count( $value ) < $schema['minItems'] || count( $value ) > $schema['maxItems'] ) {
					return false;
				}
				foreach ( $value as $item ) {
					if ( ! $this->shipping_v2_shape( $item, $schema['items'] ) ) {
						return false;
					}
				}
			} elseif ( 'string' === $type ) {
				if ( ! is_string( $value ) || ( isset( $schema['maxLength'] ) && strlen( $value ) > $schema['maxLength'] ) || ( isset( $schema['minLength'] ) && strlen( $value ) < $schema['minLength'] ) || ( isset( $schema['pattern'] ) && 1 !== preg_match( '~' . str_replace( '~', '\\~', $schema['pattern'] ) . '~D', $value ) ) ) {
					return false;
				}
			} elseif ( 'integer' === $type || 'number' === $type ) {
				if ( ( 'integer' === $type ? ! is_int( $value ) : ( ! is_int( $value ) && ! is_float( $value ) ) ) || ! is_finite( (float) $value ) || ( isset( $schema['minimum'] ) && $value < $schema['minimum'] ) || ( isset( $schema['maximum'] ) && $value > $schema['maximum'] ) ) {
					return false;
				}
			} elseif ( 'boolean' === $type ) {
				if ( ! is_bool( $value ) ) {
					return false;
				}
			} elseif ( 'null' === $type ) {
				if ( null !== $value ) {
					return false;
				}
			} else {
						return false;
			}
					return ! isset( $schema['enum'] ) || in_array( $value, $schema['enum'], true );
		}
		/**
		 * Scans one bounded JSON string and advances the byte offset.
		 *
		 * @param mixed $raw Raw JSON input.
		 * @param mixed $i Current byte offset, advanced by reference.
		 * @param mixed $ok Whether the scanned JSON string is valid.
		 *
		 * @return mixed
		 */
		private function shipping_v2_string( $raw, &$i, &$ok ) {
			$start      = $i++;
			$raw_length = strlen( $raw );
			while ( $i < $raw_length ) {
				$c = $raw[ $i++ ];
				if ( '\\' === $c ) {
					++$i;
					continue;
				}
				if ( '"' === $c ) {
					$v  = json_decode( substr( $raw, $start, $i - $start ) );
					$ok = JSON_ERROR_NONE === json_last_error() && is_string( $v );
					return $v;
				}
			}
			$ok = false;
			return null;
		}
		/**
		 * Scan before constructing objects, retaining decoded member names per object only.
		 *
		 * @param mixed $raw Raw JSON input.
		 * @param mixed $i Current byte offset, advanced by reference.
		 * @param mixed $depth Current nesting depth.
		 */
		private function shipping_v2_scan( $raw, &$i, $depth ) {
			$i += strspn( $raw, " \t\r\n", $i );
			if ( $depth > 12 || $i >= strlen( $raw ) ) {
				return false;
			}
			$c = $raw[ $i ];
			if ( '"' === $c ) {
				$ok = false;
				$this->shipping_v2_string( $raw, $i, $ok );
				return $ok;
			}
			if ( '{' !== $c && '[' !== $c ) {
				if ( 1 !== preg_match( '/\G(?:true|false|null|-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?)/', $raw, $m, 0, $i ) ) {
					return false;
				}
				$i += strlen( $m[0] );
				return true;
			}
			++$i;
			$end  = '{' === $c ? '}' : ']';
			$seen = array();
			$i   += strspn( $raw, " \t\r\n", $i );
			if ( isset( $raw[ $i ] ) && $end === $raw[ $i ] ) {
				++$i;
				return true;
			}
			$raw_length = strlen( $raw );
			while ( $i < $raw_length ) {
				if ( '{' === $c ) {
					$i += strspn( $raw, " \t\r\n", $i );
					if ( ! isset( $raw[ $i ] ) || '"' !== $raw[ $i ] ) {
						return false;
					}
					$ok  = false;
					$key = $this->shipping_v2_string( $raw, $i, $ok );
					if ( ! $ok || isset( $seen[ 'k:' . $key ] ) ) {
						return false;
					}
					$seen[ 'k:' . $key ] = true;
					$i                  += strspn( $raw, " \t\r\n", $i );
					if ( ! isset( $raw[ $i ] ) || ':' !== $raw[ $i++ ] ) {
						return false;
					}
				}
				if ( ! $this->shipping_v2_scan( $raw, $i, $depth + 1 ) ) {
					return false;
				}
				$i += strspn( $raw, " \t\r\n", $i );
				if ( ! isset( $raw[ $i ] ) ) {
					return false;
				}
				$next = $raw[ $i++ ];
				if ( $end === $next ) {
					return true;
				}
				if ( ',' !== $next ) {
					return false;
				}
			}
			return false;
		}
		/**
		 * Parses bounded v2 JSON after duplicate-member scanning.
		 *
		 * @param mixed $raw Raw JSON input.
		 *
		 * @return mixed
		 */
		public function parse_shipping_v2_json( $raw ) {
			if ( ! is_string( $raw ) || strlen( $raw ) > 8192 ) {
				return $this->shipping_error();
			}
			$i = 0;
			if ( ! $this->shipping_v2_scan( $raw, $i, 1 ) ) {
				return $this->shipping_error();
			}
			$i += strspn( $raw, " \t\r\n", $i );
			if ( strlen( $raw ) !== $i ) {
				return $this->shipping_error();
			}
			$value = json_decode( $raw, true, 12 );
			return JSON_ERROR_NONE === json_last_error() && is_array( $value ) ? $value : $this->shipping_error();
		}
		/**
		 * Returns the v2 condition-section map.
		 *
		 * @return mixed
		 */
		private static function shipping_v2_sections() {
			return array(
				'shipping_by_products'  => array( 'products', 'product_cats', 'product_tags', 'classes' ),
				'shipping_by_user_role' => array( 'user_roles', 'user_id', 'user_membership' ),
				'shipping_by_cities'    => array( 'cities', 'postcodes' ),
				'shipping_by_time'      => array( 'time' ),
			);
		}
		/**
		 * Returns the nine v2 owner class identities.
		 *
		 * @return mixed
		 */
		private static function shipping_v2_owners() {
			return array_merge(
				self::shipping_owners(),
				array(
					'shipping_by_user_role'            => 'WCJ_Shipping_By_User_Role',
					'shipping_by_products'             => 'WCJ_Shipping_By_Products',
					'shipping_by_cities'               => 'WCJ_Shipping_By_Cities',
					'shipping_by_time'                 => 'WCJ_Shipping_By_Time',
					'shipping_options_most_expensive'  => 'WCJ_Shipping_Options',
					'shipping_options_hide_free'       => 'WCJ_Shipping_Options',
					'shipping_options_free_by_product' => 'WCJ_Shipping_Options',
				)
			);
		}
		/**
		 * Validates the closed v2 input and its joint typed-key constraints.
		 *
		 * @param mixed $in Explicit diagnostic input.
		 * @param mixed $compare Whether the comparison schema is requested.
		 *
		 * @return mixed
		 */
		private function shipping_v2_input_valid( $in, $compare = false ) {
			$m = self::shipping_v2_metadata();
			if ( false === $m ) {
				return false;
			}
			if ( ! $this->shipping_v2_shape( $in, $m['schemas']['input_schemas'][ $compare ? 'compare_v2' : 'explain_v2' ] ) ) {
				return false;
			}
			if ( $compare ) {
				foreach ( $in['scenarios'] as $single ) {
					if ( ! $this->shipping_v2_input_valid( $single ) ) {
						return false;
					}
				}
				return true;
			}
			foreach ( $in['owners'] as $owner ) {
				if ( isset( $owner['rule_key'] ) && ! $this->shipping_rule_key_valid( $owner['rule_key'] ) ) {
					return false;
				}
			}
			$f = $in['facts'];
			if ( 'supplied' === $f['actor']['status'] && ( 1 !== count( $f['actor']['first_roles'] ) || 0 === count( $f['actor']['all_roles'] ) ) ) {
				return false;
			}
			if ( 'supplied' === $f['amount_quantity']['status'] ) {
				$a = $f['amount_quantity'];
				unset( $a['status'] );
				if ( ! $this->shipping_facts_valid( $a ) ) {
					return false;
				}
			}
			if ( 'supplied' === $f['products']['status'] ) {
				$p = $f['products'];
				if ( ( ! $p['cart_present'] && ! $p['cart_empty'] ) || ( ( ! $p['cart_present'] || $p['cart_empty'] ) && array() !== $p['cart_items'] ) || ( ! $p['package_contents_present'] && array() !== $p['package_items'] ) ) {
					return false;
				}
				$seen = array();
				foreach ( $p['variable_expansions'] as $v ) {
					$tag = array( $v['configured_id']['kind'], $v['configured_id']['value'] );
					if ( in_array( $tag, $seen, true ) ) {
						return false;
					}
					$seen[] = $tag;
				}
			}
			if ( 'supplied' === $f['ordered_rates']['status'] ) {
				$o = $f['ordered_rates'];
				if ( ! $this->shipping_typed_key_valid( $o['selected_key'] ) ) {
					return false;
				}
				$seen     = array();
				$selected = 0;
				foreach ( $o['rates'] as $rate ) {
					if ( ! $this->shipping_typed_key_valid( $rate['key'] ) || array_key_exists( $rate['key']['value'], $seen ) ) {
						return false;
					}
					$seen[ $rate['key']['value'] ] = true;
					if ( $rate['key']['kind'] === $o['selected_key']['kind'] && $rate['key']['value'] === $o['selected_key']['value'] ) {
						++$selected;
					}
				}
				if ( 1 !== $selected ) {
					return false;
				}
			}
			return true;
		}
		/**
		 * Builds one matching v2 contract row.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $context Contract context identifier.
		 * @param mixed $reason Contract reason identifier.
		 * @param mixed $sections Condition section states.
		 * @param mixed $observed Optional captured callback facts.
		 *
		 * @return mixed
		 */
		private function shipping_v2_row( $owner, $context, $reason, $sections = null, $observed = null ) {
			$m = self::shipping_v2_metadata();
			if ( false === $m ) {
				return null;
			}
			foreach ( $m['profiles'] as $t ) {
				if ( $owner !== $t['owner'] || $context !== $t['context'] || $reason !== $t['reason'] || ( null === $observed ) !== ( 'normal_shipping_callback' !== $t['provenance'] ) ) {
					continue;
				}
				if ( null !== $observed ) {
					if ( 'shipping_options_free_by_product' !== $owner && ( $t['entry_before'] !== $observed['entry_before'] || $t['entry_after'] !== $observed['entry_after'] ) ) {
						continue;
					}
					$t['call_ordinal'] = $observed['call_ordinal'];
				}
				$profile = $t['sections_profile'];
				unset( $t['sections_profile'] );
				$t['sections'] = array();
				if ( 'actual' === $profile ) {
					$t['sections'] = $sections;
				} elseif ( 'empty' !== $profile ) {
					foreach ( self::shipping_v2_sections()[ $owner ] as $section ) {
						$t['sections'][] = array(
							'section'       => $section,
							'include_state' => $profile,
							'exclude_state' => $profile,
							'include_path'  => null,
							'exclude_path'  => null,
						);
					}
				}
					return $t;
			}
			return null;
		}
		/**
		 * Resolves a row reason from the captured section states.
		 *
		 * @param mixed $sections Condition section states.
		 *
		 * @return mixed
		 */
		private function shipping_v2_section_reason( $sections ) {
			$include = false;
			$exclude = false;
			$active  = false;
			$invalid = false;
			foreach ( $sections as $s ) {
				if ( in_array( $s['include_state'], array( 'not_matched', 'membership_extension_missing' ), true ) ) {
					$include = true;
				}
				if ( in_array( $s['exclude_state'], array( 'matched', 'data_unavailable_bypass' ), true ) ) {
					$exclude = true;
				}
				foreach ( array( 'include', 'exclude' ) as $phase ) {
					$active  = $active || ! in_array( $s[ $phase . '_state' ], array( 'disabled', 'empty_rule' ), true );
					$invalid = $invalid || 'invalid_time_bypass' === $s[ $phase . '_state' ];
				}
			}
			return $include ? 'include_rejected' : ( $exclude ? 'exclude_matched' : ( ! $active ? 'no_active_conditions' : ( $invalid ? 'invalid_time_bypass' : 'conditions_retained' ) ) );
		}
		/**
		 * Validates complete v2 rows, their order and joint values.
		 *
		 * @param mixed $rows Contract rows to validate or package.
		 * @param mixed $context Contract context identifier.
		 *
		 * @return mixed
		 */
		private function shipping_v2_rows_valid( $rows, $context ) {
			if ( ! $this->shipping_dense( $rows ) || 9 !== count( $rows ) ) {
				return false;
			}
			$m = self::shipping_v2_metadata();
			if ( false === $m ) {
				return false;
			}
			$owners   = array_keys( self::shipping_v2_owners() );
			$ordinals = array();
			foreach ( $rows as $i => $row ) {
				if ( ! is_array( $row ) || ! isset( $row['owner'], $row['context'], $row['sections'] ) || $owners[ $i ] !== $row['owner'] || $row['context'] !== $context ) {
					return false;
				}
				$found = null;
				foreach ( $m['profiles'] as $profile ) {
					$keys = array_keys( $profile );
					$keys[ array_search( 'sections_profile', $keys, true ) ] = 'sections';
					if ( ! $this->shipping_exact( $row, $keys ) ) {
						continue;
					}
					$ok = true;
					foreach ( $profile as $key => $value ) {
						if ( 'sections_profile' === $key ) {
							continue;
						}
						if ( 'call_ordinal' === $key && is_array( $value ) ) {
							$ok = $ok && is_int( $row[ $key ] ) && in_array( $row[ $key ], $value, true );
						} else {
							$ok = $ok && $row[ $key ] === $value;
						}
					}
					if ( $ok ) {
						$found = $profile['sections_profile'];
						break;
					}
				}
				if ( null === $found || ! $this->shipping_dense( $row['sections'] ) ) {
					return false;
				}
				$expected = 'empty' === $found ? array() : self::shipping_v2_sections()[ $row['owner'] ];
				if ( count( $expected ) !== count( $row['sections'] ) ) {
					return false;
				}
				foreach ( $expected as $j => $section ) {
					$s = $row['sections'][ $j ];
					if ( ! $this->shipping_exact( $s, array( 'section', 'include_state', 'exclude_state', 'include_path', 'exclude_path' ) ) || $s['section'] !== $section ) {
						return false;
					}
					foreach ( array( 'include', 'exclude' ) as $phase ) {
						$pair = array( $s[ $phase . '_state' ], $s[ $phase . '_path' ] );
						if ( ! in_array( $pair, $m['phase_pairs'][ $row['owner'] . '/' . $section . '/' . $context ], true ) || ( 'actual' !== $found && array( $found, null ) !== $pair ) || ( 'actual' === $found && in_array( $pair[0], array( 'not_evaluated', 'unsupported' ), true ) ) ) {
							return false;
						}
					}
					if ( ( 'disabled' === $s['include_state'] ) !== ( 'disabled' === $s['exclude_state'] ) ) {
						return false;
					}
				}
				if ( 'actual' === $found && $this->shipping_v2_section_reason( $row['sections'] ) !== $row['reason'] ) {
					return false;
				}
				if ( null !== $row['call_ordinal'] ) {
					if ( in_array( $row['call_ordinal'], $ordinals, true ) ) {
						return false;
					}
					$ordinals[] = $row['call_ordinal'];
				}
			}
			return true;
		}
		/**
		 * Builds a v2 response from validated rows.
		 *
		 * @param mixed $rows Contract rows to validate or package.
		 * @param mixed $context Contract context identifier.
		 *
		 * @return mixed
		 */
		private function shipping_v2_response( $rows, $context ) {
			if ( ! $this->shipping_v2_rows_valid( $rows, $context ) ) {
				return $this->shipping_error( 'booster_shipping_diagnostic_unavailable' );
			}
			return array(
				'schema_version'      => 2,
				'scope'               => 'shipping_conditions_and_options',
				'context'             => $context,
				'rows'                => $rows,
				'observations_atomic' => false,
				'final_availability'  => null,
				'label'               => 'explicit_merchant_scenario' === $context ? 'Independent merchant-supplied shipping hypotheses; not an atomic pipeline or final shipping availability. Time uses the real clock.' : 'Independent own-actor Booster callback observations; not final shipping availability. The free-product availability row is separately selected.',
			);
		}
		/**
		 * Validates a complete v2 single or comparison response.
		 *
		 * @param mixed $value Value supplied to this method.
		 * @param mixed $compare Whether the comparison schema is requested.
		 *
		 * @return mixed
		 */
		private function shipping_v2_output_valid( $value, $compare ) {
			$m = self::shipping_v2_metadata();
			if ( false === $m ) {
				return false;
			}
			if ( ! $this->shipping_v2_shape( $value, $m['schemas']['output_schemas'][ $compare ? 'comparison_v2' : 'single_v2' ] ) ) {
				return false;
			}
			if ( $compare ) {
				foreach ( $value['results'] as $v ) {
					if ( ! $this->shipping_v2_output_valid( $v, false ) ) {
						return false;
					}
				}
				return true;
			}
			return $this->shipping_v2_rows_valid( $value['rows'], $value['context'] );
		}
		/**
		 * Checks the actual v2 owner and declaring method source.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $module Callback owner instance.
		 *
		 * @return mixed
		 */
		private function shipping_v2_source_valid( $owner, $module ) {
			$owners = self::shipping_v2_owners();
			if ( ! isset( $owners[ $owner ] ) || ! is_object( $module ) || get_class( $module ) !== $owners[ $owner ] ) {
				return false;
			}
			$section = 0 === strpos( $owner, 'shipping_options_' ) ? 'shipping_options' : $owner;
			$file    = realpath( __DIR__ . '/class-wcj-' . str_replace( '_', '-', $section ) . '.php' );
			$c       = new ReflectionClass( $module );
			if ( false === $file || $c->getFileName() !== $file ) {
				return false;
			}
			if ( isset( self::shipping_v2_sections()[ $owner ] ) ) {
				$method = new ReflectionMethod( $module, 'available_shipping_methods' );
				$check  = new ReflectionMethod( $module, 'check' );
				return 'WCJ_Module_Shipping_By_Condition' === $method->getDeclaringClass()->getName() && $method->getFileName() === realpath( __DIR__ . '/classes/class-wcj-module-shipping-by-condition.php' ) && $check->getDeclaringClass()->getName() === $owners[ $owner ] && $check->getFileName() === $file;
			}
			$method = new ReflectionMethod( $module, $this->shipping_v2_callback( $owner ) );
			return $method->getFileName() === $file && $method->getDeclaringClass()->getName() === $owners[ $owner ] && 2 === $method->getNumberOfParameters();
		}
		/**
		 * Returns the callback name for the selected v2 owner.
		 *
		 * @param mixed $owner Booster owner identifier.
		 *
		 * @return mixed
		 */
		private function shipping_v2_callback( $owner ) {
			$map = array(
				'shipping_options_most_expensive'  => 'show_most_expensive_shipping',
				'shipping_options_hide_free'       => 'hide_shipping_when_free_is_available',
				'shipping_options_free_by_product' => 'free_shipping_by_product',
			);
			return isset( $map[ $owner ] ) ? $map[ $owner ] : 'available_shipping_methods';
		}
		/**
		 * Checks the selected v2 owner callback and argument count.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $module Callback owner instance.
		 *
		 * @return mixed
		 */
		private function shipping_v2_registered( $owner, $module ) {
			global $wp_filter;
			$hook     = 'shipping_options_free_by_product' === $owner ? 'woocommerce_shipping_free_shipping_is_available' : 'woocommerce_package_rates';
			$callback = array( $module, $this->shipping_v2_callback( $owner ) );
			if ( ! isset( $wp_filter[ $hook ]->callbacks ) ) {
				return false;
			}
			$seen = 0;
			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				if ( ! is_int( $priority ) ) {
					return false;
				}
				foreach ( $callbacks as $cb ) {
					if ( isset( $cb['function'] ) && $cb['function'] === $callback ) {
						if ( ! isset( $cb['accepted_args'] ) || 2 !== $cb['accepted_args'] || ( 'shipping_options_free_by_product' === $owner && PHP_INT_MAX !== $priority ) ) {
									return false;
						}
						++$seen;
					}
				}
			}
			return 1 === $seen;
		}
		/**
		 * Resolves module, role and registration admission for one v2 owner.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $module Callback owner instance.
		 *
		 * @return mixed
		 */
		private function shipping_v2_admission( $owner, $module ) {
			$section    = 0 === strpos( $owner, 'shipping_options_' ) ? 'shipping_options' : $owner;
			$allowed    = ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( $section );
			$enabled    = $allowed && 'yes' === wcj_get_option( 'wcj_' . $section . '_enabled', 'no' );
			$registered = $this->shipping_v2_registered( $owner, $module );
			if ( ( ! $allowed || ! $enabled ) && ! $registered ) {
				return 'module_not_admitted';
			}
			return ! $allowed || ! $enabled || ! $registered || ! $this->shipping_v2_source_valid( $owner, $module ) ? 'module_admission_denied' : null;
		}
		/**
		 * Checks a bounded consecutive list of condition values.
		 *
		 * @param mixed $values Configured condition values.
		 *
		 * @return mixed
		 */
		private function shipping_v2_values_valid( $values ) {
			if ( ! $this->shipping_dense( $values ) || count( $values ) > 32 ) {
				return false;
			}
			foreach ( $values as $v ) {
				if ( ( ! is_string( $v ) && ! is_int( $v ) ) || ( is_string( $v ) && strlen( $v ) > 128 ) ) {
					return false;
				}
			}
			return true;
		}
		/**
		 * Evaluates one supported condition section against explicit facts.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $section Condition section identifier.
		 * @param mixed $values Configured condition values.
		 * @param mixed $phase Include or exclude phase.
		 * @param mixed $facts Explicit tagged facts.
		 * @param mixed $cache Request-local scenario fact cache.
		 *
		 * @return mixed
		 */
		private function shipping_v2_check( $module, $owner, $section, $values, $phase, $facts, &$cache ) {
			if ( 'shipping_by_products' === $owner ) {
				$cart = 'yes' === wcj_get_option( 'wcj_shipping_by_' . $section . '_cart_not_package', 'yes' );
				$path = $cart ? 'cart' : 'package';
				if ( $cart ? ( ! $facts['cart_present'] || $facts['cart_empty'] ) : ! $facts['package_contents_present'] ) {
					return array( true, 'data_unavailable_bypass', 'unavailable_data' );
				}
				$variations = 'yes' === wcj_get_option( 'wcj_shipping_by_' . $section . '_add_variations_enabled', 'no' );
				if ( 'products' === $section && $variations ) {
					$expanded = array();
					foreach ( $values as $id ) {
						$mapping = null;
						foreach ( $facts['variable_expansions'] as $v ) {
							if ( $v['configured_id']['value'] === $id ) {
								$mapping = $v;
								break;
							}
						}
						if ( null === $mapping ) {
							return 'unsupported_catalogue_scenario';
						}
						if ( $mapping['is_variable'] ) {
							foreach ( $mapping['children'] as $child ) {
								$expanded[] = $child['value'];
							}
						} else {
							$expanded[] = $id;
						}
					}
					$values = array_unique( $expanded );
					if ( count( $values ) > 32 ) {
						return 'unsupported_rule_shape';
					}
				}
				$all = 'include' === $phase && 'yes' === wcj_get_option( 'wcj_shipping_by_' . $section . '_validate_all_enabled', 'no' );
				foreach ( $facts[ $cart ? 'cart_items' : 'package_items' ] as $item ) {
					if ( 'products' === $section ) {
						$id  = $variations && 0 !== $item['variation_id']['value'] && '0' !== $item['variation_id']['value'] ? $item['variation_id']['value'] : $item['product_id']['value'];
						$ids = array( $id );
					} elseif ( 'classes' === $section ) {
						$ids = array( $item['shipping_class_id']['value'] );
					} else {
						$ids = array();
						foreach ( $item[ 'product_cats' === $section ? 'category_ids' : 'tag_ids' ] as $id ) {
							$ids[] = $id['value'];
						}
						if ( empty( $ids ) && $all ) {
							return array( false, 'not_matched', $path );
						}
					}
					foreach ( $ids as $id ) {
						$match = in_array( (string) $id, $values, true );
						if ( $all && ! $match ) {
							return array( false, 'not_matched', $path );
						}
						if ( ! $all && $match ) {
							return array( true, 'matched', $path );
						}
					}
				}
				return array( $all, $all ? 'matched' : 'not_matched', $path );
			}
			if ( 'shipping_by_user_role' === $owner ) {
				$path = 'supplied_scenario';
				if ( 'user_roles' === $section ) {
					if ( ! isset( $cache['roles'] ) ) {
						$multi          = wcj_get_option( 'wcj_' . $owner . '_check_multiple_roles', 'no' );
						$cache['roles'] = $facts[ 'no' === $multi ? 'first_roles' : 'all_roles' ];
					}
					$result = count( array_intersect( $cache['roles'], $values ) ) > 0;
				} elseif ( 'user_id' === $section ) {
					$result = in_array( (string) $facts['actor_id'], $values, true );
				} else {
					if ( ! $facts['membership_extension_present'] ) {
						return array( false, 'membership_extension_missing', $path );
					}
					$result = false;
					foreach ( $values as $plan ) {
						$at = isset( $cache['membership'] ) ? $cache['membership'] : 0;
						if ( ! isset( $facts['membership_calls'][ $at ] ) || $facts['membership_calls'][ $at ]['plan_id']['value'] !== $plan ) {
							return 'unsupported_membership_hypothesis';
						}
						$call                = $facts['membership_calls'][ $at ];
						$cache['membership'] = $at + 1;
						if ( $call['result'] ) {
							$result = true;
							break;
						}
					}
				}
				return array( $result, $result ? 'matched' : 'not_matched', $path );
			}
			if ( 'shipping_by_cities' === $owner ) {
				if ( ! is_string( $values ) || strlen( $values ) > 8192 ) {
					return 'unsupported_rule_shape';
				}
				$lines = array_map( 'strtoupper', array_map( 'trim', explode( PHP_EOL, $values ) ) );
				if ( count( $lines ) > 32 ) {
					return 'unsupported_rule_shape';
				}
				$value  = strtoupper( $facts[ 'cities' === $section ? 'city' : 'postcode' ] );
				$result = 'cities' === $section ? in_array( $value, $lines, true ) : wcj_check_postcode( $value, $lines );
				return array( $result, $result ? 'matched' : 'not_matched', 'supplied_scenario' );
			}
			if ( ! is_string( $values ) || strlen( $values ) > 128 ) {
				return 'unsupported_rule_shape';
			}
			$parsed = $module->parse_time( $values );
			if ( false === $parsed ) {
				return array( 'include' === $phase, 'invalid_time_bypass', 'invalid_range' );
			}
			$now    = wcj_get_timestamp_date_from_gmt();
			$result = $now >= $parsed['time_from'] && $now <= $parsed['time_to'];
			return array( $result, $result ? 'matched' : 'not_matched', 'valid_real_clock' );
		}
		/**
		 * Evaluates the configured sections for one condition owner.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $module Callback owner instance.
		 * @param mixed $key Explicit rule or rate key.
		 * @param mixed $facts Explicit tagged facts.
		 *
		 * @return mixed
		 */
		private function shipping_v2_condition_scenario( $owner, $module, $key, $facts ) {
			$sections = array();
			$cache    = array();
			foreach ( self::shipping_v2_sections()[ $owner ] as $section ) {
				$s = array(
					'section'       => $section,
					'include_state' => 'disabled',
					'exclude_state' => 'disabled',
					'include_path'  => null,
					'exclude_path'  => null,
				);
				if ( 'no' !== wcj_get_option( 'wcj_shipping_by_' . $section . '_section_enabled', 'yes' ) ) {
					foreach ( array( 'include', 'exclude' ) as $phase ) {
						$values                 = get_option( 'wcj_shipping_' . $section . '_' . $phase . ( 'instance' === $key['kind'] ? '_instance_' : '_' ) . $key['value'], '' );
						$s[ $phase . '_state' ] = 'empty_rule';
						if ( ! empty( $values ) ) {
							if ( ! in_array( $owner, array( 'shipping_by_cities', 'shipping_by_time' ), true ) && ! $this->shipping_v2_values_valid( $values ) ) {
								return array( 'unsupported_rule_shape', null );
							}
							$r = $this->shipping_v2_check( $module, $owner, $section, $values, $phase, $facts, $cache );
							if ( is_string( $r ) ) {
								return array( $r, null );
							}
							$s[ $phase . '_state' ] = $r[1];
							$s[ $phase . '_path' ]  = $r[2];
						}
					}
				}
				$sections[] = $s;
			}
			return array( $this->shipping_v2_section_reason( $sections ), $sections );
		}
		/**
		 * Evaluates one shipping-options owner against explicit facts.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $f Explicit facts.
		 *
		 * @return mixed
		 */
		private function shipping_v2_options_scenario( $owner, $f ) {
			if ( 'shipping_options_free_by_product' === $owner ) {
				$values = wcj_get_option( 'wcj_shipping_free_shipping_by_product_products', '' );
				if ( empty( $values ) ) {
					return 'empty_grant_list';
				}
				if ( ! $this->shipping_v2_values_valid( $values ) || ! $f['package_contents_present'] ) {
					return 'unsupported_rule_shape';
				}
				$type  = apply_filters( 'booster_option', 'all', wcj_get_option( 'wcj_shipping_free_shipping_by_product_type', 'all' ) );
				$grant = false;
				foreach ( $f['items'] as $item ) {
					if ( in_array( $item['product_id']['value'], $values, true ) ) {
						if ( 'at_least_one' === $type ) {
							return 'at_least_one_grant';
						}
						$grant = true;
					} elseif ( 'all' === $type ) {
						return 'all_nonmatch_preserved';
					}
				}
				return $grant ? ( 'all' === $type ? 'all_items_grant' : 'source_fallback_grant' ) : 'package_no_grant';
			}
			$rates    = array();
			$selected = null;
			foreach ( $f['rates'] as $record ) {
				$rate                             = (object) array(
					'id'        => $record['id']['value'],
					'method_id' => $record['method_id'],
					'cost'      => $record['cost'],
				);
				$rates[ $record['key']['value'] ] = $rate;
				if ( $record['key']['kind'] === $f['selected_key']['kind'] && $record['key']['value'] === $f['selected_key']['value'] ) {
					$selected = $rate;
				}
			}
			$key = $f['selected_key']['value'];
			if ( 'shipping_options_most_expensive' === $owner ) {
				if ( 'yes' !== wcj_get_option( 'wcj_shipping_most_expensive_enabled', 'no' ) ) {
					return 'feature_disabled';
				}
				$ignored = wcj_get_option( 'wcj_shipping_most_expensive_ignored_methods', array( 'free_shipping' ) );
				if ( ! $this->shipping_v2_values_valid( $ignored ) ) {
					return 'unsupported_rule_shape';
				}
				$winner = null;
				$out    = array();
				foreach ( $rates as $rate ) {
					if ( ! in_array( $rate->method_id, $ignored, true ) && ( null === $winner || $rate->cost > $winner->cost ) ) {
						$winner = $rate;
					}
				}
				if ( null !== $winner ) {
					$out[ $winner->id ] = $winner;
				}
				foreach ( $rates as $rate ) {
					if ( in_array( $rate->method_id, $ignored, true ) ) {
						$out[ $rate->id ] = $rate;
					}
				}
				$survives = false;
				foreach ( $out as $v ) {
					$survives = $survives || $v === $selected;
				}
				if ( $survives && ( ! isset( $out[ $key ] ) || $out[ $key ] !== $selected ) ) {
					return 'selected_object_rekeyed';
				}
				if ( $survives ) {
					return in_array( $selected->method_id, $ignored, true ) ? 'ignored_method_kept' : 'most_expensive_kept';
				}
				return $winner === $selected || in_array( $selected->method_id, $ignored, true ) ? 'id_collision_overwrite' : 'most_expensive_not_selected';
			}
			$free   = false;
			$reason = 'hidden_by_free';
			foreach ( $rates as $rate_key => $rate ) {
				if ( false !== strpos( $rate_key, 'free_shipping' ) ) {
					$free = true;
					if ( $rate === $selected ) {
						$reason = 'kept_free_key';
					}
				} elseif ( 'except_local_pickup' === apply_filters( 'booster_option', 'hide_all', wcj_get_option( 'wcj_shipping_hide_if_free_available_type', 'hide_all' ) ) && false !== strpos( $rate_key, 'local_pickup' ) ) {
					if ( $rate === $selected ) {
						$reason = 'kept_local_key';
					}
				} elseif ( 'flat_rate_only' === apply_filters( 'booster_option', 'hide_all', wcj_get_option( 'wcj_shipping_hide_if_free_available_type', 'hide_all' ) ) && false === strpos( $rate_key, 'flat_rate' ) ) {
					if ( $rate === $selected ) {
						$reason = 'kept_nonflat_key';
					}
				}
			}
			return $free ? $reason : 'no_free_key';
		}
		/**
		 * Explains the nine owners for an explicit merchant scenario.
		 *
		 * @param mixed $input Explicit diagnostic input.
		 *
		 * @return mixed
		 */
		private function shipping_v2_explain( $input ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return $this->shipping_error( 'booster_shipping_capability_required' );
			}
			if ( ! $this->is_full_experience() ) {
				return $this->shipping_error( 'booster_shipping_elite_required' );
			}
			if ( false === self::shipping_v2_metadata() ) {
				return $this->shipping_error( 'booster_shipping_diagnostic_unavailable' );
			}
			if ( ! $this->shipping_v2_input_valid( $input ) ) {
				return $this->shipping_error();
			}
			if ( wcj_is_module_enabled( 'modules_by_user_roles' ) ) {
				return $this->shipping_error( 'booster_shipping_context_not_supported' );
			}
			$modules = $this->shipping_modules();
			$rows    = array();
			$legacy  = null;
			foreach ( self::shipping_v2_owners() as $owner => $class ) {
				$section = 0 === strpos( $owner, 'shipping_options_' ) ? 'shipping_options' : $owner;
				$module  = isset( $modules[ $section ] ) ? $modules[ $section ] : null;
				$reason  = $this->shipping_v2_admission( $owner, $module );
				$detail  = null;
				$family  = in_array( $owner, array_keys( self::shipping_owners() ), true ) ? 'amount_quantity' : ( 'shipping_by_products' === $owner ? 'products' : ( 'shipping_by_user_role' === $owner ? 'actor' : ( 'shipping_by_cities' === $owner ? 'address' : ( 'shipping_by_time' === $owner ? 'time' : ( 'shipping_options_free_by_product' === $owner ? 'free_grant' : 'ordered_rates' ) ) ) ) );
				if ( null === $reason && isset( $input['owners'][ $owner ] ) && ( ! isset( $module->use_shipping_instances ) || ! is_bool( $module->use_shipping_instances ) || ( 'instance' === $input['owners'][ $owner ]['rule_key']['kind'] ) !== $module->use_shipping_instances ) ) {
					$reason = 'unsupported_rate_identity';
				}
				if ( null === $reason && 'unavailable' === $input['facts'][ $family ]['status'] ) {
					$reason = 'missing_supplied_facts';
				}
				if ( null === $reason ) {
					if ( 'amount_quantity' === $family ) {
						if ( null === $legacy ) {
							$f = $input['facts']['amount_quantity'];
							unset( $f['status'] );
							$legacy = $this->explain_shipping_options(
								array(
									'mode'   => 'scenario',
									'owners' => array_intersect_key( $input['owners'], self::shipping_owners() ),
									'facts'  => $f,
								)
							);
						}
						if ( is_wp_error( $legacy ) ) {
							return $legacy;
						}
						foreach ( $legacy['rows'] as $row ) {
							if ( $owner === $row['owner'] ) {
								$reason = $row['reason'];
								break;
							}
						}
					} elseif ( isset( self::shipping_v2_sections()[ $owner ] ) ) {
							$r      = $this->shipping_v2_condition_scenario( $owner, $module, $input['owners'][ $owner ]['rule_key'], $input['facts'][ $family ] );
							$reason = $r[0];
							$detail = $r[1];
					} else {
									$reason = $this->shipping_v2_options_scenario( $owner, $input['facts'][ $family ] );
					}
				}
				$rows[] = $this->shipping_v2_row( $owner, 'explicit_merchant_scenario', $reason, $detail );
			}
			return $this->shipping_v2_response( $rows, 'explicit_merchant_scenario' );
		}
		/**
		 * Compares two independent v2 merchant scenarios.
		 *
		 * @param mixed $input Explicit diagnostic input.
		 *
		 * @return mixed
		 */
		private function shipping_v2_compare( $input ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return $this->shipping_error( 'booster_shipping_capability_required' );
			}
			if ( ! $this->is_full_experience() ) {
				return $this->shipping_error( 'booster_shipping_elite_required' );
			}
			if ( false === self::shipping_v2_metadata() ) {
				return $this->shipping_error( 'booster_shipping_diagnostic_unavailable' );
			}
			if ( ! $this->shipping_v2_input_valid( $input, true ) ) {
				return $this->shipping_error();
			}
			$results = array();
			foreach ( $input['scenarios'] as $in ) {
				$v = $this->shipping_v2_explain( $in );
				if ( is_wp_error( $v ) ) {
					return $v;
				}
				$results[] = $v;
			}
			$m = self::shipping_v2_metadata();
			if ( false === $m ) {
				return $this->shipping_error( 'booster_shipping_diagnostic_unavailable' );
			}
			$schema = $m['schemas']['output_schemas']['comparison_v2'];
			$out    = array(
				'schema_version'      => 2,
				'scope'               => 'shipping_conditions_and_options',
				'context'             => 'explicit_merchant_scenario',
				'results'             => $results,
				'observations_atomic' => false,
				'final_availability'  => null,
				'label'               => $schema['properties']['label']['enum'][0],
			);
			return $this->shipping_v2_output_valid( $out, true ) ? $out : $this->shipping_error( 'booster_shipping_diagnostic_unavailable' );
		}


		/**
		 * Passive taps retain only finite outcomes, never raw shopper/configuration values.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $rates Rate map supplied by the callback.
		 */
		public static function shipping_v2_start( $module, $owner, $rates ) {
			$g =& self::$shipping_generation;
			if ( ! isset( $g['version'] ) || 2 !== $g['version'] || 'unarmed' === $g['state'] ) {
				return null;
			}
			if ( 'pass' !== $g['state'] || ! isset( $g['modules'][ $owner ] ) || $g['modules'][ $owner ] !== $module || ! is_array( $rates ) || isset( $g['captures'][ $owner ] ) || isset( $g['rows'][ $owner ] ) ) {
				$g['state'] = 'invalid';
				return null;
			}
			$g['ordinal'] = min( 10, $g['ordinal'] + 1 );
			if ( $g['ordinal'] > 9 ) {
				$g['state'] = 'invalid';
				return null;
			}
			$key     = $g['rate']['value'];
			$present = array_key_exists( $key, $rates );
			if ( ! self::$shipping_observer->shipping_v2_registered( $owner, $module ) || ! self::$shipping_observer->shipping_v2_position_valid( $owner, $module ) ) {
				$g['state'] = 'invalid';
				return null;
			}
			$g['captures'][ $owner ] = array(
				'owner'        => $owner,
				'call_ordinal' => $g['ordinal'],
				'entry_before' => $present,
				'object'       => $present ? $rates[ $key ] : null,
				'sections'     => array(),
				'keys'         => array(),
				'modes'        => array(),
				'bounds'       => array(),
				'unsupported'  => count( $rates ) > 32,
			);
			$g['counts'][ $owner ]   = array( 1, 0 );
			return $owner;
		}
		/**
		 * Selects the section being captured by an open owner callback.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $key Explicit rule or rate key.
		 * @param mixed $rate Selected shipping rate.
		 * @param mixed $section Condition section identifier.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_section( $module, $key, $rate, $section ) {
			$g =& self::$shipping_generation;
			if ( ! isset( $g['version'] ) || 2 !== $g['version'] || 'pass' !== $g['state'] ) {
				return;
			}
			foreach ( $g['captures'] as $owner => &$c ) {
				if ( $g['modules'][ $owner ] === $module && $c['entry_before'] && $c['object'] === $rate && $key !== $g['rate']['value'] ) {
					$g['state'] = 'invalid';
					unset( $c );
					return;
				}
			}
			unset( $c );
			if ( $key !== $g['rate']['value'] ) {
				return;
			}
			foreach ( $g['captures'] as $owner => &$c ) {
				if ( $g['modules'][ $owner ] === $module && $c['entry_before'] ) {
					$c['sections'][ $section ] = array(
						'section'       => $section,
						'include_state' => 'disabled',
						'exclude_state' => 'disabled',
						'include_path'  => null,
						'exclude_path'  => null,
					);
				}
			}
			unset( $c );
		}
		/**
		 * Records and returns the exact callback rule key without repair.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $rate Selected shipping rate.
		 * @param mixed $section Condition section identifier.
		 * @param mixed $phase Include or exclude phase.
		 * @param mixed $mode Rule-key mode.
		 * @param mixed $key Explicit rule or rate key.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_key( $module, $rate, $section, $phase, $mode, $key ) {
			$g =& self::$shipping_generation;
			if ( ! isset( $g['version'] ) || 2 !== $g['version'] || 'pass' !== $g['state'] ) {
				return $key;
			}
			foreach ( $g['captures'] as $owner => &$c ) {
				if ( $g['modules'][ $owner ] === $module && $c['object'] === $rate ) {
					$tag = $section . '/' . $phase;
					if ( isset( $c['keys'][ $tag ] ) ) {
						$g['state'] = 'invalid';
						break;
					}
					$c['keys'][ $tag ]  = $key;
					$c['modes'][ $tag ] = $mode;
				}
			}
			unset( $c );
			return $key;
		}
		/**
		 * Records the selected include or exclude rule phase.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $key Explicit rule or rate key.
		 * @param mixed $section Condition section identifier.
		 * @param mixed $phase Include or exclude phase.
		 * @param mixed $values Configured condition values.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_phase( $module, $key, $section, $phase, $values ) {
			$g =& self::$shipping_generation;
			if ( ! isset( $g['version'] ) || 2 !== $g['version'] || 'pass' !== $g['state'] || $key !== $g['rate']['value'] ) {
				return;
			}
			foreach ( $g['captures'] as $owner => &$c ) {
				if ( $g['modules'][ $owner ] === $module && isset( $c['sections'][ $section ] ) ) {
					if ( empty( $values ) ) {
						unset( $c['current'] );
					} else {
						$c['current'] = array( $section, $phase );
					}
					$c['sections'][ $section ][ $phase . '_state' ] = empty( $values ) ? 'empty_rule' : 'unsupported';
					if ( ! empty( $values ) && ( in_array( $owner, array( 'shipping_by_cities', 'shipping_by_time' ), true ) ? ( ! is_string( $values ) || strlen( $values ) > ( 'shipping_by_time' === $owner ? 128 : 8192 ) ) : ! self::$shipping_observer->shipping_v2_values_valid( $values ) ) ) {
						$c['unsupported'] = true;
					}
				}
			}
			unset( $c );
		}
		/**
		 * Records and returns a callback predicate result.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $result Callback or diagnostic result.
		 * @param mixed $path Observed predicate path.
		 * @param mixed $special Optional special bypass state.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_result( $module, $result, $path, $special = null ) {
			$g =& self::$shipping_generation;
			if ( ! isset( $g['version'] ) || 2 !== $g['version'] || 'pass' !== $g['state'] ) {
				return $result;
			}
			foreach ( $g['captures'] as $owner => &$c ) {
				if ( $g['modules'][ $owner ] === $module && isset( $c['current'] ) ) {
					list( $section, $phase )                        = $c['current'];
					$c['sections'][ $section ][ $phase . '_state' ] = null !== $special ? $special : ( $result ? 'matched' : 'not_matched' );
					$c['sections'][ $section ][ $phase . '_path' ]  = $path;
					unset( $c['current'] );
				}
			}
			unset( $c );
			return $result;
		}
		/**
		 * Completes an open v2 owner capture.
		 *
		 * @param mixed $ticket Request-local observation ticket.
		 * @param mixed $rates Rate map supplied by the callback.
		 * @param mixed $reason Contract reason identifier.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_finish( $ticket, $rates, $reason = null ) {
			$g =& self::$shipping_generation;
			if ( null === $ticket || ! isset( $g['version'] ) || 2 !== $g['version'] || 'pass' !== $g['state'] ) {
				return;
			}
			$c = $g['captures'][ $ticket ];
			if ( ! is_array( $rates ) ) {
				$g['state'] = 'invalid';
				return;
			}
			$key              = $g['rate']['value'];
			$c['entry_after'] = array_key_exists( $key, $rates );
			$s                = self::$shipping_observer;
			if ( null === $reason ) {
				$reason = ! $c['entry_before'] ? 'selected_rate_not_seen' : ( ! empty( $c['unsupported'] ) ? 'unsupported_rule_shape' : $s->shipping_v2_section_reason( array_values( $c['sections'] ) ) );
			}
			foreach ( $c['keys'] as $tag => $value ) {
				if (
					! is_bool( $c['modes'][ $tag ] ) || ! $s->shipping_rule_key_valid(
						array(
							'kind'  => $c['modes'][ $tag ] ? 'instance' : 'method',
							'value' => $value,
						)
					)
				) {
					$reason = 'unsupported_rate_identity';
				}
			}
			if ( ! $s->shipping_v2_registered( $ticket, $g['modules'][ $ticket ] ) || ! $s->shipping_v2_position_valid( $ticket, $g['modules'][ $ticket ] ) ) {
				$g['state'] = 'invalid';
				return;
			}
			$row = $s->shipping_v2_row( $ticket, 'own_actor_observation', $reason, array_values( $c['sections'] ), $c );
			unset( $g['captures'][ $ticket ] );
			$g['counts'][ $ticket ][1] = 1;
			if ( null === $row ) {
				$g['state'] = 'invalid';
				return;
			}
			$g['rows'][ $ticket ] = $row;
		}


		/**
		 * Records the selected rate and reason for an options capture.
		 *
		 * @param mixed $ticket Request-local observation ticket.
		 * @param mixed $rate Selected shipping rate.
		 * @param mixed $reason Contract reason identifier.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_note( $ticket, $rate, $reason ) {
			$g =& self::$shipping_generation;
			if ( null !== $ticket && isset( $g['captures'][ $ticket ] ) && $g['captures'][ $ticket ]['object'] === $rate ) {
				$g['captures'][ $ticket ]['note'] = $reason;
			}
		}
		/**
		 * Completes an options capture using its observed result.
		 *
		 * @param mixed $ticket Request-local observation ticket.
		 * @param mixed $rates Rate map supplied by the callback.
		 * @param mixed $winner Observed options winner.
		 * @param mixed $free Observed free-shipping result.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_options_finish( $ticket, $rates, $winner = null, $free = null ) {
			$g =& self::$shipping_generation;
			if ( null === $ticket || 'pass' !== $g['state'] || ! isset( $g['captures'][ $ticket ] ) ) {
				return;
			}
			$c      = $g['captures'][ $ticket ];
			$key    = $g['rate']['value'];
			$reason = 'selected_rate_not_seen';
			if ( $c['entry_before'] ) {
				if ( 'shipping_options_hide_free' === $ticket ) {
					$reason = $free ? ( isset( $c['note'] ) ? $c['note'] : 'hidden_by_free' ) : 'no_free_key';
				} else {
					$survives = false;
					foreach ( $rates as $rate ) {
						$survives = $survives || $rate === $c['object'];
					}
					$ignored = isset( $c['note'] ) && 'ignored_method_kept' === $c['note'];
					if ( $survives && ( ! array_key_exists( $key, $rates ) || $rates[ $key ] !== $c['object'] ) ) {
						$reason = 'selected_object_rekeyed';
					} elseif ( $survives ) {
						$reason = $ignored ? 'ignored_method_kept' : 'most_expensive_kept';
					} else {
						$reason = $ignored || $winner === $c['object'] ? 'id_collision_overwrite' : 'most_expensive_not_selected';
					}
				}
			}
			if ( ! empty( $c['unsupported'] ) ) {
				$reason = 'unsupported_rule_shape';
			}
			self::shipping_v2_finish( $ticket, $rates, $reason );
		}
		/**
		 * Starts the selected free-shipping owner capture.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $incoming Incoming predicate result.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_free_start( $module, $incoming ) {
			$g =& self::$shipping_generation;
			if ( ! isset( $g['version'] ) || 2 !== $g['version'] || ! isset( $g['free_window'] ) || 'available' !== $g['free_window'] ) {
				return null;
			}
			$owner = 'shipping_options_free_by_product';
			if ( ! self::$shipping_observer->shipping_v2_registered( $owner, $module ) ) {
				$g['state'] = 'invalid';
				return null;
			}
			if ( 'open' !== $g['state'] || ! is_bool( $incoming ) || ! isset( $g['modules'][ $owner ] ) || $g['modules'][ $owner ] !== $module || isset( $g['rows'][ $owner ], $g['captures'][ $owner ] ) ) {
				$g['state'] = 'invalid';
				return null;
			}
			$g['ordinal'] = min( 10, $g['ordinal'] + 1 );
			if ( $g['ordinal'] > 9 || isset( $g['counts'][ $owner ] ) ) {
				$g['state'] = 'invalid';
				return null;
			}
			$g['counts'][ $owner ]   = array( 1, 0 );
			$g['captures'][ $owner ] = array(
				'entry_before' => $incoming,
				'call_ordinal' => $g['ordinal'],
			);
			return $owner;
		}
		/**
		 * Completes and returns a free-shipping predicate result.
		 *
		 * @param mixed $ticket Request-local observation ticket.
		 * @param mixed $value Value supplied to this method.
		 * @param mixed $reason Contract reason identifier.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_free_result( $ticket, $value, $reason ) {
			$g =& self::$shipping_generation;
			if ( null === $ticket || 'open' !== $g['state'] || ! isset( $g['captures'][ $ticket ] ) ) {
				return $value;
			}
			$c = $g['captures'][ $ticket ];
			if ( ! self::$shipping_observer->shipping_v2_registered( $ticket, $g['modules'][ $ticket ] ) ) {
				$g['state'] = 'invalid';
				return $value;
			}
			if ( ! is_bool( $value ) ) {
				$g['state'] = 'invalid';
				return $value;
			}
			if ( ! empty( $c['unsupported'] ) ) {
				$reason = 'unsupported_rule_shape';
			}
			$c['entry_after'] = $value;
			$row              = self::$shipping_observer->shipping_v2_row( $ticket, 'own_actor_observation', $reason, null, $c );
			unset( $g['captures'][ $ticket ] );
			$g['counts'][ $ticket ][1] = 1;
			if ( null === $row ) {
				$g['state'] = 'invalid';
			} else {
				$g['rows'][ $ticket ] = $row;
			}
			return $value;
		}


		/**
		 * Builds the strict v2 actor, blog, package, rate and instance nonce action.
		 *
		 * @param mixed $package Selected shipping package.
		 * @param mixed $rate Selected shipping rate.
		 * @param mixed $instance Selected method instance identifier.
		 * @param mixed $blog Captured blog identifier.
		 * @param mixed $actor Captured actor identifier.
		 *
		 * @return mixed
		 */
		private function shipping_v2_nonce_action( $package, $rate, $instance, $blog, $actor ) {
			if ( ! $this->shipping_typed_key_valid( $package ) || ! $this->shipping_typed_key_valid( $rate ) || ! is_int( $instance ) || $instance < 0 || $instance > 2147483647 || ! is_int( $blog ) || $blog < 1 || ! is_int( $actor ) || $actor < 1 ) {
				return false;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Canonical authenticated payload/nonce bytes must reject invalid UTF-8 rather than use the repairing fallback in wp_json_encode.
			$json = json_encode( array( 2, 'shipping_conditions_and_options', $package['kind'], $package['value'], $rate['kind'], $rate['value'], $instance ) );
			return false === $json ? false : 'wcj_shipping_observe_v2:' . $blog . ':' . $actor . ':' . hash( 'sha256', $json );
		}
		/**
		 * Builds an admitted private v2 shipping observation link.
		 *
		 * @param mixed $package Selected shipping package.
		 * @param mixed $rate Selected shipping rate.
		 * @param mixed $instance Selected method instance identifier.
		 *
		 * @return mixed
		 */
		public function shipping_v2_observation_link( $package, $rate, $instance ) {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
				return $this->shipping_error( 'booster_shipping_capability_required' );
			}
			$id = $this->shipping_transport_selector( 'int', $instance );
			if ( false === $id ) {
				return $this->shipping_error();
			}
			$action = $this->shipping_v2_nonce_action( $package, $rate, $id['value'], get_current_blog_id(), get_current_user_id() );
			if ( false === $action ) {
				return $this->shipping_error();
			}
			return add_query_arg(
				array(
					'wcj_shipping_observe'          => '1',
					'wcj_shipping_contract_version' => '2',
					'wcj_shipping_scope'            => 'shipping_conditions_and_options',
					'wcj_shipping_package_kind'     => $package['kind'],
					'wcj_shipping_package_key'      => (string) $package['value'],
					'wcj_shipping_rate_kind'        => $rate['kind'],
					'wcj_shipping_rate_key'         => (string) $rate['value'],
					'wcj_shipping_free_instance'    => $instance,
					'wcj_shipping_nonce'            => wp_create_nonce( $action ),
				),
				wc_get_checkout_url()
			);
		}
		/**
		 * Checks the owner callback order within the selected observation window.
		 *
		 * @param mixed $owner Booster owner identifier.
		 * @param mixed $module Callback owner instance.
		 *
		 * @return mixed
		 */
		private function shipping_v2_position_valid( $owner, $module ) {
			if ( 'shipping_options_free_by_product' === $owner ) {
				return true;
			}
			global $wp_filter;
			if ( ! isset( $wp_filter['woocommerce_package_rates']->callbacks ) ) {
				return false;
			}
			$begin  = null;
			$end    = null;
			$target = null;
			$i      = 0;
			foreach ( $wp_filter['woocommerce_package_rates']->callbacks as $callbacks ) {
				foreach ( $callbacks as $cb ) {
					++$i;
					if ( ! isset( $cb['function'] ) ) {
						continue;
					}
					if ( array( self::$shipping_observer, 'begin_shipping_pass' ) === $cb['function'] ) {
						$begin = $i;
					}
					if ( array( self::$shipping_observer, 'end_shipping_pass' ) === $cb['function'] ) {
						$end = $i;
					}
					if ( array( $module, $this->shipping_v2_callback( $owner ) ) === $cb['function'] ) {
						$target = $i;
					}
				}
			}
			return null !== $begin && null !== $end && null !== $target && $begin < $target && $target < $end;
		}
		/**
		 * Admits the selected v2 shipping observation generation.
		 *
		 * @return mixed
		 */
		private function arm_shipping_v2() {
			// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this commerce capability; retain the existing least-privilege authorization.
			if ( self::$shipping_observer !== $this || 'unarmed' !== self::$shipping_generation['state'] || ! self::write_shipping_transport_headers() || ! self::shipping_transport_headers_valid() || ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! is_checkout() || is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) || ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
				self::deny_shipping_transport();
			}
			foreach ( array( 'wcj_shipping_observe', 'wcj_shipping_contract_version', 'wcj_shipping_scope', 'wcj_shipping_package_kind', 'wcj_shipping_package_key', 'wcj_shipping_rate_kind', 'wcj_shipping_rate_key', 'wcj_shipping_free_instance', 'wcj_shipping_nonce' ) as $key ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
				if ( ! isset( $_GET[ $key ] ) || ! is_string( $_GET[ $key ] ) ) {
					self::deny_shipping_transport();
				}
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
			if ( '1' !== $_GET['wcj_shipping_observe'] || '2' !== $_GET['wcj_shipping_contract_version'] || 'shipping_conditions_and_options' !== $_GET['wcj_shipping_scope'] || 1 !== preg_match( '/\A[A-Za-z0-9]{1,64}\z/', $_GET['wcj_shipping_nonce'] ) ) {
				self::deny_shipping_transport();
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
			$package = $this->shipping_transport_selector( $_GET['wcj_shipping_package_kind'], $_GET['wcj_shipping_package_key'] );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
			$rate = $this->shipping_transport_selector( $_GET['wcj_shipping_rate_kind'], $_GET['wcj_shipping_rate_key'] );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
			$instance = $this->shipping_transport_selector( 'int', $_GET['wcj_shipping_free_instance'] );
			if ( false === $package || false === $rate || false === $instance ) {
				self::deny_shipping_transport();
			}
			self::$shipping_generation = array(
				'state'         => 'unarmed',
				'version'       => 2,
				'actor'         => get_current_user_id(),
				'blog'          => get_current_blog_id(),
				'package'       => $package,
				'rate'          => $rate,
				'free_instance' => $instance['value'],
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed transport and bound payment/shipping nonce are verified by identity_valid before arming; malformed raw selectors must be rejected, not sanitized into acceptance.
				'nonce'         => $_GET['wcj_shipping_nonce'],
				'passes'        => 0,
				'ordinal'       => 0,
				'counts'        => array(),
				'rows'          => array(),
				'captures'      => array(),
				'renders'       => 0,
				'free_window'   => null,
			);
			if ( ! $this->shipping_identity_valid() || wcj_is_module_enabled( 'modules_by_user_roles' ) || ! $this->shipping_core_sources_valid() || ! $this->shipping_v2_extra_sources() ) {
				self::deny_shipping_transport();
			}
			if ( false === self::shipping_v2_metadata() ) {
				self::deny_shipping_transport();
			}
			$modules                              = $this->shipping_modules();
			self::$shipping_generation['modules'] = array();
			foreach ( self::shipping_v2_owners() as $owner => $class ) {
				$id     = 0 === strpos( $owner, 'shipping_options_' ) ? 'shipping_options' : $owner;
				$module = isset( $modules[ $id ] ) ? $modules[ $id ] : null;
				if ( null !== $module && ! $this->shipping_v2_source_valid( $owner, $module ) ) {
					self::deny_shipping_transport();
				}
				self::$shipping_generation['modules'][ $owner ] = $module;
			}
			self::$shipping_generation['state'] = 'armed';
		}
		/**
		 * Checks the pinned WooCommerce free-shipping source identities.
		 *
		 * @return mixed
		 */
		private function shipping_v2_extra_sources() {
			$specs = array(
				'WC_Shipping_Free_Shipping' => array( 'shipping/free-shipping/class-wc-shipping-free-shipping.php', '8371a7f5a4d9f460f1243e64c6ed7a88df2c7b02825114161bf7b95e5c632712' ),
				'WC_Shipping_Method'        => array( 'abstracts/abstract-wc-shipping-method.php', 'cf324b0e37f34399b66b00a01006df03a4a190dbed3e18a7ca26250b7babba53' ),
			);
			foreach ( $specs as $class => $spec ) {
				$file = realpath( WC_ABSPATH . 'includes/' . $spec[0] );
				if ( false === $file || ! class_exists( $class ) ) {
					return false;
				}
				$class_file = ( new ReflectionClass( $class ) )->getFileName();
				if ( $file !== $class_file || hash_file( 'sha256', $file ) !== $spec[1] ) {
					return false;
				}
			}
			return true;
		}
		/**
		 * Checks the selected native free-shipping method instance.
		 *
		 * @param mixed $method Selected native shipping method.
		 *
		 * @return mixed
		 */
		private function shipping_v2_selected_method( $method ) {
			if ( ! is_object( $method ) || 'WC_Shipping_Free_Shipping' !== get_class( $method ) ) {
				return false;
			}
			foreach ( array( 'id', 'instance_id' ) as $key ) {
				$r = new ReflectionProperty( $method, $key );
				if ( ! $r->isPublic() || $r->isStatic() ) {
					return false;
				}
			}
			return 'free_shipping' === $method->id && is_int( $method->instance_id ) && $method->instance_id === self::$shipping_generation['free_instance'];
		}
		/**
		 * Checks the bounded native free-shipping call sequence.
		 *
		 * @param mixed $trace Captured argument-free callback stack.
		 * @param mixed $callback Expected callback name.
		 * @param mixed $filter Whether the filter-specific stack is expected.
		 * @param mixed $method Selected native shipping method.
		 *
		 * @return mixed
		 */
		private function shipping_v2_window_trace( $trace, $callback, $filter, $method ) {
			$sequence = $filter ? array( array( '', $callback ), array( 'WP_Hook', 'apply_filters' ), array( '', 'apply_filters' ), array( 'WC_Shipping_Free_Shipping', 'is_available' ), array( 'WC_Shipping_Method', 'get_rates_for_package' ), array( 'WC_Shipping', 'calculate_shipping_for_package' ), array( 'WC_Shipping', 'calculate_shipping' ), array( 'WC_Cart', 'calculate_shipping' ) ) : array( array( '', $callback ), array( 'WP_Hook', 'apply_filters' ), array( 'WP_Hook', 'do_action' ), array( '', 'do_action' ), array( 'WC_Shipping', 'calculate_shipping_for_package' ), array( 'WC_Shipping', 'calculate_shipping' ), array( 'WC_Cart', 'calculate_shipping' ) );
			if ( count( $trace ) < count( $sequence ) || count( $trace ) > 8 || ! isset( $trace[0]['object'] ) || $trace[0]['object'] !== $this ) {
				return false;
			}
			foreach ( $sequence as $i => $expected ) {
				$f = $trace[ $i ];
				if ( ! isset( $f['function'] ) || $f['function'] !== $expected[1] || ( 0 !== $i && ( isset( $f['class'] ) ? $f['class'] : '' ) !== $expected[0] ) ) {
					return false;
				}
				if ( '' !== $expected[0] ) {
					if ( ! isset( $f['object'] ) || ( 'WC_Shipping_Method' === $expected[0] ? 'WC_Shipping_Free_Shipping' !== get_class( $f['object'] ) : get_class( $f['object'] ) !== $expected[0] ) ) {
						return false;
					}
					$decl = new ReflectionMethod( $f['object'], $expected[1] );
					if ( $decl->getDeclaringClass()->getName() !== $expected[0] ) {
						return false;
					}
					$files = array(
						'WP_Hook'                   => ABSPATH . WPINC . '/class-wp-hook.php',
						'WC_Shipping_Free_Shipping' => WC_ABSPATH . 'includes/shipping/free-shipping/class-wc-shipping-free-shipping.php',
						'WC_Shipping_Method'        => WC_ABSPATH . 'includes/abstracts/abstract-wc-shipping-method.php',
						'WC_Shipping'               => WC_ABSPATH . 'includes/class-wc-shipping.php',
						'WC_Cart'                   => WC_ABSPATH . 'includes/class-wc-cart.php',
					);
					if ( ! isset( $files[ $expected[0] ] ) || $decl->getFileName() !== realpath( $files[ $expected[0] ] ) ) {
						return false;
					}
				}
			}
			$offset = $filter ? 5 : 4;
			if ( $trace[ $offset ]['object'] !== $trace[ $offset + 1 ]['object'] || $trace[ $offset + 2 ]['object'] !== self::$shipping_generation['cart'] || ( isset( self::$shipping_generation['shipping'] ) && $trace[ $offset ]['object'] !== self::$shipping_generation['shipping'] ) ) {
				return false;
			}
			if ( $filter && ( $trace[3]['object'] !== $method || $trace[4]['object'] !== $method ) ) {
				return false;
			}
			return $trace[ $offset ]['object'];
		}
		/**
		 * Admits the selected method's before-availability subwindow.
		 *
		 * @param mixed $package Selected shipping package.
		 * @param mixed $method Selected native shipping method.
		 *
		 * @return mixed
		 */
		public function shipping_v2_before_method( $package, $method ) {
			$g =& self::$shipping_generation;
			if ( ! isset( $g['version'] ) || 2 !== $g['version'] || 'open' !== $g['state'] || ! $this->shipping_v2_selected_method( $method ) ) {
				return;
			}
			$trace    = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 8 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Bounded argument-free callback provenance is an admission check, never a debug log or response.
			$shipping = $this->shipping_v2_window_trace( $trace, __FUNCTION__, false, $method );
			if ( false === $shipping || null !== $g['free_window'] || ! is_array( $package ) || ! array_key_exists( 'package_id', $package ) || $this->shipping_package_id( $package, null ) !== $g['package_id'] ) {
				$g['state'] = 'invalid';
				return;
			}
			$g['shipping']    = $shipping;
			$g['free_method'] = $method;
			$g['free_window'] = 'before';
		}
		/**
		 * Records the selected method's availability within its admitted subwindow.
		 *
		 * @param mixed $available Incoming method availability.
		 * @param mixed $package Selected shipping package.
		 * @param mixed $method Selected native shipping method.
		 *
		 * @return mixed
		 */
		public function shipping_v2_availability( $available, $package, $method ) {
			$g =& self::$shipping_generation;
			if ( ! isset( $g['version'] ) || 2 !== $g['version'] || 'open' !== $g['state'] || ! $this->shipping_v2_selected_method( $method ) ) {
				return $available;
			}
			$trace    = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 8 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Bounded argument-free callback provenance is an admission check, never a debug log or response.
			$shipping = $this->shipping_v2_window_trace( $trace, __FUNCTION__, true, $method );
			if ( false === $shipping || 'before' !== $g['free_window'] || $method !== $g['free_method'] || ! is_bool( $available ) || ! is_array( $package ) || ! array_key_exists( 'package_id', $package ) || $this->shipping_package_id( $package, null ) !== $g['package_id'] ) {
				$g['state'] = 'invalid';
				return $available;
			}
			$g['free_window'] = 'available';
			return $available;
		}
		/**
		 * Closes the selected method's availability subwindow.
		 *
		 * @param mixed $package Selected shipping package.
		 * @param mixed $method Selected native shipping method.
		 *
		 * @return mixed
		 */
		public function shipping_v2_after_method( $package, $method ) {
			$g =& self::$shipping_generation;
			if ( ! isset( $g['version'] ) || 2 !== $g['version'] || 'open' !== $g['state'] || ! $this->shipping_v2_selected_method( $method ) ) {
				return;
			}
			$trace    = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT, 8 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Bounded argument-free callback provenance is an admission check, never a debug log or response.
			$shipping = $this->shipping_v2_window_trace( $trace, __FUNCTION__, false, $method );
			if ( false === $shipping || ! in_array( $g['free_window'], array( 'before', 'available' ), true ) || $method !== $g['free_method'] || isset( $g['captures']['shipping_options_free_by_product'] ) || ! is_array( $package ) || ! array_key_exists( 'package_id', $package ) || $this->shipping_package_id( $package, null ) !== $g['package_id'] ) {
				$g['state'] = 'invalid';
				return;
			}
			$g['free_window'] = 'closed';
			unset( $g['free_method'] );
		}
		/**
		 * Returns a closed v2 observation after current admission checks.
		 *
		 * @return mixed
		 */
		private function get_shipping_v2_observation() {
			$g = self::$shipping_generation;
			if ( ! in_array( $g['state'], array( 'armed', 'closed' ), true ) || ! $this->shipping_identity_valid() || ! self::shipping_transport_headers_valid( false ) || ( isset( $g['free_window'] ) && ! in_array( $g['free_window'], array( null, 'closed' ), true ) ) ) {
				return $this->shipping_error( 'booster_shipping_observation_unavailable' );
			}
			if ( false === self::shipping_v2_metadata() ) {
				return $this->shipping_error( 'booster_shipping_observation_unavailable' );
			}
			$rows = array();
			foreach ( self::shipping_v2_owners() as $owner => $class ) {
				if ( isset( $g['rows'][ $owner ] ) ) {
					if ( array( 1, 1 ) !== $g['counts'][ $owner ] || ! $this->shipping_v2_registered( $owner, $g['modules'][ $owner ] ) || ! $this->shipping_v2_position_valid( $owner, $g['modules'][ $owner ] ) ) {
							return $this->shipping_error( 'booster_shipping_observation_unavailable' );
					}
					$rows[] = $g['rows'][ $owner ];
				} else {
					if ( isset( $g['counts'][ $owner ] ) ) {
						return $this->shipping_error( 'booster_shipping_observation_unavailable' );
					}
					$registered = $this->shipping_v2_registered( $owner, $g['modules'][ $owner ] );
					$reason     = ! $registered ? 'callback_not_registered' : ( $this->shipping_v2_position_valid( $owner, $g['modules'][ $owner ] ) ? 'not_observed' : 'unsupported_callback_position' );
					$rows[]     = $this->shipping_v2_row( $owner, 'own_actor_observation', $reason );
				}
			}
			return $this->shipping_v2_response( $rows, 'own_actor_observation' );
		}


		/**
		 * Checks and returns a callback bound while recording unsupported values.
		 *
		 * @param mixed $module Callback owner instance.
		 * @param mixed $value Value supplied to this method.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_bound( $module, $value ) {
			$g =& self::$shipping_generation;
			if ( ! isset( $g['version'] ) || 2 !== $g['version'] || 'pass' !== $g['state'] ) {
				return $value;
			}
			if ( ! is_array( $value ) || count( $value ) > 32 ) {
				foreach ( $g['captures'] as $owner => &$c ) {
					if ( $g['modules'][ $owner ] === $module && isset( $c['current'] ) ) {
						$c['unsupported'] = true;
					}
				}
				unset( $c );
			}
			return $value;
		}
		/**
		 * Checks and returns free-shipping values for the open capture.
		 *
		 * @param mixed $ticket Request-local observation ticket.
		 * @param mixed $value Value supplied to this method.
		 *
		 * @return mixed
		 */
		public static function shipping_v2_free_values( $ticket, $value ) {
			$g =& self::$shipping_generation;
			if ( null !== $ticket && isset( $g['captures'][ $ticket ] ) && ! empty( $value ) && ! self::$shipping_observer->shipping_v2_values_valid( $value ) ) {
				$g['captures'][ $ticket ]['unsupported'] = true;
			}
		}
	}

endif;

// First recognition is final even when no diagnostic keys were present.
WCJ_Rule_Explanations::initialize_payment_transport();

// Included before both actual module-loading paths fire wcj_loaded.
add_action( 'wcj_loaded', array( 'WCJ_Rule_Explanations', 'bind_payment_observer' ) );

WCJ_Rule_Explanations::initialize_shipping_transport();
add_action( 'wcj_loaded', array( 'WCJ_Rule_Explanations', 'bind_shipping_observer' ) );
