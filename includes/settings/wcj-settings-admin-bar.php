<?php
/**
 * Booster for WooCommerce - Settings - Admin Bar
 *
 * @version 3.1.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_admin_bar_options',
	),
	array(
		'title'    => __( '"WooCommerce" Admin Bar', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_bar_wc_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( '"Booster" Admin Bar', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_bar_booster_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( '"Booster: Active" Admin Bar', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_bar_booster_active_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_admin_bar_options',
	),
);
