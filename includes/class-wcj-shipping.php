<?php
/**
 * Booster for WooCommerce - Module - Custom Shipping
 *
 * @version 4.7.1
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping' ) ) :

	class WCJ_Shipping extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 4.5.0
		 * @todo    (maybe) deprecated "Custom Shipping (Legacy - without Shipping Zones)" - hide when disabled
		 */
		function __construct() {
			$this->id         = 'shipping';
			$this->short_desc = __( 'Custom Shipping', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add multiple custom shipping methods to WooCommerce.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-custom-shipping';
			parent::__construct();

			if ( $this->is_enabled() ) {

				// Custom Shipping (Legacy - without Shipping Zones)
				add_action( 'woocommerce_shipping_init', array( $this, 'init_template_class' ) );
				add_filter( 'woocommerce_shipping_methods', array( $this, 'add_wc_shipping_wcj_custom_class' ) );

				// Custom Shipping
				if ( 'yes' === wcj_get_option( 'wcj_shipping_custom_shipping_w_zones_enabled', 'no' ) ) {
					add_action( 'woocommerce_shipping_init', array( $this, 'init_shipping_zones_class' ) );
					add_filter( 'woocommerce_shipping_methods', array( $this, 'add_wc_shipping_wcj_custom_w_zones_class' ) );
				}
			}
		}

		/**
		 * @version 4.5.0
		 * @since   4.5.0
		 */
		function init_template_class(){
			require_once( 'shipping/class-wc-shipping-wcj-custom.php' );
		}

		/**
		 * @version 4.5.0
		 * @since   4.5.0
		 */
		function init_shipping_zones_class(){
			require_once( 'shipping/class-wc-shipping-wcj-custom-with-shipping-zones.php' );
		}

		/*
		 * add_wc_shipping_wcj_custom_class.
		 *
		 * @version 4.7.0
		 */
		function add_wc_shipping_wcj_custom_class( $methods ) {
			$total_number = wcj_get_option( 'wcj_shipping_custom_shipping_total_number', 1 );
			if ( ! class_exists( 'WC_Shipping_WCJ_Custom_Template' ) ) {
				$this->init_template_class();
			}
			for ( $i = 1; $i <= $total_number; $i ++ ) {
				$the_method = new WC_Shipping_WCJ_Custom_Template();
				$the_method->init( $i );
				$methods[ $the_method->id ] = $the_method;
			}
			return $methods;
		}

		/*
		 * add_wc_shipping_wcj_custom_w_zones_class.
		 *
		 * @version 4.7.1
		 * @since   2.5.6
		 */
		function add_wc_shipping_wcj_custom_w_zones_class( $methods ) {
			if ( ! class_exists( 'WC_Shipping_WCJ_Custom_W_Zones' ) ) {
				$this->init_shipping_zones_class();
			}
			$methods['booster_custom_shipping_w_zones'] = 'WC_Shipping_WCJ_Custom_W_Zones';
			return $methods;
		}

	}

endif;

return new WCJ_Shipping();
