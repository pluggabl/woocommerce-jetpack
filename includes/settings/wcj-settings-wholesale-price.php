<?php
/**
 * Booster for WooCommerce - Settings - Wholesale Price
 *
 * @version 7.1.8
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'wcj_before_get_products', $this->id );
do_action( 'wcj_before_get_terms', $this->id );
$product_cats = wcj_get_terms( 'product_cat' );
$products     = wcj_get_products();


$settings = array(
	array(
		'id'   => 'wholesale_price_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wholesale_price_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wholesale_price_general_options_tab'          => __( 'General Options', 'woocommerce-jetpack' ),
			'wholesale_price_template_variables_tab'       => __( 'Template Variables', 'woocommerce-jetpack' ),
			'wholesale_price_wholesale_levels_options_tab' => __( 'Wholesale Levels Options', 'woocommerce-jetpack' ),
			'wholesale_price_additional_user_role_options_tab' => __( 'Additional User Roles Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wholesale_price_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => sprintf(
		/* translators: %s: translators Added */
			__( 'If you want to display prices table on frontend, use %s shortcode.', 'woocommerce-jetpack' ),
			'<code>[wcj_product_wholesale_price_table]</code>'
		),
		'id'    => 'wcj_wholesale_price_general_options',
	),
	array(
		'title'    => __( 'Table Heading Format', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'The <code>heading_format</code> param from <code>[wcj_product_wholesale_price_table]</code> shortcode will replace this value as long it\'s different from the default.', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%level_min_qty%' ) ),
		'id'       => 'wcj_wholesale_price_table_sc_title_format',
		'default'  => 'from %level_min_qty% pcs.',
		'type'     => 'text',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Enable per Product', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add new meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_per_product_enable',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Quantity Calculation', 'woocommerce-jetpack' ),
		'id'      => 'wcj_wholesale_price_use_total_cart_quantity',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'no'              => __( 'Product quantity', 'woocommerce-jetpack' ),
			'total_wholesale' => __( 'Total cart quantity (wholesale products only)', 'woocommerce-jetpack' ),
			'yes'             => __( 'Total cart quantity', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Exclusive Use Only', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Apply wholesale discount only if no other cart discounts were applied.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_apply_only_if_no_other_discounts',
		'default'  => 'no',
		'type'     => 'checkbox',
	),

	array(
		'title'    => __( 'Convert Price by Country Module Use Only', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Apply price directly method use in wholesale discount on cart page when you are using Price by Country Module ', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_apply_only_if_price_country_currency_price_directly',
		'default'  => 'no',
		'type'     => 'checkbox',
	),

	array(
		'title'    => __( 'Round Single Product Price', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If enabled will round single product price with precision set in WooCommerce > Settings > General > Number of decimals.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_rounding_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Discount Info on Cart Page', 'woocommerce-jetpack' ),
		'desc'    => __( 'Show', 'woocommerce-jetpack' ),
		'id'      => 'wcj_wholesale_price_show_info_on_cart',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'desc_tip' => __( 'If show discount info on cart page is enabled, set format here.', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%old_price%', '%original_price%', '%price%', '%discount_value%' ) ),
		'id'       => 'wcj_wholesale_price_show_info_on_cart_format',
		'default'  => '<del>%old_price%</del> %price%<br>You save: <span style="color:red;">%discount_value%</span>',
		'type'     => 'textarea',
		'css'      => 'width:100%;',
	),
	array(
		'title'   => __( 'Discount Type', 'woocommerce-jetpack' ),
		'id'      => 'wcj_wholesale_price_discount_type',
		'default' => 'percent',
		'type'    => 'select',
		'options' => array(
			'percent' => __( 'Percent', 'woocommerce-jetpack' ),
			'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
		),
	),
	wcj_get_ajax_settings(
		array(
			'title'   => __( 'Products to Include', 'woocommerce-jetpack' ),
			'desc'    => __( 'Leave blank to include all products.', 'woocommerce-jetpack' ),
			'id'      => 'wcj_wholesale_price_products_to_include',
			'default' => '',
		),
		true
	),
	wcj_get_ajax_settings(
		array(
			'title'   => __( 'Products to Exclude', 'woocommerce-jetpack' ),
			'id'      => 'wcj_wholesale_price_products_to_exclude',
			'default' => '',
		),
		true
	),
	array(
		'title'    => __( 'Products to Exclude forcefully', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable the option if you encounter any difficulties with Product Exclusion.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_products_to_exclude_forcefully',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Product Categories to Include', 'woocommerce-jetpack' ),
		'desc'    => __( 'Leave blank to include all products.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_wholesale_price_product_cats_to_include',
		'default' => '',
		'type'    => 'multiselect',
		'class'   => 'chosen_select',
		'options' => $product_cats,
	),
	array(
		'title'   => __( 'Product Categories to Exclude', 'woocommerce-jetpack' ),
		'id'      => 'wcj_wholesale_price_product_cats_to_exclude',
		'default' => '',
		'type'    => 'multiselect',
		'class'   => 'chosen_select',
		'options' => $product_cats,
	),
	array(
		'title'    => __( 'Product Categories to Exclude forcefully', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable the option if you encounter any difficulties with Product Categories Exclusion.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_product_cats_to_exclude_forcefully',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Advanced: Price Changes', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable wholesale pricing for products with "Price Changes"', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Try enabling this checkbox, if you are having compatibility issues with other plugins.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_check_for_product_changes_price',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Advanced: Price Filters Priority', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Priority for all module\'s price filters. If you face pricing issues while using another plugin or booster module, You can change the Priority, Greater value for high priority & Lower value for low priority. Set to zero to use default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wholesale_price_advanced_price_hooks_priority',
		'default'  => 0,
		'type'     => 'number',
	),
	$this->get_wpml_terms_in_all_languages_setting(),
	$this->get_wpml_products_in_all_languages_setting(),
	array(
		'id'   => 'wcj_wholesale_price_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wholesale_price_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wholesale_price_template_variables_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Template Variables', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Options regarding template variables', 'woocommerce-jetpack' ),
		'id'    => 'wcj_wholesale_price_template_vars',
	),
	array(
		'title'             => __( 'Discount Value - Fixed Discount Totals', 'woocommerce-jetpack' ),
		'id'                => 'wcj_wholesale_price_template_vars_discount_value_fdt',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		/* translators: %discount_value%: translators Added */
		'desc_tip'          => __( 'Defines how the <code>%discount_value%</code> will calculate the totals when the fixed discount is in use.', 'woocommerce-jetpack' ),
		'default'           => 'do_not_consider_qty',
		'type'              => 'select',
		'options'           => array(
			'do_not_consider_qty' => __( 'Do not consider quantity', 'woocommerce-jetpack' ),
			'consider_qty'        => __( 'Consider quantity', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'             => __( 'Discount Value - Price Directly Totals', 'woocommerce-jetpack' ),
		'id'                => 'wcj_wholesale_price_template_vars_discount_value_pdt',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		/* translators: %discount_value%: translators Added */
		'desc_tip'          => __( 'Defines how the <code>%discount_value%</code> will calculate the totals when the price directly is in use.', 'woocommerce-jetpack' ),
		'default'           => 'do_not_consider_qty',
		'type'              => 'select',
		'options'           => array(
			'do_not_consider_qty' => __( 'Do not consider quantity', 'woocommerce-jetpack' ),
			'consider_qty'        => __( 'Consider quantity', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_wholesale_price_template_vars',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wholesale_price_template_variables_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wholesale_price_wholesale_levels_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Wholesale Levels Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_wholesale_price_level_options',
	),
	array(
		'title'             => __( 'Number of Levels', 'woocommerce-jetpack' ),
		'id'                => 'wcj_wholesale_price_levels_number',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array(
				'step' => '1',
				'min'  => '1',
			)
		),
		'css'               => 'width:100px;',
	),
	array(
		'title'   => __( 'Default Max Qty Level', 'woocommerce-jetpack' ),
		'id'      => 'wcj_wholesale_price_max_qty_level',
		'default' => 1,
		'type'    => 'number',
		'css'     => 'width:100px;',
	),
);
$wholesale_price_levels_number = apply_filters( 'booster_option', 1, get_option( 'wcj_wholesale_price_levels_number', 1 ) );
for ( $i = 1; $i <= $wholesale_price_levels_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'             => __( 'Min Quantity', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'              => __( 'Minimum quantity to apply discount', 'woocommerce-jetpack' ),
				'id'                => 'wcj_wholesale_price_level_min_qty_' . $i,
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
				),
			),
			array(
				'title'             => __( 'Discount', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'              => __( 'Discount', 'woocommerce-jetpack' ),
				'id'                => 'wcj_wholesale_price_level_discount_percent_' . $i, // mislabeled - should be 'wcj_wholesale_price_level_discount_'.
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => array( 'step' => '0.0001' ),
			),
		)
	);
}
$settings   = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_wholesale_price_level_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wholesale_price_wholesale_levels_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wholesale_price_additional_user_role_options_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Additional User Roles Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'If you want to set different wholesale pricing options for different user roles, fill this section. Please note that you can also use Booster\'s "Price based on User Role" module without filling this section.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_wholesale_price_by_user_role_options',
		),
		array(
			'title'   => __( 'User Roles Settings', 'woocommerce-jetpack' ),
			'desc'    => __( 'Save settings after you change this option. Leave blank to disable.', 'woocommerce-jetpack' ),
			'type'    => 'multiselect',
			'id'      => 'wcj_wholesale_price_by_user_role_roles',
			'default' => '',
			'class'   => 'chosen_select',
			'options' => wcj_get_user_roles_options(),
		),
	)
);
$user_roles = wcj_get_option( 'wcj_wholesale_price_by_user_role_roles', '' );
if ( ! empty( $user_roles ) ) {
	foreach ( $user_roles as $user_role_key ) {
		$settings            = array_merge(
			$settings,
			array(
				array(
					'title'             => __( 'Number of Levels', 'woocommerce-jetpack' ) . ' [' . $user_role_key . ']',
					'id'                => 'wcj_wholesale_price_levels_number_' . $user_role_key,
					'default'           => 1,
					'type'              => 'custom_number',
					'desc'              => apply_filters( 'booster_message', '', 'desc' ),
					'custom_attributes' => array_merge(
						is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
						array(
							'step' => '1',
							'min'  => '1',
						)
					),
					'css'               => 'width:100px;',
				),
			)
		);
		$wholesale_price_num = apply_filters( 'booster_option', 1, get_option( 'wcj_wholesale_price_levels_number_' . $user_role_key, 1 ) );
		for ( $i = 1; $i <= $wholesale_price_num; $i++ ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title'             => __( 'Min Quantity', 'woocommerce-jetpack' ) . ' #' . $i . ' [' . $user_role_key . ']',
						'desc'              => __( 'Minimum quantity to apply discount', 'woocommerce-jetpack' ),
						'id'                => 'wcj_wholesale_price_level_min_qty_' . $user_role_key . '_' . $i,
						'default'           => 0,
						'type'              => 'number',
						'custom_attributes' => array(
							'step' => '1',
							'min'  => '0',
						),
					),
					array(
						'title'             => __( 'Discount', 'woocommerce-jetpack' ) . ' #' . $i . ' [' . $user_role_key . ']',
						'desc'              => __( 'Discount', 'woocommerce-jetpack' ),
						'id'                => 'wcj_wholesale_price_level_discount_percent_' . $user_role_key . '_' . $i, // mislabeled - should be 'wcj_wholesale_price_level_discount_'.
						'default'           => 0,
						'type'              => 'number',
						'custom_attributes' => array( 'step' => '0.0001' ),
					),
				)
			);
		}
	}
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_wholesale_price_by_user_role_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wholesale_price_additional_user_role_options_tab',
			'type' => 'tab_end',
		),
	)
);
do_action( 'wcj_after_get_products', $this->id );
do_action( 'wcj_after_get_terms', $this->id );
return $settings;
