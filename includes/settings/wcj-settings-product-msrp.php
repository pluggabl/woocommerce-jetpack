<?php
/**
 * Booster for WooCommerce - Settings - Product MSRP
 *
 * @version 3.6.0
 * @since   3.6.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$sections = array(
	'single'   => __( 'Single Product Page', 'woocommerce-jetpack' ),
	'archives' => __( 'Archives', 'woocommerce-jetpack' ),
);
$settings = array();
foreach ( $sections as $section_id => $section_title ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => sprintf( __( '%s Display Options', 'woocommerce-jetpack' ), $section_title ),
			'type'     => 'title',
			'id'       => 'wcj_product_msrp_display_on_' . $section_id . '_options',
		),
		array(
			'title'    => __( 'Display', 'woocommerce-jetpack' ),
			'type'     => 'select',
			'id'       => 'wcj_product_msrp_display_on_' . $section_id,
			'default'  => 'show',
			'options'  => array(
				'hide'           => __( 'Do not show', 'woocommerce-jetpack' ),
				'show'           => __( 'Show', 'woocommerce-jetpack' ),
				'show_if_higher' => __( 'Only show if MSRP is higher than the standard price', 'woocommerce-jetpack' ),
				'show_if_diff'   => __( 'Only show if MSRP differs from the standard price', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'    => __( 'Position', 'woocommerce-jetpack' ),
			'type'     => 'select',
			'id'       => 'wcj_product_msrp_display_on_' . $section_id . '_position',
			'default'  => 'after_price',
			'options'  => array(
				'before_price' => __( 'Before the standard price', 'woocommerce-jetpack' ),
				'after_price'  => __( 'After the standard price', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'    => __( 'Savings', 'woocommerce-jetpack' ),
			'desc'     => sprintf( __( 'Savings amount. To display this, use %s in "Final Template"', 'woocommerce-jetpack' ), '<code>' . '%you_save%' . '</code>' ) . ' ' .
				wcj_message_replaced_values( array( '%you_save_raw%' ) ),
			'type'     => 'custom_textarea',
			'id'       => 'wcj_product_msrp_display_on_' . $section_id . '_you_save',
			'default'  => ' (%you_save_raw%)',
		),
		array(
			'desc'     => sprintf( __( 'Savings amount in percent. To display this, use %s in "Final Template"', 'woocommerce-jetpack' ), '<code>' . '%you_save_percent%' . '</code>' ) . ' ' .
				wcj_message_replaced_values( array( '%you_save_percent_raw%' ) ),
			'type'     => 'custom_textarea',
			'id'       => 'wcj_product_msrp_display_on_' . $section_id . '_you_save_percent',
			'default'  => ' (%you_save_percent_raw% %)',
		),
		array(
			'desc'     => __( 'Savings amount in percent rounding precision', 'woocommerce-jetpack' ),
			'type'     => 'number',
			'id'       => 'wcj_product_msrp_display_on_' . $section_id . '_you_save_percent_round',
			'default'  => 0,
			'custom_attributes' => array( 'min' => 0 ),
		),
		array(
			'title'    => __( 'Final Template', 'woocommerce-jetpack' ),
			'desc_tip' => apply_filters( 'booster_message', '', 'desc_no_link' ),
			'desc'     => wcj_message_replaced_values( array( '%msrp%', '%you_save%', '%you_save_percent%' ) ),
			'type'     => 'custom_textarea',
			'id'       => 'wcj_product_msrp_display_on_' . $section_id . '_template',
			'default'  => '<div class="price"><label for="wcj_product_msrp">MSRP</label>: <span id="wcj_product_msrp"><del>%msrp%</del>%you_save%</span></div>',
			'css'      => 'width:100%;',
			'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_product_msrp_display_' . $section_id . '_options',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Admin Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_msrp_admin_options',
	),
	array(
		'title'    => __( 'Admin MSRP Input Display', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'id'       => 'wcj_product_msrp_admin_view',
		'default'  => 'inline',
		'options'  => array(
			'inline'   => __( 'Inline', 'woocommerce-jetpack' ),
			'meta_box' => __( 'As separate meta box', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_msrp_admin_options',
	),
) );
return $settings;
