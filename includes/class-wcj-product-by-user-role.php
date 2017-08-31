<?php
/**
 * Booster for WooCommerce - Module - Product Visibility by User Role
 *
 * @version 3.1.0
 * @since   2.5.5
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_By_User_Role' ) ) :

class WCJ_Product_By_User_Role extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @since   2.5.5
	 * @todo    add "Admin Products List Column" option (same as in "Product Visibility by Country" module)
	 */
	function __construct() {

		$this->id         = 'product_by_user_role';
		$this->short_desc = __( 'Product Visibility by User Role', 'woocommerce-jetpack' );
		$this->desc       = __( 'Display WooCommerce products by customer\'s user role.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-visibility-by-user-role';
		$this->extra_desc = __( 'When enabled, module will add new "Booster: Product Visibility by User Role" meta box to each product\'s edit page.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				if ( 'yes' === get_option( 'wcj_product_by_user_role_visibility', 'yes' ) ) {
					add_filter( 'woocommerce_product_is_visible', array( $this, 'product_by_user_role_visibility' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_product_by_user_role_purchasable', 'no' ) ) {
					add_filter( 'woocommerce_is_purchasable',     array( $this, 'product_by_user_role_purchasable' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_product_by_user_role_query', 'no' ) ) {
					add_action( 'pre_get_posts',                  array( $this, 'product_by_user_role_pre_get_posts' ) );
				}
			}
		}
	}

	/**
	 * product_by_user_role_pre_get_posts.
	 *
	 * @version 3.1.0
	 * @since   2.6.0
	 * @todo    (maybe) add global function for this, as similar code is in "Product Visibility by Country" module
	 * @todo    check if `purchasable` and `pre_get_posts` hooks should be added to other "Product Visibility" modules
	 */
	function product_by_user_role_pre_get_posts( $query ) {
		if ( is_admin() ) {
			return;
		}
		remove_action( 'pre_get_posts', array( $this, 'product_by_user_role_pre_get_posts' ) );
		$current_user_roles = wcj_get_current_user_all_roles();
		$post__not_in = $query->get( 'post__not_in' );
		$args = $query->query;
		$args['fields'] = 'ids';
		$loop = new WP_Query( $args );
		foreach ( $loop->posts as $product_id ) {
			$visible_user_roles = get_post_meta( $product_id, '_' . 'wcj_product_by_user_role_visible', true );
			if ( is_array( $visible_user_roles ) && ! empty( $visible_user_roles ) ) {
				$the_intersect = array_intersect( $visible_user_roles, $current_user_roles );
				if ( empty( $the_intersect ) ) {
					$post__not_in[] = $product_id;
				}
			}
		}
		$query->set( 'post__not_in', $post__not_in );
		add_action( 'pre_get_posts', array( $this, 'product_by_user_role_pre_get_posts' ) );
	}

	/**
	 * product_by_user_role_purchasable.
	 *
	 * @version 2.7.0
	 * @since   2.6.0
	 */
	function product_by_user_role_purchasable( $purchasable, $_product ) {
		return $this->product_by_user_role_visibility( $purchasable, wcj_get_product_id_or_variation_parent_id( $_product ) );
	}

	/**
	 * product_by_user_role_visibility.
	 *
	 * @version 2.6.0
	 * @since   2.5.5
	 */
	function product_by_user_role_visibility( $visible, $product_id ) {
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

}

endif;

return new WCJ_Product_By_User_Role();
