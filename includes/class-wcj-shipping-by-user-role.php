<?php
/**
 * Booster for WooCommerce - Module - Shipping by User Role
 *
 * @version 5.2.0
 * @since   5.2.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_User_Role' ) ) :

class WCJ_Shipping_By_User_Role extends WCJ_Module_Shipping_By_Condition {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   2.8.0
	 */
	function __construct() {

		$this->id         = 'shipping_by_user_role';
		$this->short_desc = __( 'Shipping Methods by Users', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set user roles, users or membership plans to include/exclude for shipping methods to show up (Free shipping available in Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Set user roles, users or membership plans to include/exclude for shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-users';

		$this->condition_options = array(
			'user_roles' => array(
				'title' => __( 'User Roles', 'woocommerce-jetpack' ),
				'desc'  => sprintf(
					__( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module.', 'woocommerce-jetpack' ),
					admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=general' )
				),
			),
			'user_id' => array(
				'title' => __( 'Users', 'woocommerce-jetpack' ),
				'desc'  => '',
			),
			'user_membership' => array(
				'title' => __( 'User Membership Plans', 'woocommerce-jetpack' ),
				'desc'  => sprintf(
					__( 'This section requires <a target="_blank" href="%s">WooCommerce Memberships</a> plugin.', 'woocommerce-jetpack' ),
					'https://woocommerce.com/products/woocommerce-memberships/'
				),
			),
		);

		parent::__construct();

	}

	/**
	 * check_multiple_roles.
	 *
	 * @version 4.8.0
	 * @since   4.8.0
	 *
	 * @return bool
	 */
	public function add_multiple_roles_option() {
		return true;
	}

	/**
	 * check.
	 *
	 * @version 4.8.0
	 * @since   3.2.0
	 * @todo    use `$package` (and in this case update `wcj_get_left_to_free_shipping()`)
	 */
	function check( $options_id, $user_roles_or_ids_or_membership_plans, $include_or_exclude, $package ) {
		switch( $options_id ) {
			case 'user_roles':
				if ( empty( $this->customer_roles ) ) {
					$this->customer_roles = 'no' === ( $multi_role_check = wcj_get_option( 'wcj_' . $this->id . '_check_multiple_roles', 'no' ) ) ? array( wcj_get_current_user_first_role() ) : wcj_get_current_user_all_roles();
				}
				return count( array_intersect( $this->customer_roles, $user_roles_or_ids_or_membership_plans ) ) > 0;
			case 'user_id':
				if ( ! isset( $this->user_id ) ) {
					$this->user_id = get_current_user_id();
				}
				return in_array( $this->user_id, $user_roles_or_ids_or_membership_plans );
			case 'user_membership':
				if ( ! isset( $this->user_id ) ) {
					$this->user_id = get_current_user_id();
				}
				if ( ! function_exists( 'wc_memberships_is_user_active_member' ) ) {
					return false;
				}
				foreach ( $user_roles_or_ids_or_membership_plans as $membership_plan ) {
					if ( wc_memberships_is_user_active_member( $this->user_id, $membership_plan ) ) {
						return true;
					}
				}
				return false;
		}
	}

	/**
	 * get_condition_options.
	 *
	 * @version 4.9.0
	 * @since   3.2.0
	 */
	function get_condition_options( $options_id ) {
		switch( $options_id ) {
			case 'user_roles':
				return wcj_get_user_roles_options();
			case 'user_membership':
				$membership_plans = array();
				$block_size       = 512;
				$offset           = 0;
				while( true ) {
					$args = array(
						'post_type'      => 'wc_membership_plan',
						'post_status'    => 'any',
						'posts_per_page' => $block_size,
						'offset'         => $offset,
						'orderby'        => 'title',
						'order'          => 'ASC',
						'fields'         => 'ids',
					);
					$loop = new WP_Query( $args );
					if ( ! $loop->have_posts() ) {
						break;
					}
					foreach ( $loop->posts as $post_id ) {
						$membership_plans[ $post_id ] = get_the_title( $post_id );
					}
					$offset += $block_size;
				}
				return $membership_plans;
		}
	}

}

endif;

return new WCJ_Shipping_By_User_Role();
