<?php
/**
 * Booster for WooCommerce - Module - Coupon by User Role
 *
 * @version 5.2.0
 * @since   3.6.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Coupon_By_User_Role' ) ) :

class WCJ_Coupon_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   3.6.0
	 * @todo    (maybe) init all options in constructor
	 * @todo    (maybe) use another error code (instead of 10000)
	 */
	function __construct() {

		$this->id         = 'coupon_by_user_role';
		$this->short_desc = __( 'Coupon by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Coupons by user roles. Invalidate per Coupon (Plus). Custom coupon invalid messages (Plus)', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Coupons by user roles.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-coupon-by-user-role';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_coupons_enabled', array( $this, 'coupons_enabled' ),          PHP_INT_MAX, 1 );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'coupon_valid' ),             PHP_INT_MAX, 3 );
			add_filter( 'woocommerce_coupon_error',    array( $this, 'coupon_not_valid_message' ), PHP_INT_MAX, 3 );
			if ( $this->invalid_per_coupon_enabled = ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_coupon_by_user_role_invalid_per_coupon', 'no' ) ) ) ) {
				$this->meta_box_screen   = 'shop_coupon';
				$this->meta_box_context  = 'side';
				$this->meta_box_priority = 'default';
				add_action( 'add_meta_boxes',        array( $this, 'add_meta_box' ) );
				add_action( 'save_post_shop_coupon', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * coupons_enabled.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function coupons_enabled( $is_enabled ) {
		$disabled_user_roles = wcj_get_option( 'wcj_coupon_by_user_role_disabled', '' );
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
	 * @todo    (maybe) check if `$coupon->get_id()` is working in WC below v3.0.0
	 */
	function coupon_valid( $valid, $coupon, $discounts ) {
		$invalid_user_roles = wcj_get_option( 'wcj_coupon_by_user_role_invalid', '' );
		if ( empty( $invalid_user_roles ) ) {
			$invalid_user_roles = array();
		}
		if ( $this->invalid_per_coupon_enabled ) {
			$invalid_user_roles_per_coupon = get_post_meta( $coupon->get_id(), '_' . 'wcj_coupon_by_user_role_invalid', true );
			if ( ! empty( $invalid_user_roles_per_coupon ) ) {
				$invalid_user_roles = array_merge( $invalid_user_roles, $invalid_user_roles_per_coupon );
			}
		}
		if ( ! empty( $invalid_user_roles ) && in_array( wcj_get_current_user_first_role(), $invalid_user_roles ) ) {
			throw new Exception( apply_filters( 'booster_option', __( 'Coupon is not valid for your user role.', 'woocommerce-jetpack' ),
				get_option( 'wcj_coupon_by_user_role_invalid_message', __( 'Coupon is not valid for your user role.', 'woocommerce-jetpack' ) ) ), 10000 );
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
			return apply_filters( 'booster_option', __( 'Coupon is not valid for your user role.', 'woocommerce-jetpack' ),
				get_option( 'wcj_coupon_by_user_role_invalid_message', __( 'Coupon is not valid for your user role.', 'woocommerce-jetpack' ) ) );
		}
		return $message;
	}

}

endif;

return new WCJ_Coupon_By_User_Role();
