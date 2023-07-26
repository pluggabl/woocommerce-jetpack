<?php
/**
 * Booster for WooCommerce - Settings - Product Price by Formula
 *
 * @version 7.0.0
 * @since   2.8.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings     = array(
	array(
		'id'   => 'product_price_by_formula_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'product_price_by_formula_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'product_price_by_formula_default_settings_tab' => __( 'Default Settings', 'woocommerce-jetpack' ),
			'product_price_by_formula_general_settings_tab' => __( 'General Settings', 'woocommerce-jetpack' ),
			'product_price_by_formula_advanced_settings_tab' => __( 'Advanced Settings', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'product_price_by_formula_default_settings_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Default Settings', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'You can set default settings here. All settings can later be changed in individual product\'s edit page.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_product_price_by_formula_options',
	),
	array(
		'title'   => __( 'Formula', 'woocommerce-jetpack' ),
		'desc'    => sprintf(
			/* translators: %1$s,%2$s: translators Added */
			__( 'Use %1$s variable for product\'s base price. For example: %2$s.', 'woocommerce-jetpack' ),
			'<code> x </code>',
			'<code> x+p1*p2 </code>'
		),
		'type'    => 'text',
		'id'      => 'wcj_product_price_by_formula_eval',
		'default' => '',
		'class'   => 'widefat',
	),
	array(
		'title'             => __( 'Enable Price Calculation By Formula For All Products', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'type'              => 'checkbox',
		'id'                => 'wcj_product_price_by_formula_enable_for_all_products',
		'default'           => 'no',
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc_no_link' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'   => __( 'Total Params', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_price_by_formula_total_params',
		'default' => 1,
		'type'    => 'custom_number',
	),
);
$total_number = wcj_get_option( 'wcj_product_price_by_formula_total_params', 1 );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings[] = array(
		'title'   => 'p' . $i,
		'id'      => 'wcj_product_price_by_formula_param_' . $i,
		'default' => '',
		'type'    => 'text',
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_product_price_by_formula_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'product_price_by_formula_default_settings_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'product_price_by_formula_general_settings_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'General Settings', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_product_price_by_formula_general_options',
		),
		array(
			'title'   => __( 'Rounding', 'woocommerce-jetpack' ),
			'type'    => 'select',
			'id'      => 'wcj_product_price_by_formula_rounding',
			'default' => 'no_rounding',
			'options' => array(
				'no_rounding' => __( 'No rounding (disabled)', 'woocommerce-jetpack' ),
				'round'       => __( 'Round', 'woocommerce-jetpack' ),
				'wcj_ceil'    => __( 'Round up', 'woocommerce-jetpack' ),
				'wcj_floor'   => __( 'Round down', 'woocommerce-jetpack' ),
			),
		),
		array(
			'desc'              => __( 'rounding precision', 'woocommerce-jetpack' ),
			'type'              => 'number',
			'id'                => 'wcj_product_price_by_formula_rounding_precision',
			'default'           => 0,
			'custom_attributes' => array( 'min' => 0 ),
		),
		array(
			'title'    => __( 'Compatible With Woococommerce Booking Plugin', 'woocommerce-jetpack' ),
			'desc'     => __( 'Enable on Woocommerce Booking Module', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'If you are facing pricing issue after enable Woocommerce Booking plugin please enable  the section', 'woocommerce-jetpack' ) . '<br />',
			'type'     => 'checkbox',
			'id'       => 'wcj_product_price_by_formula_woo_booking_plugin',
			'default'  => 'no',
		),

		array(
			'title'    => __( 'Promotional pricing issue', 'woocommerce-jetpack' ),
			'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'If you are facing a promotional  pricing issue in booking products then enable the section', 'woocommerce-jetpack' ) . '<br />',
			'type'     => 'checkbox',
			'id'       => 'wcj_product_price_by_formula_woo_booking_promotional',
			'default'  => 'no',
		),
		array(
			'title'    => __( 'Disable Admin Scope', 'woocommerce-jetpack' ),
			'desc'     => __( 'Disable module on Admin scope.', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Disable if you want to use ‘Product Price by Formula’ module only on Frontend.', 'woocommerce-jetpack' ) . '<br />' . __( 'For example if you use ‘Cost of Goods’ module the profit will be correctly calculated if you leave the box unticked', 'woocommerce-jetpack' ),
			'type'     => 'checkbox',
			'id'       => 'wcj_product_price_by_formula_admin_scope',
			'default'  => 'yes',
		),
		array(
			'title'    => __( 'Disable Quick Edit Product For Admin Scope', 'woocommerce-jetpack' ),
			'desc'     => __( 'Disable For Admin Quick Edit Scope.', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Disable if you are facing any compatibility issue in product quick/bulk edit. ', 'woocommerce-jetpack' ) . '<br />' . __( 'For example if you use Quick Edit Product  and donot want change the deafult price then the  box ticked', 'woocommerce-jetpack' ),
			'type'     => 'checkbox',
			'id'       => 'wcj_product_price_by_formula_admin_quick_edit_product_scope',
			'default'  => 'no',
		),
		array(
			'id'   => 'wcj_product_price_by_formula_general_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'product_price_by_formula_general_settings_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'product_price_by_formula_advanced_settings_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Advanced Settings', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_product_price_by_formula_advanced_options',
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
			'title'             => __( 'Reset products', 'woocommerce-jetpack' ),
			'desc_tip'          => __( "If enabled will also reset formula related settings on products when <code>Reset Settings</code> button is pressed.<br>It's necessary to <code>Save changes</code> first with the option enabled so the <code>Reset settings</code> can be pressed.", 'woocommerce-jetpack' ),
			'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			'id'                => 'wcj_product_price_by_formula_reset_products',
			'default'           => 'no',
			'type'              => 'checkbox',
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		),
		array(
			'id'   => 'wcj_product_price_by_formula_advanced_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'product_price_by_formula_advanced_settings_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
