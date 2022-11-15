<?php
/**
 * Booster for WooCommerce - Module - Custom Shipping
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Shipping' ) ) :
		/**
		 * WCJ_Shipping.
		 *
		 * @version 4.5.0
		 * @todo    (maybe) deprecated "Custom Shipping (Legacy - without Shipping Zones)" - hide when disabled
		 */
	class WCJ_Shipping extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 4.5.0
		 * @todo    (maybe) deprecated "Custom Shipping (Legacy - without Shipping Zones)" - hide when disabled
		 */
		public function __construct() {
			$this->id         = 'shipping';
			$this->short_desc = __( 'Custom Shipping', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add multiple custom shipping methods to WooCommerce.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-custom-shipping';
			parent::__construct();

			if ( $this->is_enabled() ) {

				// Custom Shipping (Legacy - without Shipping Zones).
				add_action( 'woocommerce_shipping_init', array( $this, 'init_template_class' ) );
				add_filter( 'woocommerce_shipping_methods', array( $this, 'add_wc_shipping_wcj_custom_class' ) );

				// Custom Shipping.
				if ( 'yes' === wcj_get_option( 'wcj_shipping_custom_shipping_w_zones_enabled', 'no' ) ) {
					add_action( 'woocommerce_shipping_init', array( $this, 'init_shipping_zones_class' ) );
					add_filter( 'woocommerce_shipping_methods', array( $this, 'add_wc_shipping_wcj_custom_w_zones_class' ) );
				}
			}
		}

		/**
		 * Init_template_class
		 *
		 * @version 4.5.0
		 * @since   4.5.0
		 */
		public function init_template_class() {
			require_once 'shipping/class-wc-shipping-wcj-custom-template.php';
		}

		/**
		 * Init_shipping_zones_class
		 *
		 * @version 5.6.2
		 * @since   4.5.0
		 */
		public function init_shipping_zones_class() {
			require_once 'shipping/class-wc-shipping-wcj-custom-with-shipping-zones.php';
		}

		/**
		 * Add_wc_shipping_wcj_custom_class.
		 *
		 * @version 4.7.0
		 * @param String $methods Get methods.
		 */
		public function add_wc_shipping_wcj_custom_class( $methods ) {
			if ( ! class_exists( 'WC_Shipping_WCJ_Custom_Template' ) ) {
				$this->init_template_class();
			}
			$total_number = wcj_get_option( 'wcj_shipping_custom_shipping_total_number', 1 );
			for ( $i = 1; $i <= $total_number; $i ++ ) {
				$the_method = new WC_Shipping_WCJ_Custom_Template();
				$the_method->init( $i );
				$methods[ $the_method->id ] = $the_method;
			}
			return $methods;
		}

		/**
		 * Add_wc_shipping_wcj_custom_w_zones_class.
		 *
		 * @version 5.6.8
		 * @since   2.5.6
		 * @param String $methods Get methods.
		 */
		public function add_wc_shipping_wcj_custom_w_zones_class( $methods ) {
			if ( ! class_exists( 'WC_Shipping_WCJ_Custom_With_Shipping_Zones' ) ) {
				$this->init_shipping_zones_class();
			}
			$methods['booster_custom_shipping_w_zones'] = 'WC_Shipping_WCJ_Custom_With_Shipping_Zones';
			return $methods;
		}

	}

endif;

return new WCJ_Shipping();
