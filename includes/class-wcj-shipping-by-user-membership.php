<?php
/**
 * Booster for WooCommerce - Module - Shipping by User Membership
 *
 * @version 3.1.4
 * @since   3.1.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_User_Membership' ) ) :

class WCJ_Shipping_By_User_Membership extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function __construct() {

		$this->id         = 'shipping_by_user_membership';
		$this->short_desc = __( 'Shipping Methods by User Membership', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set membership plans to include/exclude for WooCommerce shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-user-membership';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_package_rates', array( $this, 'available_shipping_methods' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * is_user_active_member.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function is_user_active_member( $user_id, $membership_plans ) {
		if ( ! function_exists( 'wc_memberships_is_user_active_member' ) ) {
			return false;
		}
		foreach ( $membership_plans as $membership_plan ) {
			if ( wc_memberships_is_user_active_member( $user_id, $membership_plan ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * available_shipping_methods.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 * @todo    apply_filters( 'booster_get_option' )
	 */
	function available_shipping_methods( $rates, $package ) {
		$user_id = get_current_user_id();
		foreach ( $rates as $rate_key => $rate ) {
			$include_membership = get_option( 'wcj_shipping_user_membership_include_' . $rate->method_id, '' );
			if ( ! empty( $include_membership ) && ! $this->is_user_active_member( $user_id, $include_membership ) ) {
				unset( $rates[ $rate_key ] );
				continue;
			}
			$exclude_membership = get_option( 'wcj_shipping_user_membership_exclude_' . $rate->method_id, '' );
			if ( ! empty( $exclude_membership ) && $this->is_user_active_member( $user_id, $exclude_membership ) ) {
				unset( $rates[ $rate_key ] );
				continue;
			}
		}
		return $rates;
	}

}

endif;

return new WCJ_Shipping_By_User_Membership();
