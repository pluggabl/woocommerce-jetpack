<?php
/**
 * Booster for WooCommerce - Settings - Stock
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'id'   => 'wcj_product_stock_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_product_stock_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_product_stock_in_stock_tab'              => __( 'In Stock', 'woocommerce-jetpack' ),
			'wcj_product_stock_out_of_stock_tab'          => __( 'Out of Stock', 'woocommerce-jetpack' ),
			'wcj_product_stock_available_on_bacorder_tab' => __( 'Available on backorder', 'woocommerce-jetpack' ),
			'wcj_product_stock_custom_html_tab'           => __( 'Custom Stock HTML', 'woocommerce-jetpack' ),
			'wcj_product_stock_more_options_tab'          => __( 'More Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_product_stock_in_stock_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Custom "In Stock" Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_stock_custom_in_stock_options',
	),
	array(
		'title'   => __( 'Custom "In Stock"', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_stock_custom_in_stock_section_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Custom "In Stock" Text', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_stock_custom_in_stock_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'desc'     => __( '"In Stock" text.', 'woocommerce-jetpack' ) . ' ' .
				/* translators: %s: translators Added */
			sprintf( __( 'If needed, use %s to insert stock quantity.', 'woocommerce-jetpack' ), '<code>%s</code>' ),
		'desc_tip' => __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_custom_in_stock',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;height:100px;',
	),
	array(
		'desc'     => __( '"Low amount" text.', 'woocommerce-jetpack' ) . ' ' . __( 'Ignored if empty.', 'woocommerce-jetpack' ) . ' ' .
				/* translators: %s: translators Added */
			sprintf( __( 'If needed, use %s to insert stock quantity.', 'woocommerce-jetpack' ), '<code>%s</code>' ),
		'desc_tip' => __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ) . ' ' .

							/* translators: %1$s:%2$s:%3$s: translators Added */
				__( 'Used only if <em>Only show quantity remaining in stock when low</em> is selected for <em>Stock display format</em> in <em>WooCommerce > Settings > Products > Inventory</em>.', 'woocommerce-jetpack' ),
		'<em>' . __( 'Only show quantity remaining in stock when low', 'woocommerce-jetpack' ) . '</em>',
		'<em>' . __( 'Stock display format', 'woocommerce' ) . '</em>',
		'<em>' . __( 'WooCommerce &gt Settings &gt Products &gt Inventory', 'woocommerce-jetpack' )
		. '</em>',
		'id'       => 'wcj_stock_custom_in_stock_low_amount',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;height:100px;',
	),
	array(
		'desc'     => __( '"Can be backordered" text.', 'woocommerce-jetpack' ) . ' ' . __( 'Ignored if empty.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_stock_custom_in_stock_can_be_backordered',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;height:100px;',
	),
	array(
		'title'   => __( 'Custom "In Stock" Class', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_stock_custom_in_stock_class_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		/* translators: %s: translators Added */
		'desc'    => sprintf( __( 'Default: %s.', 'woocommerce-jetpack' ), '<code>in-stock</code>' ),
		'css'     => 'width:100%;',
		'id'      => 'wcj_stock_custom_in_stock_class',
		'default' => '',
		'type'    => 'text',
	),
	array(
		'id'   => 'wcj_stock_custom_in_stock_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_stock_in_stock_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_product_stock_out_of_stock_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Custom "Out of Stock" Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_stock_custom_out_of_stock_options',
	),
	array(
		'title'   => __( 'Custom "Out of Stock"', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_stock_custom_out_of_stock_section_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Custom "Out of Stock" Text', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_stock_custom_out_of_stock_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'desc_tip' => __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ),
		/* translators: %s: translators Added */
		'desc'     => sprintf( __( 'Default: %s.', 'woocommerce-jetpack' ), '<code>' . __( 'Out of stock', 'woocommerce' ) . '</code>' ),
		'id'       => 'wcj_stock_custom_out_of_stock',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;height:100px;',
	),
	array(
		'title'   => __( 'Custom "Out of Stock" Class', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_stock_custom_out_of_stock_class_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		/* translators: %s: translators Added */
		'desc'    => sprintf( __( 'Default: %s.', 'woocommerce-jetpack' ), '<code>out-of-stock</code>' ),
		'css'     => 'width:100%;',
		'id'      => 'wcj_stock_custom_out_of_stock_class',
		'default' => '',
		'type'    => 'text',
	),
	array(
		'id'   => 'wcj_stock_custom_out_of_stock_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_stock_out_of_stock_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_product_stock_available_on_bacorder_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Custom "Available on backorder" Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_stock_custom_backorder_options',
		'desc'  => __( 'This option is used if the "Allow backorders?" is "Allow, but notify customer" in the product.', 'woocommerce-jetpack' ),
	),
	array(
		'title'   => __( 'Custom "Available on backorder"', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_stock_custom_backorder_section_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Custom "Available on backorder" Text', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_stock_custom_backorder_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'desc_tip' => __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ),
		/* translators: %s: translators Added */
		'desc'     => sprintf( __( 'Default: %s or empty string.', 'woocommerce-jetpack' ), '<code>' . __( 'Available on backorder', 'woocommerce' ) . '</code>' ),
		'id'       => 'wcj_stock_custom_backorder',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;height:100px;',
	),
	array(
		'title'   => __( 'Custom "Available on backorder" Class', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_stock_custom_backorder_class_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		/* translators: %s: translators Added */
		'desc'    => sprintf( __( 'Default: %s.', 'woocommerce-jetpack' ), '<code>available-on-backorder</code>' ),
		'css'     => 'width:100%;',
		'id'      => 'wcj_stock_custom_backorder_class',
		'default' => '',
		'type'    => 'text',
	),
	array(
		'id'   => 'wcj_stock_custom_backorder_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_stock_available_on_bacorder_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_product_stock_custom_html_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Custom Stock HTML', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_stock_custom_stock_html_options',
	),
	array(
		'title'             => __( 'Custom Stock HTML', 'woocommerce-jetpack' ),
		'desc'              => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'                => 'wcj_stock_custom_stock_html_section_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'HTML', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ),
		'desc'              => wcj_message_replaced_values( array( '%class%', '%availability%' ) ) . '. ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'                => 'wcj_stock_custom_stock_html',
		'default'           => '<p class="stock %class%">%availability%</p>',
		'type'              => 'textarea',
		'css'               => 'width:100%;height:100px;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'id'   => 'wcj_stock_custom_stock_html_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_stock_custom_html_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_product_stock_more_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'More Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_stock_more_options',
	),
	array(
		'title'             => __( 'Remove Stock Display', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'This will remove stock display from frontend.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'desc'              => __( 'Remove', 'woocommerce-jetpack' ),
		'id'                => 'wcj_stock_remove_frontend_display_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'   => 'wcj_stock_more_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_stock_more_options_tab',
		'type' => 'tab_end',
	),
);
