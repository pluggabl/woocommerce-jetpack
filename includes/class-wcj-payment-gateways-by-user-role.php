<?php
/**
 * Booster for WooCommerce - Module - Gateways by User Role
 *
 * @version 4.7.1
 * @since   2.5.3
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_By_User_Role' ) ) :

class WCJ_Payment_Gateways_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.2
	 * @since   2.5.3
	 */
	function __construct() {
		$this->id         = 'payment_gateways_by_user_role';
		$this->short_desc = __( 'Gateways by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set user roles to include/exclude for payment gateways to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-payment-gateways-by-user-role';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX - 100, 1 );
		}
	}

	/**
	 * available_payment_gateways.
	 *
	 * @version 4.7.1
	 * @since   2.5.3
	 */
	function available_payment_gateways( $_available_gateways ) {
		$customer_roles = 'no' === ( $multi_role_check = wcj_get_option( 'wcj_payment_gateways_by_user_role_multi_role_check', 'no' ) ) ? array( wcj_get_current_user_first_role() ) : wcj_get_current_user_all_roles();
		foreach ( $_available_gateways as $key => $gateway ) {
			$include_roles = wcj_get_option( 'wcj_gateways_user_roles_include_' . $key, '' );
			if ( ! empty( $include_roles ) && ! array_intersect( $customer_roles, $include_roles ) ) {
				unset( $_available_gateways[ $key ] );
				continue;
			}
			$exclude_roles = wcj_get_option( 'wcj_gateways_user_roles_exclude_' . $key, '' );
			if ( ! empty( $exclude_roles ) && array_intersect( $customer_roles, $exclude_roles ) ) {
				unset( $_available_gateways[ $key ] );
				continue;
			}
		}
		return $_available_gateways;
	}

}

endif;

return new WCJ_Payment_Gateways_By_User_Role();
