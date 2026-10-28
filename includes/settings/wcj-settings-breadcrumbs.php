<?php
/**
 * Booster for WooCommerce - Settings - Breadcrumbs
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
		'id'   => 'breadcrumbs_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'breadcrumbs_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'breadcrumbs_general_options_tab' => __( 'General options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'breadcrumbs_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_breadcrumbs_options',
	),
	array(
		'title'   => __( 'Change Breadcrumbs Home URL', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_breadcrumbs_change_home_url_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'desc'    => __( 'Home URL', 'woocommerce-jetpack' ),
		'id'      => 'wcj_breadcrumbs_home_url',
		'default' => home_url(),
		'type'    => 'text',
		'css'     => 'width:66%;min-width:300px;',
	),
	array(
		'title'             => __( 'Hide Breadcrumbs', 'woocommerce-jetpack' ),
		'desc'              => __( 'Hide', 'woocommerce-jetpack' ),
		'id'                => 'wcj_breadcrumbs_hide',
		'default'           => 'no',
		'type'              => 'checkbox',
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'   => 'wcj_breadcrumbs_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'breadcrumbs_general_options_tab',
		'type' => 'tab_end',
	),
);
