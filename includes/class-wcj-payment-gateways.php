<?php
/**
 * WooCommerce Jetpack Payment Gateways
 *
 * The WooCommerce Jetpack Payment Gateways class.
 *
 * @version 2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways' ) ) :

class WCJ_Payment_Gateways extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.2
	 */
	public function __construct() {

		$this->id         = 'payment_gateways';
		$this->short_desc = __( 'Custom Gateways', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add multiple custom payment gateways to WooCommerce.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-custom-payment-gateways/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Include custom payment gateway
			include_once( 'gateways/class-wc-gateway-wcj-custom.php' );

			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_custom_payment_gateways_fields_order_meta' ), PHP_INT_MAX, 2 );
			add_action( 'add_meta_boxes', array( $this, 'add_custom_payment_gateways_fields_admin_order_meta_box' ) );
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
	 * @version 2.5.2
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

	/**
	 * get_settings.
	 *
	 * @version 2.4.8
	 */
	function get_settings() {
		$wocommerce_checkout_settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout' );
		$wocommerce_checkout_settings_url = '<a href="' . $wocommerce_checkout_settings_url . '">' . __( 'WooCommerce > Settings > Checkout', 'woocommerce-jetpack' ) . '</a>';
		$settings = array(
			array(
				'title'    => __( 'Custom Payment Gateways Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_custom_payment_gateways_options',
				'desc'     => __( 'This section lets you set number of custom payment gateways to add.', 'woocommerce-jetpack' )
					. ' ' . sprintf( __( 'After setting the number, visit %s to set each gateway options.', 'woocommerce-jetpack' ), $wocommerce_checkout_settings_url ),
			),
			array(
				'title'    => __( 'Number of Gateways', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Number of custom payments gateways to be added. All settings for each new gateway are in WooCommerce > Settings > Checkout.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_payment_gateways_number',
				'default'  => 1,
				'type'     => 'number',
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min' => '1', 'max' => '10', )
				),
			),
		);
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_custom_payment_gateways_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings [] = array(
				'title'    => __( 'Admin Title Custom Gateway', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'       => 'wcj_custom_payment_gateways_admin_title_' . $i,
				'default'  => __( 'Custom Gateway', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'     => 'text',
			);
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_custom_payment_gateways_options',
			),
		) );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Payment_Gateways();
