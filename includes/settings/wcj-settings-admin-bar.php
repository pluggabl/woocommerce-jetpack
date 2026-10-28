<?php
/**
 * Booster for WooCommerce - Settings - Admin Bar
 *
 * @version 7.0.0
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'id'   => 'admin_bar_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'admin_bar_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'admin_bar_general_options_tab' => __( 'General options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'admin_bar_general_options_tab',
		'type' => 'tab_start',
	),
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
		'id'   => 'wcj_admin_bar_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'admin_bar_general_options_tab',
		'type' => 'tab_end',
	),
);
