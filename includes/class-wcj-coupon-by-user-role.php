<?php
/**
 * Booster for WooCommerce - Module - Coupon by User Role
 *
 * @version 3.6.0
 * @since   3.6.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Coupon_By_User_Role' ) ) :

class WCJ_Coupon_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 * @todo    per coupon
	 * @todo    (maybe) init all options in constructor
	 * @todo    (maybe) use another error code (instead of 10000)
	 */
	function __construct() {

		$this->id         = 'coupon_by_user_role';
		$this->short_desc = __( 'Coupon by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce coupons by user roles.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-coupon-by-user-role';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_coupons_enabled', array( $this, 'coupons_enabled' ),          PHP_INT_MAX, 1 );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'coupon_valid' ),             PHP_INT_MAX, 3 );
			add_filter( 'woocommerce_coupon_error',    array( $this, 'coupon_not_valid_message' ), PHP_INT_MAX, 3 );
		}
	}

	/**
	 * coupons_enabled.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function coupons_enabled( $is_enabled ) {
		$disabled_user_roles = get_option( 'wcj_coupon_by_user_role_disabled', '' );
		if ( ! empty( $disabled_user_roles ) && in_array( wcj_get_current_user_first_role(), $disabled_user_roles ) ) {
			return false;
		}
		return $is_enabled;
	}

	/**
	 * coupon_valid.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function coupon_valid( $valid, $coupon, $discounts ) {
		$invalid_user_roles = get_option( 'wcj_coupon_by_user_role_invalid', '' );
		if ( ! empty( $invalid_user_roles ) && in_array( wcj_get_current_user_first_role(), $invalid_user_roles ) ) {
			throw new Exception( get_option( 'wcj_coupon_by_user_role_invalid_message', __( 'Coupon is not valid for your user role.', 'woocommerce-jetpack' ) ), 10000 );
			return false;
		}
		return $valid;
	}

	/**
	 * coupon_not_valid_message.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function coupon_not_valid_message( $message, $code, $coupon ) {
		if ( 10000 === $code ) {
			return get_option( 'wcj_coupon_by_user_role_invalid_message', __( 'Coupon is not valid for your user role.', 'woocommerce-jetpack' ) );
		}
		return $message;
	}

}

endif;

return new WCJ_Coupon_By_User_Role();
