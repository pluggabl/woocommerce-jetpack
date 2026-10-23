<?php
/**
 * Booster for WooCommerce - Module - Custom Gateways
 *
 * @version 7.2.5
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Payment_Gateways' ) ) :
	/**
	 * WCJ_Payment_Gateways.
	 */
	class WCJ_Payment_Gateways extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 7.1.4
		 */
		public function __construct() {

			$this->id         = 'payment_gateways';
			$this->short_desc = 'Custom Gateways';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Custom Gateways', 'woocommerce-jetpack' );
				}
			);
			$this->desc = 'Add multiple custom payment gateways to WooCommerce (1 custom gateway allowed in free version).';
			wcj_init_presentation(
				function () {
					$this->desc = __( 'Add multiple custom payment gateways to WooCommerce (1 custom gateway allowed in free version).', 'woocommerce-jetpack' );
				}
			);
			$this->desc_pro = 'Add multiple custom payment gateways to WooCommerce.';
			wcj_init_presentation(
				function () {
					$this->desc_pro = __( 'Add multiple custom payment gateways to WooCommerce.', 'woocommerce-jetpack' );
				}
			);
			$this->link_slug = 'woocommerce-custom-payment-gateways';
			parent::__construct();

			if ( $this->is_enabled() ) {
				include_once 'gateways/class-wc-gateway-wcj-custom-template.php';
				add_action( 'woocommerce_after_checkout_validation', array( $this, 'check_required_wcj_input_fields' ), PHP_INT_MAX, 2 );

				if ( true === wcj_is_hpos_enabled() ) {
					add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_custom_payment_gateways_fields_order_meta_hpos' ), PHP_INT_MAX, 2 );
					add_action( 'add_meta_boxes', array( $this, 'add_custom_payment_gateways_fields_admin_order_meta_box_hpos' ) );
				} else {
					add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_custom_payment_gateways_fields_order_meta' ), PHP_INT_MAX, 2 );
					add_action( 'add_meta_boxes', array( $this, 'add_custom_payment_gateways_fields_admin_order_meta_box' ) );
				}
				add_action( 'admin_init', array( $this, 'maybe_delete_payment_gateway_input_fields' ) );
				add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_custom_gateway_blocks' ) );
				add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'update_custom_gateway_blocks_fields' ), 10, 2 );
				add_action( 'woocommerce_before_settings_checkout', array( $this, 'custom_gateway_blocks_admin_notice' ) );
			}
		}


		/**
		 * Register candidate names without constructing Woo's gateway singleton.
		 *
		 * @param object $registry Public Blocks payment method registry.
		 */
		public function register_custom_gateway_blocks( $registry ) {
			if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				return;
			}
			require_once __DIR__ . '/gateways/class-wcj-custom-payment-gateway-input-schema.php';
			require_once __DIR__ . '/gateways/class-wcj-custom-payment-gateway-blocks.php';
			$the_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_custom_payment_gateways_number', 1 ) );
			for ( $i = 1; $i <= $the_number; $i++ ) {
				$id = ( 1 === $i ) ? 'jetpack_custom_gateway' : 'jetpack_custom_gateway_' . $i;
				if ( ! $registry->is_registered( $id ) ) {
					$registry->register( new WCJ_Custom_Payment_Gateway_Blocks( $id, $this ) );
				}
			}
		}

		/**
		 * Apply the existing module/role policy explicitly, including on REST.
		 *
		 * @return bool
		 */
		public function custom_gateway_blocks_is_allowed() {
			global $wcj_modules_by_user_roles_data;
			$roles_enabled = wcj_is_module_enabled( 'modules_by_user_roles' );
			if ( $roles_enabled ) {
				$user = wp_get_current_user();
				$role = ( isset( $user->roles ) && is_array( $user->roles ) && ! empty( $user->roles ) ? reset( $user->roles ) : 'guest' );
				$role = ( '' !== $role ? $role : 'guest' );
				if ( isset( $wcj_modules_by_user_roles_data ) ) {
					if ( ! is_array( $wcj_modules_by_user_roles_data ) || ! isset( $wcj_modules_by_user_roles_data['role'], $wcj_modules_by_user_roles_data['modules_incl'], $wcj_modules_by_user_roles_data['modules_excl'] ) || $role !== $wcj_modules_by_user_roles_data['role'] ) {
						return false;
					}
					foreach ( array( 'modules_incl', 'modules_excl' ) as $key ) {
						if ( '' !== $wcj_modules_by_user_roles_data[ $key ] && ! is_array( $wcj_modules_by_user_roles_data[ $key ] ) ) {
							return false;
						}
					}
				}
			}
			return $this->is_enabled() && ( ! $roles_enabled || wcj_check_modules_by_user_roles( 'payment_gateways' ) );
		}

		/**
		 * Validate the native profile on Woo's own final checkout POST/order only.
		 *
		 * No client order lookup, own save, processor call or PaymentResult mutation.
		 *
		 * @param WC_Order        $order Woo-owned order supplied by the hook.
		 * @param WP_REST_Request $request Current Store API checkout request.
		 */
		public function update_custom_gateway_blocks_fields( $order, $request ) {
			if ( ! $order instanceof WC_Order || ! $request instanceof WP_REST_Request || 'POST' !== $request->get_method() || ! preg_match( '#^/wc/store(?:/v1)?/checkout$#i', $request->get_route() ) ) {
				return;
			}
			$id = wc_clean( wp_unslash( $request['payment_method'] ?? '' ) );
			if ( ! is_string( $id ) ) {
				$this->reject_custom_gateway_blocks_fields( $order );
			}
			if ( '' === $id ) {
				$order->delete_meta_data( '_wcj_custom_payment_gateway_input_fields' );
				return;
			}
			$gateways = WC()->payment_gateways()->get_available_payment_gateways();
			$gateway  = isset( $gateways[ $id ] ) ? $gateways[ $id ] : null;
			if ( ! $gateway instanceof WC_Gateway_WCJ_Custom_Template ) {
				if ( preg_match( '/^jetpack_custom_gateway(?:_\d+)?$/D', $id ) ) {
					$this->reject_custom_gateway_blocks_fields( $order );
				}
				$order->delete_meta_data( '_wcj_custom_payment_gateway_input_fields' );
				return;
			}
			if ( $id !== $order->get_payment_method() || $id !== $gateway->id || 'yes' !== $gateway->get_option( 'enabled', 'no' ) || ! $this->custom_gateway_blocks_is_allowed() ) {
				$this->reject_custom_gateway_blocks_fields( $order );
			}
			// POST must work even when the separate Blocks subclass was never loaded.
			require_once __DIR__ . '/gateways/class-wcj-custom-payment-gateway-input-schema.php';
			$schema = WCJ_Custom_Payment_Gateway_Input_Schema::build( $id, $gateway->get_option( 'description' ) );
			if ( isset( $schema['unsupported'] ) ) {
				$this->reject_custom_gateway_blocks_fields( $order );
			}
			$validated = WCJ_Custom_Payment_Gateway_Input_Schema::validate( $id, $schema, $request['payment_data'] );
			if ( isset( $validated['error'] ) ) {
				$message = '';
				if ( 'stale' === $validated['error'] ) {
					$message = __( 'Checkout has been updated. Please refresh and try again.', 'woocommerce-jetpack' );
				} elseif ( 'required' === $validated['error'] ) {
					/* translators: %s: configured payment field label. */
					$message = sprintf( __( '%s is a required payment field.', 'woocommerce-jetpack' ), esc_html( $validated['label'] ) );
				}
				$this->reject_custom_gateway_blocks_fields( $order, $message );
			}
			if ( empty( $schema['fields'] ) ) {
				$order->delete_meta_data( '_wcj_custom_payment_gateway_input_fields' );
				return;
			}
			$order->update_meta_data( '_wcj_custom_payment_gateway_input_fields', $validated['values'] );
		}

		/**
		 * A rejected final POST must not retain a previous draft's payment input.
		 *
		 * @param WC_Order $order Woo-owned current checkout order.
		 * @param string   $message Fixed safe validation message.
		 * @throws \Automattic\WooCommerce\StoreApi\Exceptions\RouteException Invalid profile/input.
		 */
		private function reject_custom_gateway_blocks_fields( $order, $message = '' ) {
			$order->delete_meta_data( '_wcj_custom_payment_gateway_input_fields' );
			if ( '' === $message ) {
				$message = __( 'This custom payment method is not available for this block-based Checkout. Please choose another payment method.', 'woocommerce-jetpack' );
			}
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'wcj_custom_gateway_fields_invalid', esc_html( $message ), 400 );
		}

		/** Show unsupported-profile notices only to authorized Payments administrators. */
		public function custom_gateway_blocks_admin_notice() {
			global $current_tab;
			if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}
			$screen = get_current_screen();
			if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id || 'checkout' !== $current_tab ) {
				return;
			}
			require_once __DIR__ . '/gateways/class-wcj-custom-payment-gateway-input-schema.php';
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( ! $gateway instanceof WC_Gateway_WCJ_Custom_Template || 'yes' !== $gateway->get_option( 'enabled', 'no' ) ) {
					continue;
				}
				$schema = WCJ_Custom_Payment_Gateway_Input_Schema::build( $gateway->id, $gateway->get_option( 'description' ) );
				if ( ! isset( $schema['unsupported'] ) ) {
					continue;
				}
				$reason = ( 'hooks' === $schema['reason'] ? __( 'Custom shortcode or description hooks are unsupported by this native profile.', 'woocommerce-jetpack' ) : __( 'The complete configured description and inputs cannot be represented by the static text-input profile.', 'woocommerce-jetpack' ) );
				/* translators: 1: authorized gateway admin title, 2: fixed unsupported reason. */
				$message = sprintf( __( '%1$s is not offered in the block-based Checkout: %2$s Classic checkout is unaffected.', 'woocommerce-jetpack' ), $gateway->method_title, $reason );
				echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
			}
		}

		/**
		 * Maybe_delete_payment_gateway_input_fields.
		 *
		 * @version 7.1.4
		 * @since   3.3.0
		 */
		public function maybe_delete_payment_gateway_input_fields() {
			$wpnonce = isset( $_GET['_wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wcj_delete_payment_gateway_input_fields' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_delete_payment_gateway_input_fields'] ) ) {
				$order_id = sanitize_text_field( wp_unslash( $_GET['wcj_delete_payment_gateway_input_fields'] ) );
				if ( true === wcj_is_hpos_enabled() ) {
					$order = wcj_get_order( $order_id );
					if ( $order && false !== $order ) {
						$order->delete_meta_data( '_wcj_custom_payment_gateway_input_fields' );
						$order->save();
					}
				} else {
					delete_post_meta( $order_id, '_wcj_custom_payment_gateway_input_fields' );
				}

				wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'wcj_delete_payment_gateway_input_fields', '_wpnonce' ) ) ) );
				exit;
			}
		}

		/**
		 * Check_required_wcj_input_fields.
		 *
		 * @version 5.6.7
		 * @since   3.0.1
		 * @param array          $data defines the data.
		 * @param string | array $errors defines the errors.
		 */
		public function check_required_wcj_input_fields( $data, $errors ) {
			$payment_method = $data['payment_method'];
			if ( 'jetpack_custom_gateway' === substr( $payment_method, 0, 22 ) ) {
				$wpnonce = isset( $_REQUEST['woocommerce-process-checkout-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['woocommerce-process-checkout-nonce'] ), 'woocommerce-process_checkout' ) : false;
				foreach ( $_POST as $key => $value ) {
					if ( 'wcj_input_field_' === substr( $key, 0, 16 ) ) {
						if ( $wpnonce && isset( $_POST[ 'for_' . $key ] ) && $payment_method === $_POST[ 'for_' . $key ] ) {
							$is_required_set = ( isset( $_POST[ $key . '_required' ] ) && 'yes' === $_POST[ $key . '_required' ] );
							if ( $is_required_set && '' === $value ) {
								$label = ( isset( $_POST[ 'label_for_' . $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'label_for_' . $key ] ) ) : substr( $key, 16 ) );
								/* translators: %s: translation added */
								$errors->add( 'booster', sprintf( __( '<strong>%s</strong> is a required field.', 'woocommerce-jetpack' ), $label ) );
							}
						}
					}
				}
			}
		}

		/**
		 * Add_custom_payment_gateways_fields_admin_order_meta_box.
		 *
		 * @version 2.5.2
		 * @since   2.5.2
		 */
		public function add_custom_payment_gateways_fields_admin_order_meta_box() {
			$order_id     = get_the_ID();
			$input_fields = get_post_meta( $order_id, '_wcj_custom_payment_gateway_input_fields', true );
			if ( ! empty( $input_fields ) ) {
				$payment_method_title = get_post_meta( $order_id, '_payment_method_title', true );
				$screen               = 'shop_order';
				$context              = 'side';
				$priority             = 'high';
				add_meta_box(
					'wc-jetpack-' . $this->id,
					/* translators: %s: translation added */
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . sprintf( __( '%s Fields', 'woocommerce-jetpack' ), $payment_method_title ),
					array( $this, 'create_custom_payment_gateways_fields_admin_order_meta_box' ),
					$screen,
					$context,
					$priority
				);
			}
		}

		/**
		 * Add_custom_payment_gateways_fields_admin_order_meta_box_hpos.
		 *
		 * @version 7.1.4
		 * @since  1.0.0
		 */
		public function add_custom_payment_gateways_fields_admin_order_meta_box_hpos() {

			$order_id = ( isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : '' );//phpcs:ignore WordPress.Security.NonceVerification

			$order = wcj_get_order( $order_id );
			if ( $order && false !== $order ) {
				$input_fields = $order->get_meta( '_wcj_custom_payment_gateway_input_fields' );
			}
			if ( ! empty( $input_fields ) ) {
				$payment_method_title = $order->get_meta( '_payment_method_title' );
				$screen               = 'woocommerce_page_wc-orders';
				$context              = 'side';
				$priority             = 'high';
				add_meta_box(
					'wc-jetpack-' . $this->id,
					/* translators: %s: translation added */
					__( 'Booster', 'woocommerce-jetpack' ) . ': ' . sprintf( __( '%s Fields', 'woocommerce-jetpack' ), $payment_method_title ),
					array( $this, 'create_custom_payment_gateways_fields_admin_order_meta_box' ),
					$screen,
					$context,
					$priority
				);
			}
		}

		/**
		 * Create_custom_payment_gateways_fields_admin_order_meta_box.
		 *
		 * @version 7.2.5
		 * @since   2.5.2
		 */
		public function create_custom_payment_gateways_fields_admin_order_meta_box() {
			if ( true === wcj_is_hpos_enabled() ) {
				$order_id = ( isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : '' );//phpcs:ignore WordPress.Security.NonceVerification
				$order    = wcj_get_order( $order_id );
				if ( $order && false !== $order ) {
					$input_fields = $order->get_meta( '_wcj_custom_payment_gateway_input_fields' );
				}
			} else {
				$order_id     = get_the_ID();
				$input_fields = get_post_meta( $order_id, '_wcj_custom_payment_gateway_input_fields', true );
			}
			$html       = '';
			$table_data = array();
			foreach ( $input_fields as $name => $value ) {
				$table_data[] = array( $name, $value );
			}
			$html .= wcj_get_table_html(
				$table_data,
				array(
					'table_class'        => 'widefat striped',
					'table_heading_type' => 'vertical',
				)
			);
			if ( 'yes' === wcj_get_option( 'wcj_custom_payment_gateways_input_fields_delete_button', 'no' ) ) {
				$html .= '<p><a style="color:#a00;" href="' . esc_url( wp_nonce_url( add_query_arg( 'wcj_delete_payment_gateway_input_fields', $order_id ), 'wcj_delete_payment_gateway_input_fields' ) ) . '"' . wcj_get_js_confirmation() . '>' .
				__( 'Delete', 'woocommerce-jetpack' ) . '</a></p>';
			}
			$allowed_tags                 = wp_kses_allowed_html( 'post' );
			$allowed_tags['a']['onclick'] = true;
			echo wp_kses( $html, $allowed_tags );
		}

		/**
		 * Update_custom_payment_gateways_fields_order_meta.
		 *
		 * @version 5.6.7
		 * @since   2.5.2
		 * @param int    $order_id defines the order_id.
		 * @param string $posted defines the posted.
		 */
		public function update_custom_payment_gateways_fields_order_meta( $order_id, $posted ) {
			$payment_method = get_post_meta( $order_id, '_payment_method', true );
			if ( 'jetpack_custom_gateway' === substr( $payment_method, 0, 22 ) ) {
				$input_fields = array();
				$wpnonce      = isset( $_REQUEST['woocommerce-process-checkout-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['woocommerce-process-checkout-nonce'] ), 'woocommerce-process_checkout' ) : false;
				foreach ( $_POST as $key => $value ) {
					if ( 'wcj_input_field_' === substr( $key, 0, 16 ) ) {
						if ( ! is_array( $value ) ) {
							if ( $wpnonce && isset( $_POST[ 'for_' . $key ] ) && $payment_method === $_POST[ 'for_' . $key ] ) {
								if ( isset( $_POST[ 'label_for_' . $key ] ) ) {
									$input_fields[ sanitize_text_field( wp_unslash( $_POST[ 'label_for_' . $key ] ) ) ] = $value;
								} else {
									$input_fields[ substr( $key, 16 ) ] = $value;
								}
							}
						} else {
							if ( substr( $key, 16 ) === $payment_method ) {
								foreach ( $value as $input_name => $input_value ) {
									$label_value = isset( $input_value['label'] ) ? $input_value['label'] : '';
									if ( ! empty( $label_value ) ) {
										$input_fields[ $label_value ] = $input_value['value'];
									} else {
										$input_fields[ $input_name ] = $input_value['value'];
									}
								}
							}
						}
					}
				}
				if ( ! empty( $input_fields ) ) {
					update_post_meta( $order_id, '_wcj_custom_payment_gateway_input_fields', $input_fields );
				}
			}
		}


		/**
		 * Update_custom_payment_gateways_fields_order_meta_hpos.
		 *
		 * @version 7.1.4
		 * @since  1.0.0
		 * @param int    $order_id defines the order_id.
		 * @param string $posted defines the posted.
		 */
		public function update_custom_payment_gateways_fields_order_meta_hpos( $order_id, $posted ) {
			$order = wcj_get_order( $order_id );
			if ( $order && false !== $order ) {
				$payment_method = $order->get_meta( '_payment_method' );
				if ( 'jetpack_custom_gateway' === substr( $payment_method, 0, 22 ) ) {
					$input_fields = array();
					$wpnonce      = isset( $_REQUEST['woocommerce-process-checkout-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['woocommerce-process-checkout-nonce'] ), 'woocommerce-process_checkout' ) : false;
					foreach ( $_POST as $key => $value ) {
						if ( 'wcj_input_field_' === substr( $key, 0, 16 ) ) {
							if ( ! is_array( $value ) ) {
								if ( $wpnonce && isset( $_POST[ 'for_' . $key ] ) && $payment_method === $_POST[ 'for_' . $key ] ) {
									if ( isset( $_POST[ 'label_for_' . $key ] ) ) {
										$input_fields[ sanitize_text_field( wp_unslash( $_POST[ 'label_for_' . $key ] ) ) ] = $value;
									} else {
										$input_fields[ substr( $key, 16 ) ] = $value;
									}
								}
							} else {
								if ( substr( $key, 16 ) === $payment_method ) {
									foreach ( $value as $input_name => $input_value ) {
										$label_value = isset( $input_value['label'] ) ? $input_value['label'] : '';
										if ( ! empty( $label_value ) ) {
											$input_fields[ $label_value ] = $input_value['value'];
										} else {
											$input_fields[ $input_name ] = $input_value['value'];
										}
									}
								}
							}
						}
					}
					if ( ! empty( $input_fields ) ) {
						$order->update_meta_data( '_wcj_custom_payment_gateway_input_fields', $input_fields );
						$order->save();
					}
				}
			}
		}

	}

endif;

return new WCJ_Payment_Gateways();
