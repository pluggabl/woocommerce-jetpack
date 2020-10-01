<?php
/**
 * Booster for WooCommerce - Module - Shipping Options
 *
 * @version 5.2.0
 * @since   2.9.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_Options' ) ) :

class WCJ_Shipping_Options extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   2.9.0
	 * @todo    (maybe) remove (or at least mark as deprecated) "Grant free shipping on per product basis" (offer to use "Shipping Methods by Products" module instead)
	 */
	function __construct() {

		$this->id         = 'shipping_options';
		$this->short_desc = __( 'Shipping Options', 'woocommerce-jetpack' );
		$this->desc       = __( 'Hide shipping when free is available.', 'woocommerce-jetpack' ) . ' ' .
		                    __( 'Grant free shipping on per product basis (In free version, all products in cart must grant free shipping).', 'woocommerce-jetpack' ) . ' ' .
		                    __( 'Show only the most expensive shipping (In free version, only free shipping is allowed to be ignored).', 'woocommerce-jetpack' );

		$this->desc_pro   = __( 'Hide shipping when free is available.', 'woocommerce-jetpack' ) . ' ' .
		                    __( 'Grant free shipping on per product basis.', 'woocommerce-jetpack' ) . ' ' .
		                    __( 'Show only the most expensive shipping.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-options';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Hide if free is available
			if ( 'yes' === wcj_get_option( 'wcj_shipping_hide_if_free_available_all', 'no' ) ) {
				add_filter( 'woocommerce_package_rates', array( $this, 'hide_shipping_when_free_is_available' ),
					wcj_get_woocommerce_package_rates_module_filter_priority( 'shipping_options_hide_free_shipping' ), 2 );
			}
			add_filter( 'woocommerce_shipping_settings', array( $this, 'add_hide_shipping_if_free_available_fields' ), PHP_INT_MAX );

			// Free shipping by product
			if ( 'yes' === wcj_get_option( 'wcj_shipping_free_shipping_by_product_enabled', 'no' ) ) {
				add_filter( 'woocommerce_shipping_free_shipping_is_available', array( $this, 'free_shipping_by_product' ), PHP_INT_MAX, 2 );
			}

			// Show the most expensive shipping
			add_action( 'woocommerce_package_rates', array( $this, 'show_most_expensive_shipping' ), 10, 2 );
		}
	}

	/**
	 * show_most_expensive_shipping.
	 *
	 * @version 5.2.0
	 * @since   5.2.0
	 *
	 * @param $rates
	 * @param $package
	 *
	 * @return array
	 */
	function show_most_expensive_shipping( $rates, $package ) {
		if ( 'yes' !== wcj_get_option( 'wcj_shipping_most_expensive_enabled', 'no' ) ) {
			return $rates;
		}
		$most_expensive_method = '';
		$ignored_method_ids    = wcj_get_option( 'wcj_shipping_most_expensive_ignored_methods', array( 'free_shipping' ) );
		$returned_rates        = array();
		if ( is_array( $rates ) ) :
			foreach ( $rates as $key => $rate ) {
				if (
					! in_array( $rate->method_id, $ignored_method_ids ) &&
					( empty( $most_expensive_method ) || $rate->cost > $most_expensive_method->cost )
				) {
					$most_expensive_method = $rate;
				}
			}
		endif;
		if ( ! empty( $most_expensive_method ) ) {
			$returned_rates[ $most_expensive_method->id ] = $most_expensive_method;
		}
		foreach ( $rates as $key => $rate ) {
			if ( in_array( $rate->method_id, $ignored_method_ids ) ) {
				$returned_rates[ $rate->id ] = $rate;
			}
		}
		if ( ! empty( $returned_rates ) ) {
			return $returned_rates;
		}
		return $rates;
	}

	/**
	 * free_shipping_by_product.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 * @return  bool
	 */
	function free_shipping_by_product( $is_available, $package ) {
		$free_shipping_granting_products = wcj_get_option( 'wcj_shipping_free_shipping_by_product_products', '' );
		if ( empty( $free_shipping_granting_products ) ) {
			return $is_available;
		}
		$free_shipping_granting_products_type = apply_filters( 'booster_option', 'all', wcj_get_option( 'wcj_shipping_free_shipping_by_product_type', 'all' ) );
		$package_grants_free_shipping = false;
		foreach( $package['contents'] as $item ) {
			if ( in_array( $item['product_id'], $free_shipping_granting_products ) ) {
				if ( 'at_least_one' === $free_shipping_granting_products_type ) {
					return true;
				} elseif ( ! $package_grants_free_shipping ) {
					$package_grants_free_shipping = true;
				}
			} else {
				if ( 'all' === $free_shipping_granting_products_type ) {
					return $is_available;
				}
			}
		}
		return ( $package_grants_free_shipping ) ? true : $is_available;
	}

	/**
	 * hide_shipping_when_free_is_available.
	 *
	 * @version 2.9.1
	 */
	function hide_shipping_when_free_is_available( $rates, $package ) {
		$free_shipping_rates = array();
		$is_free_shipping_available = false;
		foreach ( $rates as $rate_key => $rate ) {
			if ( false !== strpos( $rate_key, 'free_shipping' ) ) {
				$is_free_shipping_available = true;
				$free_shipping_rates[ $rate_key ] = $rate;
			} else {
				if (
					'except_local_pickup' === apply_filters( 'booster_option', 'hide_all', wcj_get_option( 'wcj_shipping_hide_if_free_available_type', 'hide_all' ) ) &&
					false !== strpos( $rate_key, 'local_pickup' )
				) {
					$free_shipping_rates[ $rate_key ] = $rate;
				} elseif (
					'flat_rate_only' === apply_filters( 'booster_option', 'hide_all', wcj_get_option( 'wcj_shipping_hide_if_free_available_type', 'hide_all' ) ) &&
					false === strpos( $rate_key, 'flat_rate' )
				) {
					$free_shipping_rates[ $rate_key ] = $rate;
				}
			}
		}
		return ( $is_free_shipping_available ) ? $free_shipping_rates : $rates;
	}

	/**
	 * add_hide_shipping_if_free_available_fields.
	 *
	 * @version 2.9.1
	 * @todo    (maybe) delete this
	 */
	function add_hide_shipping_if_free_available_fields( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			$updated_settings[] = $section;
			if ( isset( $section['id'] ) && 'woocommerce_ship_to_destination' === $section['id'] ) {
				$updated_settings = array_merge( $updated_settings, array(
					array(
						'title'    => __( 'Booster: Hide when free is available', 'woocommerce-jetpack' ),
						'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
						'id'       => 'wcj_shipping_hide_if_free_available_all',
						'default'  => 'no',
						'type'     => 'checkbox',
					),
					array(
						'id'       => 'wcj_shipping_hide_if_free_available_type',
						'desc_tip' => sprintf( __( 'Available options: hide all; hide all except "Local Pickup"; hide "Flat Rate" only.', 'woocommerce-jetpack' ) ),
						'default'  => 'hide_all',
						'type'     => 'select',
						'options'  => array(
							'hide_all'            => __( 'Hide all', 'woocommerce-jetpack' ),
							'except_local_pickup' => __( 'Hide all except "Local Pickup"', 'woocommerce-jetpack' ),
							'flat_rate_only'      => __( 'Hide "Flat Rate" only', 'woocommerce-jetpack' ),
						),
						'desc'     => apply_filters( 'booster_message', '', 'desc' ),
						'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
					),
				) );
			}
		}
		return $updated_settings;
	}

}

endif;

return new WCJ_Shipping_Options();
