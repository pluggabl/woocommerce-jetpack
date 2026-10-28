<?php
/**
 * Booster for WooCommerce - Module - Shipping by User Role
 *
 * @version 7.3.0
 * @since   5.2.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Shipping_By_User_Role' ) ) :
		/**
		 * WCJ_Shipping_By_User_Role.
		 *
		 * @version 7.1.6
		 * @since   2.8.0
		 */
	class WCJ_Shipping_By_User_Role extends WCJ_Module_Shipping_By_Condition {

		/**
		 * The module user_id
		 *
		 * @var varchar $user_id Module user_id.
		 */
		public $user_id;

		/**
		 * The module customer_roles
		 *
		 * @var varchar $customer_roles Module customer_roles.
		 */
		public $customer_roles;

		/**
		 * Constructor.
		 *
		 * @version 7.3.0
		 * @since   2.8.0
		 */
		public function __construct() {

			$this->id         = 'shipping_by_user_role';
			$this->short_desc = 'Shipping Methods by Users';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Shipping Methods by Users', 'woocommerce-jetpack' );
				}
			);
			$this->desc = 'Set user roles, users or membership plans to include/exclude for shipping methods to show up (Free shipping available in Elite).';
			wcj_init_presentation(
				function () {
					$this->desc = __( 'Set user roles, users or membership plans to include/exclude for shipping methods to show up (Free shipping available in Elite).', 'woocommerce-jetpack' );
				}
			);
			$this->desc_pro = 'Set user roles, users or membership plans to include/exclude for shipping methods to show up.';
			wcj_init_presentation(
				function () {
					$this->desc_pro = __( 'Set user roles, users or membership plans to include/exclude for shipping methods to show up.', 'woocommerce-jetpack' );
				}
			);
			$this->link_slug = 'woocommerce-shipping-methods-by-users';

			$this->condition_options = array(
				'user_roles'      => array(
					'title' => 'User Roles',
					'desc'  => sprintf(
						/* translators: %s: translators Added */
						'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module.',
						admin_url( wcj_admin_tab_url() . '&wcj-cat=emails_and_misc&section=general' )
					),
				),
				'user_id'         => array(
					'title' => 'Users',
					'desc'  => '',
				),
				'user_membership' => array(
					'title' => 'User Membership Plans',
					'desc'  => sprintf(
						/* translators: %s: translators Added */
						'This section requires <a target="_blank" href="%s">WooCommerce Memberships</a> plugin.',
						'https://woocommerce.com/products/woocommerce-memberships/'
					),
				),
			);
			wcj_init_presentation(
				function () {
					$this->condition_options = array(
						'user_roles'      => array(
							'title' => __( 'User Roles', 'woocommerce-jetpack' ),
							'desc'  => sprintf(
								/* translators: %s: translators Added */
								__( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module.', 'woocommerce-jetpack' ),
								admin_url( wcj_admin_tab_url() . '&wcj-cat=emails_and_misc&section=general' )
							),
						),
						'user_id'         => array(
							'title' => __( 'Users', 'woocommerce-jetpack' ),
							'desc'  => '',
						),
						'user_membership' => array(
							'title' => __( 'User Membership Plans', 'woocommerce-jetpack' ),
							'desc'  => sprintf(
								/* translators: %s: translators Added */
								__( 'This section requires <a target="_blank" href="%s">WooCommerce Memberships</a> plugin.', 'woocommerce-jetpack' ),
								'https://woocommerce.com/products/woocommerce-memberships/'
							),
						),
					);
				}
			);

			parent::__construct();
		}

		/**
		 * Check_multiple_roles.
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
		 * Check.
		 *
		 * @version 7.0.0
		 * @since   3.2.0
		 * @todo    use `$package` (and in this case update `wcj_get_left_to_free_shipping()`)
		 * @param string         $options_id defines the options_id.
		 * @param string         $user_roles_or_ids_or_membership_plans defines the user_roles_or_ids_or_membership_plans.
		 * @param string         $include_or_exclude defines the include_or_exclude.
		 * @param string | array $package defines the package.
		 */
		public function check( $options_id, $user_roles_or_ids_or_membership_plans, $include_or_exclude, $package ) {
			switch ( $options_id ) {
				case 'user_roles':
					$wcj_path = 'cached_roles';
					if ( empty( $this->customer_roles ) ) {
						$multi_role_check     = wcj_get_option( 'wcj_' . $this->id . '_check_multiple_roles', 'no' );
						$wcj_path             = 'no' === $multi_role_check ? 'fresh_first_role' : 'fresh_all_roles';
						$this->customer_roles = 'no' === ( $multi_role_check ) ? array( wcj_get_current_user_first_role() ) : wcj_get_current_user_all_roles();
					}
					return WCJ_Rule_Explanations::shipping_v2_result( $this, count( array_intersect( WCJ_Rule_Explanations::shipping_v2_bound( $this, $this->customer_roles ), $user_roles_or_ids_or_membership_plans ) ) > 0, $wcj_path );
				case 'user_id':
					$wcj_path = isset( $this->user_id ) ? 'cached_own_id' : 'fresh_own_id';
					if ( ! isset( $this->user_id ) ) {
						$this->user_id = get_current_user_id();
					}
					return WCJ_Rule_Explanations::shipping_v2_result( $this, in_array( (string) $this->user_id, $user_roles_or_ids_or_membership_plans, true ), $wcj_path );
				case 'user_membership':
					if ( ! isset( $this->user_id ) ) {
						$this->user_id = get_current_user_id();
					}
					if ( ! function_exists( 'wc_memberships_is_user_active_member' ) ) {
						return WCJ_Rule_Explanations::shipping_v2_result( $this, false, 'membership_missing', 'membership_extension_missing' );
					}
					foreach ( $user_roles_or_ids_or_membership_plans as $membership_plan ) {
						if ( wc_memberships_is_user_active_member( $this->user_id, $membership_plan ) ) {
							return WCJ_Rule_Explanations::shipping_v2_result( $this, true, 'membership_present' );
						}
					}
					return WCJ_Rule_Explanations::shipping_v2_result( $this, false, 'membership_present' );
			}
		}

		/**
		 * Get_condition_options.
		 *
		 * @version 4.9.0
		 * @since   3.2.0
		 * @param string $options_id defines the options_id.
		 */
		public function get_condition_options( $options_id ) {
			switch ( $options_id ) {
				case 'user_roles':
					return wcj_get_user_roles_options();
				case 'user_membership':
					$membership_plans = array();
					$block_size       = 512;
					$offset           = 0;
					while ( true ) {
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
