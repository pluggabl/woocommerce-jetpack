<?php
/**
 * Booster for WooCommerce - Settings - Admin Bar
 *
 * @version 2.8.3
 * @since   2.8.3
 * @author  Algoritmika Ltd.
 * @todo    reload page after enabling the module
 * @todo    (maybe) custom user nodes
 * @todo    (maybe) optional nodes selection
 * @todo    (maybe) separate "Booster Active Modules" admin bar menu
 * @todo    (maybe) separate "Booster Modules" admin bar menu
 * @todo    (maybe) separate "Booster Tools" admin bar menu
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_admin_bar_options',
	),
	array(
		'title'    => __( 'WooCommerce Admin Bar', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_bar_wc_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Booster Admin Bar', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_bar_booster_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_admin_bar_options',
	),
);
