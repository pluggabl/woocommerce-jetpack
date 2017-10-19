<?php
/**
 * Booster for WooCommerce - Module - Shipping by User Membership
 *
 * @version 3.1.4
 * @since   3.1.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_User_Membership' ) ) :

class WCJ_Shipping_By_User_Membership extends WCJ_Module_Shipping_By_Condition {

	/**
	 * Constructor.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function __construct() {

		$this->id         = 'shipping_by_user_membership';
		$this->short_desc = __( 'Shipping Methods by User Membership', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set membership plans to include/exclude for WooCommerce shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-user-membership';

		$this->condition_options = array(
			'user_membership' => array(
				'title' => __( 'User Membership Plans', 'woocommerce-jetpack' ),
				'desc'  => sprintf(
					__( 'This module requires <a target="_blank" href="%s">WooCommerce Memberships</a> plugin.', 'woocommerce-jetpack' ),
					'https://woocommerce.com/products/woocommerce-memberships/'
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
	function check( $options_id, $membership_plans ) {
		if ( ! isset( $this->user_id ) ) {
			$this->user_id = get_current_user_id();
		}
		if ( ! function_exists( 'wc_memberships_is_user_active_member' ) ) {
			return false;
		}
		foreach ( $membership_plans as $membership_plan ) {
			if ( wc_memberships_is_user_active_member( $this->user_id, $membership_plan ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * get_condition_options.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function get_condition_options( $options_id ) {
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

endif;

return new WCJ_Shipping_By_User_Membership();
