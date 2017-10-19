<?php
/**
 * Booster for WooCommerce - Module - Shipping by User Role
 *
 * @version 3.1.4
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_User_Role' ) ) :

class WCJ_Shipping_By_User_Role extends WCJ_Module_Shipping_By_Condition {

	/**
	 * Constructor.
	 *
	 * @version 3.1.4
	 * @since   2.8.0
	 */
	function __construct() {

		$this->id         = 'shipping_by_user_role';
		$this->short_desc = __( 'Shipping Methods by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set user roles to include/exclude for WooCommerce shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-user-role';

		$this->condition_options = array(
			'user_roles' => array(
				'title' => __( 'User Roles', 'woocommerce-jetpack' ),
				'desc'  => sprintf(
					__( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module', 'woocommerce-jetpack' ),
					admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=general' )
				),
			),
		);

		parent::__construct();

	}

	/**
	 * check.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function check( $options_id, $user_roles ) {
		if ( ! isset( $this->customer_role ) ) {
			$this->customer_role = wcj_get_current_user_first_role();
		}
		return in_array( $this->customer_role, $user_roles );
	}

	/**
	 * get_condition_options.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function get_condition_options( $options_id ) {
		return wcj_get_user_roles_options();
	}

}

endif;

return new WCJ_Shipping_By_User_Role();
