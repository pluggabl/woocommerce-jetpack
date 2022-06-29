<?php
/**
 * Booster for WooCommerce - Settings - Admin Bar
 *
 * @version 5.6.0
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_admin_bar_options',
	),
	array(
		'title'         => __( '"WooCommerce" Admin Bar', 'woocommerce-jetpack' ),
		'desc'          => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
		'id'            => 'wcj_admin_bar_wc_enabled',
		'default'       => 'yes',
		'type'          => 'checkbox',
		'checkboxgroup' => 'start',
	),
	array(
		'desc'          => __( 'List product categories in "WooCommerce > Products > Categories"', 'woocommerce-jetpack' ),
		'id'            => 'wcj_admin_bar_wc_list_cats',
		'default'       => 'no',
		'type'          => 'checkbox',
		'checkboxgroup' => '',
	),
	array(
		'desc'          => __( 'List product tags in "WooCommerce > Products > Tags"', 'woocommerce-jetpack' ),
		'id'            => 'wcj_admin_bar_wc_list_tags',
		'default'       => 'no',
		'type'          => 'checkbox',
		'checkboxgroup' => 'end',
	),
	array(
		'title'   => __( '"Booster" Admin Bar', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_admin_bar_booster_enabled',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( '"Booster: Active" Admin Bar', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_admin_bar_booster_active_enabled',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_admin_bar_options',
	),
);
