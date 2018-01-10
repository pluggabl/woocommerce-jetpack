<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Addons
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$options = array(
	array(
		'name'       => 'wcj_product_addons_per_product_settings_enabled',
		'default'    => 'no',
		'type'       => 'select',
		'options'    => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
		'title'      => __( 'Enabled', 'woocommerce-jetpack' ),
	),
	array(
		'name'       => 'wcj_product_addons_per_product_total_number',
		'tooltip'    => __( 'Save product after you change this number.', 'woocommerce-jetpack' ),
		'default'    => 0,
		'type'       => 'number',
		'title'      => __( 'Product Addons Total Number', 'woocommerce-jetpack' ),
	),
);
$total_number = get_post_meta( get_the_ID(), '_' . 'wcj_product_addons_per_product_total_number', true );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$options = array_merge( $options, array(
		array(
			'title'    => __( 'Product Addon', 'woocommerce-jetpack' ) . ' #' . $i . ' - ' . __( 'Enable', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_addons_per_product_enabled_' . $i,
			'default'  => 'yes',
			'type'     => 'select',
			'options'  => array(
				'yes' => __( 'Yes', 'woocommerce-jetpack' ),
				'no'  => __( 'No', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'    => __( 'Type', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_addons_per_product_type_' . $i,
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
			'title'    => __( 'Title', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_addons_per_product_title_' . $i,
			'default'  => '',
			'type'     => 'textarea',
		),
		array(
			'title'    => __( 'Label(s)', 'woocommerce-jetpack' ),
			'tooltip'  => __( 'For radio and select enter one value per line.', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_addons_per_product_label_' . $i,
			'default'  => '',
			'type'     => 'textarea',
		),
		array(
			'title'    => __( 'Price(s)', 'woocommerce-jetpack' ),
			'tooltip'  => __( 'For radio and select enter one value per line.', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_addons_per_product_price_' . $i,
			'default'  => 0,
			'type'     => 'textarea',
		),
		array(
			'title'    => __( 'Tooltip(s)', 'woocommerce-jetpack' ),
			'tooltip'  => __( 'For radio enter one value per line.', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_addons_per_product_tooltip_' . $i,
			'default'  => '',
			'type'     => 'textarea',
		),
		array(
			'title'    => __( 'Default Value', 'woocommerce-jetpack' ),
			'tooltip'  => __( 'For checkbox use \'checked\'; for radio and select enter default label. Leave blank for no default value.', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_addons_per_product_default_' . $i,
			'default'  => '',
			'type'     => 'text',
		),
		array(
			'title'    => __( 'Placeholder', 'woocommerce-jetpack' ),
			'tooltip'  => __( 'For "Select Box" type only.', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_addons_per_product_placeholder_' . $i,
			'default'  => '',
			'type'     => 'text',
		),
		array(
			'title'    => __( 'Is required', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_addons_per_product_required_' . $i,
			'default'  => 'no',
			'type'     => 'select',
			'options'  => array(
				'yes' => __( 'Yes', 'woocommerce-jetpack' ),
				'no'  => __( 'No', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'    => __( 'Quantity', 'woocommerce-jetpack' ),
			'tooltip'  => __( 'Leave empty to disable quantity calculation for the addon. When set to zero - addon will be disabled.', 'woocommerce-jetpack' ),
			'name'     => 'wcj_product_addons_per_product_qty_' . $i,
			'default'  => '',
			'type'     => 'text',
		),
	) );
}
return $options;
