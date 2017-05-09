<?php
/**
 * Booster for WooCommerce - Module - Shipping by User Role
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_User_Role' ) ) :

class WCJ_Shipping_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function __construct() {

		$this->id         = 'shipping_by_user_role';
		$this->short_desc = __( 'Shipping Methods by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set user roles to include/exclude for WooCommerce shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-user-role';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_package_rates', array( $this, 'available_shipping_methods' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * available_shipping_methods.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 * @todo    apply_filters( 'booster_get_option' )
	 */
	function available_shipping_methods( $rates, $package ) {
		foreach ( $rates as $rate_key => $rate ) {
			$customer_role = wcj_get_current_user_first_role();
			$include_roles = get_option( 'wcj_shipping_user_roles_include_' . $rate->method_id, '' );
			if ( ! empty( $include_roles ) && ! in_array( $customer_role, $include_roles ) ) {
				unset( $rates[ $rate_key ] );
				continue;
			}
			$exclude_roles = get_option( 'wcj_shipping_user_roles_exclude_' . $rate->method_id, '' );
			if ( ! empty( $exclude_roles ) && in_array( $customer_role, $exclude_roles ) ) {
				unset( $rates[ $rate_key ] );
				continue;
			}
		}
		return $rates;
	}

}

endif;

return new WCJ_Shipping_By_User_Role();
