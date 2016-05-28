<?php
/**
 * WooCommerce Jetpack Product by User
 *
 * The WooCommerce Jetpack Product by User class.
 *
 * @version 2.5.2
 * @since   2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Product_By_User' ) ) :

class WCJ_Product_By_User extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	public function __construct() {

		$this->id         = 'product_by_user';
		$this->short_desc = __( 'Product by User', 'woocommerce-jetpack' );
		$this->desc       = __( 'Let users to add new WooCommerce products from frontend.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-by-user/';
		parent::__construct();

		if ( $this->is_enabled() ) {

		}
	}

	/**
	 * get_user_roles.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_user_roles() {
		global $wp_roles;
		$all_roles = $wp_roles->roles;
		$all_roles = apply_filters( 'editable_roles', $all_roles );
		$all_roles = array_merge( array(
			'guest' => array(
				'name'         => __( 'Guest', 'woocommerce-jetpack' ),
				'capabilities' => array(),
			) ), $all_roles );
		$all_roles_options = array();
		foreach ( $all_roles as $_role_key => $_role ) {
			$all_roles_options[ $_role_key ] = $_role['name'];
		}
		return $all_roles_options;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wcj_product_by_user_options',
			),
			array(
				'title'    => __( 'Fields', 'woocommerce-jetpack' ),
				'desc'     => __( 'Title', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_title_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'custom_attributes' => array( 'disabled' => 'disabled' ),
				'checkboxgroup' => 'start',
			),
			array(
				'desc'     => __( 'Description', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_desc_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'     => __( 'Short Description', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_short_desc_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'     => __( 'Categories', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_cats_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => '',
			),
			array(
				'desc'     => __( 'Tags', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_tags_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'end',
			),
			array(
				'title'    => __( 'User Visibility', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_user_visibility',
				'default'  => array(),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $this->get_user_roles(),
			),
			array(
				'title'    => __( 'Product Status', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_by_user_status',
				'default'  => 'draft',
				'type'     => 'select',
				'options'  => get_post_statuses(),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_by_user_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Product_By_User();
