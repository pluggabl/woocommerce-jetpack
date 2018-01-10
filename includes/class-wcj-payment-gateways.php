<?php
/**
 * Booster for WooCommerce - Module - Custom Gateways
 *
 * @version 3.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways' ) ) :

class WCJ_Payment_Gateways extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.3.0
	 */
	function __construct() {

		$this->id         = 'payment_gateways';
		$this->short_desc = __( 'Custom Gateways', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add multiple custom payment gateways to WooCommerce.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-custom-payment-gateways';
		parent::__construct();

		if ( $this->is_enabled() ) {
			include_once( 'gateways/class-wc-gateway-wcj-custom.php' );
			add_action( 'woocommerce_after_checkout_validation',  array( $this, 'check_required_wcj_input_fields' ), PHP_INT_MAX, 2 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_custom_payment_gateways_fields_order_meta' ), PHP_INT_MAX, 2 );
			add_action( 'add_meta_boxes',                         array( $this, 'add_custom_payment_gateways_fields_admin_order_meta_box' ) );
			add_action( 'admin_init',                             array( $this, 'maybe_delete_payment_gateway_input_fields' ) );
		}
	}

	/**
	 * maybe_delete_payment_gateway_input_fields.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function maybe_delete_payment_gateway_input_fields() {
		if ( isset( $_GET['wcj_delete_payment_gateway_input_fields'] ) ) {
			$order_id = $_GET['wcj_delete_payment_gateway_input_fields'];
			delete_post_meta( $order_id, '_wcj_custom_payment_gateway_input_fields' );
			wp_safe_redirect( remove_query_arg( 'wcj_delete_payment_gateway_input_fields' ) );
			exit;
		}
	}

	/**
	 * check_required_wcj_input_fields.
	 *
	 * @version 3.0.1
	 * @since   3.0.1
	 */
	function check_required_wcj_input_fields( $data, $errors ) {
		$payment_method = $data['payment_method'];
		if ( 'jetpack_custom_gateway' === substr( $payment_method, 0, 22 ) ) {
			foreach ( $_POST as $key => $value ) {
				if ( 'wcj_input_field_' === substr( $key, 0, 16 ) ) {
					if ( isset( $_POST[ 'for_' . $key ] ) && $payment_method === $_POST[ 'for_' . $key ] ) {
						$is_required_set = ( isset( $_POST[ $key . '_required' ] ) && 'yes' === $_POST[ $key . '_required' ] );
						if ( $is_required_set && '' == $value ) {
							$label = ( isset( $_POST[ 'label_for_' . $key ] ) ? $_POST[ 'label_for_' . $key ] : substr( $key, 16 ) );
							$errors->add( 'booster', sprintf( __( '<strong>%s</strong> is a required field.', 'woocommerce-jetpack' ), $label ) );
						}
					}
				}
			}
		}
	}

	/**
	 * add_custom_payment_gateways_fields_admin_order_meta_box.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function add_custom_payment_gateways_fields_admin_order_meta_box() {
		$order_id = get_the_ID();
		$input_fields = get_post_meta( $order_id, '_wcj_custom_payment_gateway_input_fields', true );
		if ( ! empty( $input_fields ) ) {
			$payment_method_title = get_post_meta( $order_id, '_payment_method_title', true );
			$screen   = 'shop_order';
			$context  = 'side';
			$priority = 'high';
			add_meta_box(
				'wc-jetpack-' . $this->id,
				__( 'Booster', 'woocommerce-jetpack' ) . ': ' . sprintf( __( '%s Fields', 'woocommerce-jetpack' ), $payment_method_title ),
				array( $this, 'create_custom_payment_gateways_fields_admin_order_meta_box' ),
				$screen,
				$context,
				$priority
			);
		}
	}

	/**
	 * create_custom_payment_gateways_fields_admin_order_meta_box.
	 *
	 * @version 3.3.0
	 * @since   2.5.2
	 */
	function create_custom_payment_gateways_fields_admin_order_meta_box() {
		$order_id = get_the_ID();
		$html = '';
		$input_fields = get_post_meta( $order_id, '_wcj_custom_payment_gateway_input_fields', true );
		$table_data = array();
		foreach ( $input_fields as $name => $value ) {
			$table_data[] = array( $name, $value );
		}
		$html .= wcj_get_table_html( $table_data, array( 'table_class' => 'widefat striped', 'table_heading_type' => 'vertical', ) );
		if ( 'yes' === get_option( 'wcj_custom_payment_gateways_input_fields_delete_button', 'no' ) ) {
			$html .= '<p><a style="color:#a00;" href="' . add_query_arg( 'wcj_delete_payment_gateway_input_fields', $order_id ) . '"' . wcj_get_js_confirmation() . '>' .
				__( 'Delete', 'woocommerce-jetpack' ) . '</a></p>';
		}
		echo $html;
	}

	/**
	 * update_custom_payment_gateways_fields_order_meta.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function update_custom_payment_gateways_fields_order_meta( $order_id, $posted ) {
		$payment_method = get_post_meta( $order_id, '_payment_method', true );
		if ( 'jetpack_custom_gateway' === substr( $payment_method, 0, 22 ) ) {
			$input_fields = array();
			foreach ( $_POST as $key => $value ) {
				if ( 'wcj_input_field_' === substr( $key, 0, 16 ) ) {
					if ( isset( $_POST[ 'for_' . $key ] ) && $payment_method === $_POST[ 'for_' . $key ] ) {
						if ( isset( $_POST[ 'label_for_' . $key ] ) ) {
							$input_fields[ $_POST[ 'label_for_' . $key ] ] = $value;
						} else {
							$input_fields[ substr( $key, 16 ) ] = $value;
						}
					}
				}
			}
			if ( ! empty( $input_fields ) ) {
				update_post_meta( $order_id, '_wcj_custom_payment_gateway_input_fields', $input_fields );
			}
		}
	}

}

endif;

return new WCJ_Payment_Gateways();
