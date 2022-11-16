<?php
/**
 * Booster for WooCommerce - Module - Product Visibility by User Role
 *
 * @version 5.6.8
 * @since   2.5.5
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Product_By_User_Role' ) ) :
	/**
	 * WCJ_Product_By_User_Role.
	 */
	class WCJ_Product_By_User_Role extends WCJ_Module_Product_By_Condition {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.5.5
		 */
		public function __construct() {

			$this->id         = 'product_by_user_role';
			$this->short_desc = __( 'Product Visibility by User Role', 'woocommerce-jetpack' );
			$this->desc       = __( 'Display products by customer\'s user role. Visibility method options (Plus)', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Display products by customer\'s user role.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-visibility-by-user-role';
			$this->extra_desc = __( 'When enabled, module will add new "Booster: Product Visibility by User Role" meta box to each product\'s edit page.', 'woocommerce-jetpack' );

			$this->title = __( 'User Roles', 'woocommerce-jetpack' );

			parent::__construct();

		}

		/**
		 * Maybe_add_extra_settings.
		 *
		 * @version 5.6.8
		 * @since   4.9.0
		 *
		 * @return array
		 */
		public function maybe_add_extra_settings() {
			$message = apply_filters( 'booster_message', '', 'desc' );
			return array(
				array(
					'title' => __( 'User Options', 'woocommerce-jetpack' ),
					'type'  => 'title',
					'id'    => 'wcj_' . $this->id . '_user_options',
				),
				array(
					'title'             => __( 'Skip Editable Roles Filter', 'woocommerce-jetpack' ),
					/* translators: %s: translation added */
					'desc_tip'          => __( 'Ignores <code>editable_roles</code> filter on admin.', 'woocommerce-jetpack' ) . '<br />' . sprintf( __( 'Enable this option for example if the shop manager can\'t see some role but only if you\'ve already tried the <strong>Shop Manager Editable Roles</strong> on <a href="%s">Admin Tools</a> module.', 'woocommerce-jetpack' ), admin_url( wcj_admin_tab_url() . '&wcj-cat=emails_and_misc&section=admin_tools' ) ),
					'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
					'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
					'id'                => 'wcj_' . $this->id . '_user_options_skip_editable_roles',
					'default'           => 'no',
					'type'              => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'wcj_' . $this->id . '_user_options',
				),
			);
		}

		/**
		 * Get_options_list.
		 *
		 * @version 4.9.0
		 * @since   3.6.0
		 */
		public function get_options_list() {
			$user_roles_options_args = 'no' === wcj_get_option( 'wcj_' . $this->id . '_user_options_skip_editable_roles', 'no' ) ? null : array( 'skip_editable_roles_filter' => true );
			return wcj_get_user_roles_options( $user_roles_options_args );
		}

		/**
		 * Get_check_option.
		 *
		 * @version 3.6.0
		 * @since   3.6.0
		 */
		public function get_check_option() {
			return wcj_get_current_user_all_roles();
		}

	}

endif;

return new WCJ_Product_By_User_Role();
