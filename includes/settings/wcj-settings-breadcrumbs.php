<?php
/**
 * Booster for WooCommerce - Settings - Breadcrumbs
 *
 * @version 2.9.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_breadcrumbs_options',
	),
	array(
		'title'    => __( 'Change Breadcrumbs Home URL', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_breadcrumbs_change_home_url_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'desc'     => __( 'Home URL', 'woocommerce-jetpack' ),
		'id'       => 'wcj_breadcrumbs_home_url',
		'default'  => home_url(),
		'type'     => 'text',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Hide Breadcrumbs', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_breadcrumbs_hide',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc_tip' => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_breadcrumbs_options',
	),
);
