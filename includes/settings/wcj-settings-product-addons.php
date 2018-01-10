<?php
/**
 * Booster for WooCommerce - Settings - Product Addons
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    add "frontend template" options
 * @todo    (maybe) add `woocommerce_payment_complete` to `$qty_triggers` (also maybe add this trigger to "PDF Invoicing" module)
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$products = wcj_get_products();

$qty_triggers = array();
$qty_triggers['woocommerce_new_order'] = __( 'New order', 'woocommerce-jetpack' );
$order_statuses = wcj_get_order_statuses();
foreach ( $order_statuses as $status => $desc ) {
	$qty_triggers[ 'woocommerce_order_status_' . $status ] = sprintf( __( 'Order status "%s"', 'woocommerce-jetpack' ), $desc );
}

$settings = array();
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Per Product Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_addons_per_product_options',
	),
	array(
		'title'    => __( 'Enable per Product Addons', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled, this will add new "Booster: Product Addons" meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_per_product_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_addons_per_product_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'All Product Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_addons_all_products_options',
	),
	array(
		'title'    => __( 'Enable All Products Addons', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled, this will add addons below to all products.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_all_products_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Product Addons Total Number', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Save changes after you change this number.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_all_products_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '0', )
		),
	),
) );
$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_product_addons_all_products_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Product Addon', 'woocommerce-jetpack' ) . ' #' . $i,
			'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_enabled_' . $i,
			'default'  => 'yes',
			'type'     => 'checkbox',
		),
		array(
			'desc'     => __( 'Type', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_type_' . $i,
			'default'  => 'checkbox',
			'type'     => 'select',
			'options'  => array(
				'checkbox' => __( 'Checkbox', 'woocommerce-jetpack' ),
				'radio'    => __( 'Radio Buttons', 'woocommerce-jetpack' ),
				'select'   => __( 'Select Box', 'woocommerce-jetpack' ),
				'text'     => __( 'Text', 'woocommerce-jetpack' ),
			),
		),
		array(
			'desc'     => __( 'Title', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_title_' . $i,
			'default'  => '',
			'type'     => 'textarea',
		),
		array(
			'desc'     => __( 'Label(s)', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'For radio and select enter one value per line.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_label_' . $i,
			'default'  => '',
			'type'     => 'textarea',
		),
		array(
			'desc'     => __( 'Price(s)', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'For radio and select enter one value per line.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_price_' . $i,
			'default'  => 0,
			'type'     => 'textarea',
			'custom_attributes' => array( 'step' => '0.0001' ),
		),
		array(
			'desc'     => __( 'Tooltip(s)', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'For radio enter one value per line.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_tooltip_' . $i,
			'default'  => '',
			'type'     => 'textarea',
		),
		array(
			'desc'     => __( 'Default Value', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'For checkbox use \'checked\'; for radio and select enter default label. Leave blank for no default value.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_default_' . $i,
			'default'  => '',
			'type'     => 'text',
		),
		array(
			'desc'    => __( 'Placeholder', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'For "Select Box" type only.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_placeholder_' . $i,
			'default'  => '',
			'type'     => 'text',
		),
		array(
			'desc'     => __( 'Is Required', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_required_' . $i,
			'default'  => 'no',
			'type'     => 'checkbox',
		),
		array(
			'desc'     => __( 'Exclude Products', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_exclude_products_' . $i,
			'default'  => '',
			'type'     => 'multiselect',
			'class'    => 'chosen_select',
			'options'  => $products,
		),
		array(
			'desc'     => __( 'Quantity', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave empty to disable quantity calculation for the addon. When set to zero - addon will be disabled.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_addons_all_products_qty_' . $i,
			'default'  => '',
			'type'     => 'text',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_addons_all_products_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_addons_options',
	),
	array(
		'title'    => __( 'Enable AJAX on Single Product Page', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_ajax_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Decrease Quantity', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If you select multiple triggers to decrease quantity, it will be decreased only once (on whichever trigger is executed first).', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_qty_decrease_triggers',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $qty_triggers,
	),
	array(
		'title'    => __( 'Addon in Cart Format', 'woocommerce-jetpack' ),
		'desc'     => __( 'Before', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_cart_format_start',
		'default'  => '<dl class="variation">',
		'type'     => 'textarea',
	),
	array(
		'desc'     => __( 'Each Addon', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use %addon_label% and %addon_price%.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_cart_format_each_addon',
		'default'  => '<dt>%addon_label%:</dt><dd>%addon_price%</dd>',
		'type'     => 'textarea',
	),
	array(
		'desc'     => __( 'After', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_cart_format_end',
		'default'  => '</dl>',
		'type'     => 'textarea',
	),
	array(
		'title'    => __( 'Addon in Order Details Table Format', 'woocommerce-jetpack' ),
		'desc'     => __( 'Before', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_order_details_format_start',
		'default'  => '',
		'type'     => 'textarea',
	),
	array(
		'desc'     => __( 'Each Addon', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use %addon_label% and %addon_price%.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_order_details_format_each_addon',
		'default'  => '&nbsp;| %addon_label%: %addon_price%',
		'type'     => 'textarea',
	),
	array(
		'desc'     => __( 'After', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_order_details_format_end',
		'default'  => '',
		'type'     => 'textarea',
	),
	array(
		'title'    => __( 'Admin Order Page', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide all addons', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_hide_on_admin_order_page',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Position Priority on Frontend', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero to use the default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_addons_position_priority',
		'default'  => 0,
		'type'     => 'number',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_addons_options',
	),
) );
return $settings;
