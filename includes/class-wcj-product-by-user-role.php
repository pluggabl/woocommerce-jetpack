<?php
/**
 * Booster for WooCommerce - Module - Product Visibility by User Role
 *
 * @version 3.6.0
 * @since   2.5.5
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_By_User_Role' ) ) :

class WCJ_Product_By_User_Role extends WCJ_Module_Product_By_Condition {

	/**
	 * Constructor.
	 *
	 * @version 3.6.0
	 * @since   2.5.5
	 */
	function __construct() {

		$this->id         = 'product_by_user_role';
		$this->short_desc = __( 'Product Visibility by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display products by customer\'s user role.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-visibility-by-user-role';
		$this->extra_desc = __( 'When enabled, module will add new "Booster: Product Visibility by User Role" meta box to each product\'s edit page.', 'woocommerce-jetpack' );

		$this->title      = __( 'User Roles', 'woocommerce-jetpack' );

		parent::__construct();

	}

	/**
	 * get_options_list.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function get_options_list() {
		return wcj_get_user_roles_options();
	}

	/**
	 * get_check_option.
	 *
	 * @version 3.6.0
	 * @since   3.6.0
	 */
	function get_check_option() {
		return wcj_get_current_user_all_roles();
	}

}

endif;

return new WCJ_Product_By_User_Role();
