<?php
/**
 * WooCommerce Jetpack Payment Gateways
 *
 * The WooCommerce Jetpack Payment Gateways class.
 *
 * @version 2.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways' ) ) :

class WCJ_Payment_Gateways extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.0
	 */
	public function __construct() {

		$this->id         = 'payment_gateways';
		$this->short_desc = __( 'Custom Gateways', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add multiple custom payment gateways to WooCommerce.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Include custom payment gateway
			include_once( 'gateways/class-wc-gateway-wcj-custom.php' );
		}
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$settings = array(

			array( 'title' => __( 'Custom Payment Gateways Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_custom_payment_gateways_options' ),

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

			array( 'type'  => 'sectionend', 'id' => 'wcj_custom_payment_gateways_options' ),
		);

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Payment_Gateways();
