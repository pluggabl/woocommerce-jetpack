<?php
/**
 * Booster for WooCommerce - Settings - Wholesale Price
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$products = wcj_get_products();
$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'Wholesale Price Levels Options. If you want to display prices table on frontend, use [wcj_product_wholesale_price_table] shortcode.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_general_options',
	),
	array(
		'title'    => __( 'Enable per Product', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_per_product_enable',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Use total cart quantity instead of product quantity', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_use_total_cart_quantity',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Apply wholesale discount only if no other cart discounts were applied', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_apply_only_if_no_other_discounts',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Round single product price', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If enabled will round single product price with precision set in WooCommerce > Settings > General > Number of decimals.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_rounding_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Show discount info on cart page', 'woocommerce-jetpack' ),
		'desc'     => __( 'Show', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_show_info_on_cart',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'If show discount info on cart page is enabled, set format here', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%old_price%', '%price%', '%discount_value%' ) ),
		'id'       => 'wcj_wholesale_price_show_info_on_cart_format',
		'default'  => '<del>%old_price%</del> %price%<br>You save: <span style="color:red;">%discount_value%</span>',
		'type'     => 'textarea',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Discount Type', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_discount_type',
		'default'  => 'percent',
		'type'     => 'select',
		'options'  => array(
			'percent' => __( 'Percent', 'woocommerce-jetpack' ),
			'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Products to include', 'woocommerce-jetpack' ),
		'desc'     => __( 'Leave blank to include all products.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_products_to_include',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $products,
	),
	array(
		'title'    => __( 'Products to exclude', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_products_to_exclude',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $products,
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_wholesale_price_general_options',
	),
	array(
		'title'    => __( 'Wholesale Levels Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_wholesale_price_level_options',
	),
	array(
		'title'    => __( 'Number of levels', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_levels_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array('step' => '1', 'min' => '1', ) ),
		'css'      => 'width:100px;',
	),
);
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_wholesale_price_levels_number', 1 ) ); $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Min quantity', 'woocommerce-jetpack' ) . ' #' . $i,
			'desc'     => __( 'Minimum quantity to apply discount', 'woocommerce-jetpack' ),
			'id'       => 'wcj_wholesale_price_level_min_qty_' . $i,
			'default'  => 0,
			'type'     => 'number',
			'custom_attributes' => array('step' => '1', 'min' => '0', ),
		),
		array(
			'title'    => __( 'Discount', 'woocommerce-jetpack' ) . ' #' . $i,
			'desc'     => __( 'Discount', 'woocommerce-jetpack' ),
			'id'       => 'wcj_wholesale_price_level_discount_percent_' . $i, // mislabeled - should be 'wcj_wholesale_price_level_discount_'
			'default'  => 0,
			'type'     => 'number',
			'custom_attributes' => array('step' => '0.0001', /* 'min' => '0', */ ),
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_wholesale_price_level_options',
	),
	array(
		'title'    => __( 'Additional User Roles Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'If you want to set different wholesale pricing options for different user roles, fill this section. Please note that you can also use Booster\'s "Price by User Role" module without filling this section.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_by_user_role_options',
	),
	array(
		'title'    => __( 'User Roles Settings', 'woocommerce-jetpack' ),
		'desc'     => __( 'Save settings after you change this option. Leave blank to disable.', 'woocommerce-jetpack' ),
		'type'     => 'multiselect',
		'id'       => 'wcj_wholesale_price_by_user_role_roles',
		'default'  => '',
		'class'    => 'chosen_select',
		'options'  => wcj_get_user_roles_options(),
	),
) );
$user_roles = get_option( 'wcj_wholesale_price_by_user_role_roles', '' );
if ( ! empty( $user_roles ) ) {
	foreach ( $user_roles as $user_role_key ) {
		$settings = array_merge( $settings, array(
			array(
				'title'   => __( 'Number of levels', 'woocommerce-jetpack' ) . ' [' . $user_role_key . ']',
				'id'      => 'wcj_wholesale_price_levels_number_' . $user_role_key,
				'default' => 1,
				'type'    => 'custom_number',
				'desc'    => apply_filters( 'booster_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
					array('step' => '1', 'min' => '1', ) ),
				'css'     => 'width:100px;',
			),
		) );
		for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_wholesale_price_levels_number_' . $user_role_key, 1 ) ); $i++ ) {
			$settings = array_merge( $settings, array(
				array(
					'title'   => __( 'Min quantity', 'woocommerce-jetpack' ) . ' #' . $i . ' [' . $user_role_key . ']',
					'desc'    => __( 'Minimum quantity to apply discount', 'woocommerce-jetpack' ),
					'id'      => 'wcj_wholesale_price_level_min_qty_' . $user_role_key . '_' . $i,
					'default' => 0,
					'type'    => 'number',
					'custom_attributes' => array('step' => '1', 'min' => '0', ),
				),
				array(
					'title'   => __( 'Discount', 'woocommerce-jetpack' ) . ' #' . $i . ' [' . $user_role_key . ']',
					'desc'    => __( 'Discount', 'woocommerce-jetpack' ),
					'id'      => 'wcj_wholesale_price_level_discount_percent_' . $user_role_key . '_' . $i, // mislabeled - should be 'wcj_wholesale_price_level_discount_'
					'default' => 0,
					'type'    => 'number',
					'custom_attributes' => array('step' => '0.0001', /* 'min' => '0', */ ),
				),
			) );
		}
	}
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_wholesale_price_by_user_role_options',
	),
) );
return $settings;
