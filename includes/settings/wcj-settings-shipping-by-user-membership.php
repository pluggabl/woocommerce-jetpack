<?php
/**
 * Booster for WooCommerce Settings - Shipping by User Membership
 *
 * @version 3.1.4
 * @since   3.1.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title' => __( 'Shipping Methods', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Leave empty to disable.', 'woocommerce-jetpack' )  . ' ' .
			sprintf(
				__( 'This module requires <a target="_blank" href="%s">WooCommerce Memberships</a> plugin.', 'woocommerce-jetpack' ),
				'https://woocommerce.com/products/woocommerce-memberships/'
			),
		'id'    => 'wcj_shipping_by_user_membership_methods_options',
	),
);
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
foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
	if ( ! in_array( $method->id, array( 'flat_rate', 'local_pickup' ) ) ) {
		$custom_attributes = apply_filters( 'booster_get_message', '', 'disabled' );
		if ( '' == $custom_attributes ) {
			$custom_attributes = array();
		}
		$desc_tip = apply_filters( 'booster_get_message', '', 'desc_no_link' );
	} else {
		$custom_attributes = array();
		$desc_tip = '';
	}
	$settings = array_merge( $settings, array(
		array(
			'title'     => $method->get_method_title(),
			'desc_tip'  => $desc_tip,
			'desc'      => __( 'Include User Membership Plans', 'woocommerce-jetpack' ),
			'id'        => 'wcj_shipping_user_membership_include_' . $method->id,
			'default'   => '',
			'type'      => 'multiselect',
			'class'     => 'chosen_select',
			'css'       => 'width: 450px;',
			'options'   => $membership_plans,
			'custom_attributes' => $custom_attributes,
		),
		array(
			'desc_tip'  => $desc_tip,
			'desc'      => __( 'Exclude User Membership Plans', 'woocommerce-jetpack' ),
			'id'        => 'wcj_shipping_user_membership_exclude_' . $method->id,
			'default'   => '',
			'type'      => 'multiselect',
			'class'     => 'chosen_select',
			'css'       => 'width: 450px;',
			'options'   => $membership_plans,
			'custom_attributes' => $custom_attributes,
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'  => 'sectionend',
		'id'    => 'wcj_shipping_by_user_membership_methods_options',
	),
) );
return $settings;
