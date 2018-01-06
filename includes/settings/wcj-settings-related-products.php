<?php
/**
 * Booster for WooCommerce - Settings - Related Products
 *
 * @version 3.1.1
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$is_multiselect_products     = ( 'yes' === get_option( 'wcj_list_for_products', 'yes' ) );
$products                    = ( $is_multiselect_products ? wcj_get_products() : false );
$product_cats                = wcj_get_terms( 'product_cat' );
$product_tags                = wcj_get_terms( 'product_tag' );
wcj_maybe_convert_and_update_option_value( array(
	array( 'id' => 'wcj_product_info_related_products_hide_products_incl', 'default' => '' ),
	array( 'id' => 'wcj_product_info_related_products_hide_products_excl', 'default' => '' ),
), $is_multiselect_products );

$orderby_options = array(
	'rand'  => __( 'Random', 'woocommerce-jetpack' ),
	'date'  => __( 'Date', 'woocommerce-jetpack' ),
	'title' => __( 'Title', 'woocommerce-jetpack' ),
);
if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
	$orderby_options['meta_value']     = __( 'Meta Value', 'woocommerce-jetpack' );
	$orderby_options['meta_value_num'] = __( 'Meta Value (Numeric)', 'woocommerce-jetpack' );
} else {
	$orderby_options['id']             = __( 'ID', 'woocommerce-jetpack' );
	$orderby_options['modified']       = __( 'Modified', 'woocommerce-jetpack' );
	$orderby_options['menu_order']     = __( 'Menu order', 'woocommerce-jetpack' );
	$orderby_options['price']          = __( 'Price', 'woocommerce-jetpack' );
}

$settings = array(
	array(
		'title'    => __( 'General', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_info_related_products_general_options',
	),
	array(
		'title'    => __( 'Related Products Number', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_num',
		'default'  => 3,
		'type'     => 'number',
	),
	array(
		'title'    => __( 'Related Products Columns', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_columns',
		'default'  => 3,
		'type'     => 'number',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_info_related_products_general_options',
	),
	array(
		'title'    => __( 'Order', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_info_related_products_order_options',
	),
	array(
		'title'    => __( 'Order by', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_orderby',
		'default'  => 'rand',
		'type'     => 'select',
		'options'  => $orderby_options,
	),
);
if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Meta Key', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Used only if order by "Meta Value" or "Meta Value (Numeric)" is selected in "Order by".', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_info_related_products_orderby_meta_value_meta_key',
			'default'  => '',
			'type'     => 'text',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Order', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Ignored if order by "Random" is selected in "Order by".', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_order',
		'default'  => 'desc',
		'type'     => 'select',
		'options'  => array(
			'asc'  => __( 'Ascending', 'woocommerce-jetpack' ),
			'desc' => __( 'Descending', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_info_related_products_order_options',
	),
	array(
		'title'    => __( 'Relate', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_info_related_products_relate_options',
	),
	array(
		'title'    => __( 'Relate by Category', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_relate_by_category',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Relate by Tag', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_relate_by_tag',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Relate by Product Attribute', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_by_attribute_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'desc'     => __( 'Attribute Type', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If using "Global Attribute" enter attribute\'s <em>slug</em> in "Attribute Name"', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_by_attribute_attribute_type',
		'default'  => 'global',
		'type'     => 'select',
		'options'  => array(
			'global' => __( 'Global Attribute', 'woocommerce-jetpack' ),
			'local'  => __( 'Local Attribute', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'Attribute Name', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_by_attribute_attribute_name',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'desc'     => __( 'Attribute Value', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_by_attribute_attribute_value',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Relate Manually', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add metabox to each product\'s edit page.', 'woocommerce-jetpack' ) .
			' ' . __( 'You will be able to select related products manually for each product individually. There is also an option to remove related products on per product basis.', 'woocommerce-jetpack' ) .
			' ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_product_info_related_products_per_product',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_info_related_products_relate_options',
	),
	array(
		'title'    => __( 'Hide', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_info_related_products_hide_options',
	),
	array(
		'title'    => __( 'Hide Related Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_hide',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Include Product Categories', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set this field to hide related products on selected product categories only. Leave blank to hide on all products.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_hide_cats_incl',
		'default'  => '',
		'class'    => 'chosen_select',
		'type'     => 'multiselect',
		'options'  => $product_cats,
	),
	array(
		'title'    => __( 'Exclude Product Categories', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set this field to NOT hide related products on selected product categories. Leave blank to hide on all products.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_hide_cats_excl',
		'default'  => '',
		'class'    => 'chosen_select',
		'type'     => 'multiselect',
		'options'  => $product_cats,
	),
	array(
		'title'    => __( 'Include Product Tags', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set this field to hide related products on selected product tags only. Leave blank to hide on all products.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_hide_tags_incl',
		'default'  => '',
		'class'    => 'chosen_select',
		'type'     => 'multiselect',
		'options'  => $product_tags,
	),
	array(
		'title'    => __( 'Exclude Product Tags', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set this field to NOT hide related products on selected product tags. Leave blank to hide on all products.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_info_related_products_hide_tags_excl',
		'default'  => '',
		'class'    => 'chosen_select',
		'type'     => 'multiselect',
		'options'  => $product_tags,
	),
	wcj_get_settings_as_multiselect_or_text(
		array(
			'title'    => __( 'Include Products', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Set this field to hide related products on selected products only. Leave blank to hide on all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_info_related_products_hide_products_incl',
			'default'  => '',
			'class'    => 'widefat',
		),
		$products,
		$is_multiselect_products
	),
	wcj_get_settings_as_multiselect_or_text(
		array(
			'title'    => __( 'Exclude Products', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Set this field to NOT hide related products on selected products. Leave blank to hide on all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_info_related_products_hide_products_excl',
			'default'  => '',
			'class'    => 'widefat',
		),
		$products,
		$is_multiselect_products
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_info_related_products_hide_options',
	),
) );
return $settings;
