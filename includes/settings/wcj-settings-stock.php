<?php
/**
 * Booster for WooCommerce - Settings - Stock
 *
 * @version 3.5.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Custom "In Stock" Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_stock_custom_in_stock_options',
	),
	array(
		'title'    => __( 'Custom "In Stock"', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_stock_custom_in_stock_section_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Custom "In Stock" HTML', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_custom_in_stock_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'desc_tip' => __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_custom_in_stock',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:100%;height:100px;',
	),
	array(
		'title'    => __( 'Custom "In Stock" Class', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_custom_in_stock_class_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'       => 'wcj_stock_custom_in_stock_class',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_stock_custom_in_stock_options',
	),
	array(
		'title'    => __( 'Custom "Out of Stock" Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_stock_custom_out_of_stock_options',
	),
	array(
		'title'    => __( 'Custom "Out of Stock"', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_stock_custom_out_of_stock_section_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Custom "Out of Stock" HTML', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_custom_out_of_stock_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'desc_tip' => __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_custom_out_of_stock',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:100%;height:100px;',
	),
	array(
		'title'    => __( 'Custom "Out of Stock" Class', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_custom_out_of_stock_class_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'       => 'wcj_stock_custom_out_of_stock_class',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_stock_custom_out_of_stock_options',
	),
	array(
		'title'    => __( 'Custom Stock HTML', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_stock_custom_stock_html_options',
	),
	array(
		'title'    => __( 'Custom Stock HTML', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_stock_custom_stock_html_section_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc_tip' => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'HTML', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%class%', '%availability%' ) ) . '. ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_stock_custom_stock_html',
		'default'  => '<p class="stock %class%">%availability%</p>',
		'type'     => 'textarea',
		'css'      => 'width:100%;height:100px;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_stock_custom_stock_html_options',
	),
	array(
		'title'    => __( 'More Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_stock_more_options',
	),
	array(
		'title'    => __( 'Remove Stock Display', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will remove stock display from frontend.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'desc'     => __( 'Remove', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_remove_frontend_display_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_stock_more_options',
	),
);
