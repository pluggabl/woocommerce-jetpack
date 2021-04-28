<?php
/**
 * Booster for WooCommerce - Settings - Global Discount
 *
 * @version 5.4.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$is_multiselect_products     = ( 'yes' === wcj_get_option( 'wcj_list_for_products', 'yes' ) );

do_action( 'wcj_before_get_terms', $this->id );
do_action( 'wcj_before_get_products', $this->id );
$products     = ( $is_multiselect_products ? wcj_get_products() : false );
do_action( 'wcj_after_get_products', $this->id );
$product_cats = wcj_get_terms( 'product_cat' );
$product_tags = wcj_get_terms( 'product_tag' );
do_action( 'wcj_after_get_terms', $this->id );

$settings = array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_global_discount_options',
	),
	array(
		'title'    => __( 'Total Groups', 'woocommerce-jetpack' ),
		'id'       => 'wcj_global_discount_groups_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc_tip' => __( 'Press Save changes after you change this number.', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ?
			apply_filters( 'booster_message', '', 'readonly' ) : array( 'step' => '1', 'min'  => '1', ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_global_discount_options',
	),
);
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_global_discount_groups_total_number', 1 ) ); $i++ ) {
	wcj_maybe_convert_and_update_option_value( array(
		array( 'id' => 'wcj_global_discount_sale_products_incl_' . $i, 'default' => '' ),
		array( 'id' => 'wcj_global_discount_sale_products_excl_' . $i, 'default' => '' ),
	), $is_multiselect_products );
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Discount Group', 'woocommerce-jetpack' ) . ' #' . $i,
			'type'     => 'title',
			'id'       => 'wcj_global_discount_options_' . $i,
		),
		array(
			'title'    => __( 'Enabled', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Enabled/disables the discount group.', 'woocommerce-jetpack' ),
			'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
			'id'       => 'wcj_global_discount_sale_enabled_' . $i,
			'default'  => 'yes',
			'type'     => 'checkbox',
		),
		array(
			'title'    => __( 'Type', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Can be fixed or percent.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_global_discount_sale_coefficient_type_' . $i,
			'default'  => 'percent',
			'type'     => 'select',
			'options'  => array(
				'percent' => __( 'Percent', 'woocommerce-jetpack' ),
				'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'    => __( 'Value', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Must be negative number.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_global_discount_sale_coefficient_' . $i,
			'default'  => 0,
			'type'     => 'number',
			'custom_attributes' => array( 'max' => 0, 'step' => 0.0001 ),
		),
		array(
			'title'    => __( 'Final Correction', 'woocommerce-jetpack' ),
			'desc_tip' => sprintf( __( 'Will apply selected function and coefficient to the final price, e.g.: %s.', 'woocommerce-jetpack' ),
				'<em>round( PRICE / COEFFICIENT ) * COEFFICIENT</em>' ),
			'id'       => 'wcj_global_discount_sale_final_correction_func_' . $i,
			'default'  => 'none',
			'type'     => 'select',
			'options'  => array(
				'none'  => __( 'None', 'woocommerce-jetpack' ),
				'round' => __( 'Round', 'woocommerce-jetpack' ),
				'ceil'  => __( 'Ceil', 'woocommerce-jetpack' ),
				'floor' => __( 'Floor', 'woocommerce-jetpack' ),
			),
		),
		array(
			'desc'     => '<br>' . __( 'Final correction coefficient', 'woocommerce-jetpack' ),
			'id'       => 'wcj_global_discount_sale_final_correction_coef_' . $i,
			'default'  => 1,
			'type'     => 'number',
			'custom_attributes' => array( 'min' => 0.0001, 'step' => 0.0001 ),
		),
		array(
			'title'    => __( 'Product Scope', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Possible values: all products, only products that are already on sale, only products that are not on sale.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_global_discount_sale_product_scope_' . $i,
			'default'  => 'all',
			'type'     => 'select',
			'options'  => array(
				'all'              => __( 'All products', 'woocommerce-jetpack' ),
				'only_on_sale'     => __( 'Only products that are already on sale', 'woocommerce-jetpack' ),
				'only_not_on_sale' => __( 'Only products that are not on sale', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'    => __( 'Include Product Categories', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Set this field to apply discount to selected product categories only. Leave blank to apply to all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_global_discount_sale_categories_incl_' . $i,
			'default'  => '',
			'class'    => 'chosen_select',
			'type'     => 'multiselect',
			'options'  => $product_cats,
		),
		array(
			'title'    => __( 'Exclude Product Categories', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Set this field to NOT apply discount to selected product categories. Leave blank to apply to all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_global_discount_sale_categories_excl_' . $i,
			'default'  => '',
			'class'    => 'chosen_select',
			'type'     => 'multiselect',
			'options'  => $product_cats,
		),
		array(
			'title'    => __( 'Include Product Tags', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Set this field to apply discount to selected product tags only. Leave blank to apply to all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_global_discount_sale_tags_incl_' . $i,
			'default'  => '',
			'class'    => 'chosen_select',
			'type'     => 'multiselect',
			'options'  => $product_tags,
		),
		array(
			'title'    => __( 'Exclude Product Tags', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Set this field to NOT apply discount to selected product tags. Leave blank to apply to all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_global_discount_sale_tags_excl_' . $i,
			'default'  => '',
			'class'    => 'chosen_select',
			'type'     => 'multiselect',
			'options'  => $product_tags,
		),
		wcj_get_settings_as_multiselect_or_text(
			array(
				'title'    => __( 'Include Products', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Set this field to apply discount to selected products only. Leave blank to apply to all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_global_discount_sale_products_incl_' . $i,
				'default'  => '',
				'class'    => 'widefat',
			),
			$products,
			$is_multiselect_products
		),
		wcj_get_settings_as_multiselect_or_text(
			array(
				'title'    => __( 'Exclude Products', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Set this field to NOT apply discount to selected products. Leave blank to apply to all products.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_global_discount_sale_products_excl_' . $i,
				'default'  => '',
				'class'    => 'widefat',
			),
			$products,
			$is_multiselect_products
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_global_discount_options_' . $i,
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Advanced Settings', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_global_discount_advanced_options',
	),
	array(
		'title'    => __( 'Compatibility With Products Shortcode', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add compatibility with [products] shortcode that try to get sale products, like [products on_sale="true"]', 'woocommerce-jetpack' ),
		'id'       => 'wcj_global_discount_products_shortcode_compatibility',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Global Discount in Admin', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will enable "global discount" product pricing in backend. It will also affect some modules, e.g.: "Products XML Feeds" module.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_global_discount_enabled_in_admin',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Price Filters Priority', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Priority for all module\'s price filters. If you face pricing issues while using another plugin or booster module, You can change the Priority, Greater value for high priority & Lower value for low priority. Set to zero to use default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_global_discount_advanced_price_hooks_priority',
		'default'  => 0,
		'type'     => 'number',
	),
	$this->get_wpml_terms_in_all_languages_setting(),
	$this->get_wpml_products_in_all_languages_setting(),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_global_discount_advanced_options',
	),
) );
return $settings;
