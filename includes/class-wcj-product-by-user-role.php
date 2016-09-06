<?php
/**
 * WooCommerce Jetpack Product by User Role
 *
 * The WooCommerce Jetpack Product by User Role class.
 *
 * @version 2.5.6
 * @since   2.5.5
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_By_User_Role' ) ) :

class WCJ_Product_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.6
	 * @since   2.5.5
	 */
	function __construct() {

		$this->id         = 'product_by_user_role';
		$this->short_desc = __( 'Product Visibility by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display WooCommerce products by customer\'s user role.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-visibility-by-user-role/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				add_filter( 'woocommerce_product_is_visible', array( $this, 'product_by_user_role' ), PHP_INT_MAX, 2 );
			}
		}
	}

	/**
	 * product_by_user_role.
	 *
	 * @version 2.5.6
	 * @since   2.5.5
	 */
	function product_by_user_role( $visible, $product_id ) {
		$visible_user_roles = get_post_meta( $product_id, '_' . 'wcj_product_by_user_role_visible', true );
		if ( is_array( $visible_user_roles ) && ! empty( $visible_user_roles ) ) {
			$current_user_roles = wcj_get_current_user_all_roles();
			$the_intersect = array_intersect( $visible_user_roles, $current_user_roles );
			if ( empty( $the_intersect ) ) {
				return false;
			}
		}
		return $visible;
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 2.5.6
	 * @since   2.5.5
	 */
	function get_meta_box_options() {
		$options = array(
			array(
				'name'       => 'wcj_product_by_user_role_visible',
				'default'    => '',
				'type'       => 'select',
				'options'    => wcj_get_user_roles_options(),
				'multiple'   => true,
				'title'      => __( 'Visible for User Roles', 'woocommerce-jetpack' ),
				'tooltip'    => __( 'Hold Control (Ctrl) key to select multiple roles.', 'woocommerce-jetpack' ),
			),
		);
		return $options;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.6
	 * @since   2.5.5
	 */
	function get_settings() {
		$settings = array();
		return $this->add_standard_settings( $settings, __( 'When enabled, module will add new "Booster: Product Visibility by User Role" meta box to each product\'s edit page.', 'woocommerce-jetpack' ) );
	}
}

endif;

return new WCJ_Product_By_User_Role();
