<?php
/**
 * WooCommerce Jetpack Shipping
 *
 * The WooCommerce Jetpack Shipping class.
 *
 * @version 2.4.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping' ) ) :

class WCJ_Shipping extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.4
	 */
	function __construct() {

		$this->id         = 'shipping';
		$this->short_desc = __( 'Shipping', 'woocommerce-jetpack' );
		$this->desc       = __( 'Hide WooCommerce shipping when free is available.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
//			include_once( 'shipping/class-wc-shipping-wcj-custom.php' );
//			add_filter( 'woocommerce_available_shipping_methods', array( $this, 'hide_all_shipping_when_free_is_available' ), 10, 1 );
			add_filter( 'woocommerce_package_rates',     array( $this, 'hide_shipping_when_free_is_available' ), 10, 2 );
			add_filter( 'woocommerce_shipping_settings', array( $this, 'add_hide_shipping_if_free_available_fields' ), 100 );
		}
	}

	/**
	 * hide_shipping_when_free_is_available.
	 *
	 * @version 2.4.4
	 */
	function hide_shipping_when_free_is_available( $rates, $package ) {
		// Only modify rates if free_shipping is present
		if ( isset( $rates['free_shipping'] ) ) {
			// To unset a single rate/method, do the following. This example unsets flat_rate shipping
			if ( 'yes' === get_option( 'wcj_shipping_hide_if_free_available_local_delivery' ) ) {
				unset( $rates['local_delivery'] );
			}
			if ( 'yes' === get_option( 'wcj_shipping_hide_if_free_available_all' ) ) {
				// To unset all methods except for free_shipping, do the following
				$free_shipping          = $rates['free_shipping'];
				$rates                  = array();
				$rates['free_shipping'] = $free_shipping;
			}
		}
		return $rates;
	}

	/**
	* Hide ALL Shipping option when free shipping is available
	*
	* @param array $available_methods
	*/
	/* function hide_all_shipping_when_free_is_available( $available_methods ) {
		if( isset( $available_methods['free_shipping'] ) ) {
			// Get Free Shipping array into a new array
			$freeshipping = array();
			$freeshipping = $available_methods['free_shipping'];
			// Empty the $available_methods array
			unset( $available_methods );
			// Add Free Shipping back into $avaialble_methods
			$available_methods = array();
			$available_methods[] = $freeshipping;
		}
		return $available_methods;
	} */

	/**
	 * add_hide_shipping_if_free_available_fields.
	 *
	 * @version 2.4.4
	 */
	function add_hide_shipping_if_free_available_fields( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			if ( isset( $section['id'] ) && 'woocommerce_shipping_method_format' == $section['id'] ) {
				$updated_settings[] = array(
					'title'    => __( 'Booster: Hide shipping', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
					'desc_tip' => __( '', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
					'default'  => 'no',
					'type'     => 'checkbox',
					'checkboxgroup' => 'start',
				);
				$updated_settings[] = array(
					'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
					'desc_tip' => __( '', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_hide_if_free_available_all',
					'default'  => 'no',
					'type'     => 'checkbox',
					'checkboxgroup' => 'end',
				);
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.4
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Hide if free is available', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you hide other shipping options when free shipping is available on shop frontend.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_options',
			),
			array(
				'title'    => __( 'Hide shipping', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
				'desc_tip' => __( '', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
				'desc_tip' => __( '', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_all',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'end',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_hide_if_free_available_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Shipping();
