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
			$this->short_desc = __( 'Custom Gateways', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add multiple custom payment gateways to WooCommerce (1 custom gateway allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add multiple custom payment gateways to WooCommerce.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-custom-payment-gateways';
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
