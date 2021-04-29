<?php
/**
 * Booster for WooCommerce - Settings - Product Price by Formula
 *
 * @version 5.4.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Default Settings', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'You can set default settings here. All settings can later be changed in individual product\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_price_by_formula_options',
	),
	array(
		'title'    => __( 'Formula', 'woocommerce-jetpack' ),
		'desc'     => sprintf( __( 'Use %s variable for product\'s base price. For example: %s.', 'woocommerce-jetpack' ),
			'<code>' . 'x' . '</code>', '<code>' . 'x+p1*p2' . '</code>' ),
		'type'     => 'text',
		'id'       => 'wcj_product_price_by_formula_eval',
		'default'  => '',
		'class'    => 'widefat',
	),
	array(
		'title'    => __( 'Enable Price Calculation By Formula For All Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_product_price_by_formula_enable_for_all_products',
		'default'  => 'no',
		'desc_tip' => apply_filters( 'booster_message', '', 'desc_no_link' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Total Params', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_price_by_formula_total_params',
		'default'  => 1,
		'type'     => 'custom_number',
	),
);
$total_number = wcj_get_option( 'wcj_product_price_by_formula_total_params', 1 );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings[] = array(
		'title'    => 'p' . $i,
		'id'       => 'wcj_product_price_by_formula_param_' . $i,
		'default'  => '',
		'type'     => 'text',
	);
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_price_by_formula_options',
	),
	array(
		'title'    => __( 'General Settings', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_price_by_formula_general_options',
	),
	array(
		'title'    => __( 'Rounding', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'id'       => 'wcj_product_price_by_formula_rounding',
		'default'  => 'no_rounding',
		'options'  => array(
			'no_rounding' => __( 'No rounding (disabled)', 'woocommerce-jetpack' ),
			'round'       => __( 'Round', 'woocommerce-jetpack' ),
			'wcj_ceil'    => __( 'Round up', 'woocommerce-jetpack' ),
			'wcj_floor'   => __( 'Round down', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'rounding precision', 'woocommerce-jetpack' ),
		'type'     => 'number',
		'id'       => 'wcj_product_price_by_formula_rounding_precision',
		'default'  => 0,
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'Disable Admin Scope', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable module on Admin scope.', 'woocommerce-jetpack' ),
		'desc_tip' => __( "Disable if you want to use ‘Product Price by Formula’ module only on Frontend.", 'woocommerce-jetpack' ) . '<br />' . __( 'For example if you use ‘Cost of Goods’ module the profit will be correctly calculated if you leave the box unticked', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_product_price_by_formula_admin_scope',
		'default'  => 'yes',
	),
	array(
        'title' => __('Disable Quick Edit Product For Admin Scope', 'woocommerce-jetpack'),
        'desc' => __('Disable For Admin Quick Edit Scope.', 'woocommerce-jetpack'),
        'desc_tip' => __("Disable if you are facing any compatibility issue in product quick/bulk edit. ", 'woocommerce-jetpack') . '<br />' . __('For example if you use Quick Edit Product  and donot want change the deafult price then the  box ticked', 'woocommerce-jetpack'),
        'type' => 'checkbox',
        'id' => 'wcj_product_price_by_formula_admin_quick_edit_product_scope',
        'default' => 'no',
    ),


	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_price_by_formula_general_options',
	),
	array(
		'title'    => __( 'Advanced Settings', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_price_by_formula_advanced_options',
	),
	array(
		'title'    => __( 'Price Filters Priority', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Priority for all module\'s price filters. If you face pricing issues while using another plugin or booster module, You can change the Priority, Greater value for high priority & Lower value for low priority. Set to zero to use default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_price_by_formula_advanced_price_hooks_priority',
		'default'  => 0,
		'type'     => 'number',
	),
	array(
		'title'    => __( 'Save Calculated Products Prices', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This may help if you are experiencing compatibility issues with other plugins.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_price_by_formula_save_prices',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_price_by_formula_advanced_options',
	),
) );
return $settings;
