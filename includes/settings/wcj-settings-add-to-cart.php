<?php
/**
 * Booster for WooCommerce - Settings - Add to Cart
 *
 * @version 4.6.1
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Per Category Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This sections lets you set Add to Cart button text on per category basis.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_per_category_options',
	),
	array(
		'title'    => __( 'Per Category Labels', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable Section', 'woocommerce-jetpack' ) . '</strong>',
		'desc_tip' => '',
		'id'       => 'wcj_add_to_cart_per_category_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Category Groups Number', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Click "Save changes" after you change this number.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_per_category_total_groups_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '1' )
		),
	),
);
$product_cats = array();
$product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ){
	foreach ( $product_categories as $product_category ) {
		$product_cats[ $product_category->term_id ] = $product_category->name;
	}
}
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_add_to_cart_per_category_total_groups_number', 1 ) ); $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i,
			'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
			'id'       => 'wcj_add_to_cart_per_category_enabled_group_' . $i,
			'default'  => 'yes',
			'type'     => 'checkbox',
		),
		array(
			'desc'     => __( 'categories', 'woocommerce-jetpack' ),
			'desc_tip' => '',
			'id'       => 'wcj_add_to_cart_per_category_ids_group_' . $i,
			'default'  => '',
			'type'     => 'multiselect',
			'class'    => 'chosen_select',
			'options'  => $product_cats,
		),
		array(
			'desc'     => __( 'Button text - single product view', 'woocommerce-jetpack' ),
			'id'       => 'wcj_add_to_cart_per_category_text_single_group_' . $i,
			'default'  => '',
			'type'     => 'textarea',
		),
		array(
			'desc'     => __( 'Button text - product archive (category) view', 'woocommerce-jetpack' ),
			'id'       => 'wcj_add_to_cart_per_category_text_archive_group_' . $i,
			'default'  => '',
			'type'     => 'textarea',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_per_category_options',
	),
	array(
		'title'    => __( 'Per Product Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you set Add to Cart button text on per product basis. When enabled, label for each product can be changed in "Edit Product".', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_per_product_options',
	),
	array(
		'title'    => __( 'Per Product Labels', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable Section', 'woocommerce-jetpack' ) . '</strong>',
		'desc_tip' => '',
		'id'       => 'wcj_add_to_cart_per_product_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_per_product_options',
	),
	array(
		'title'    => __( 'Per Product Type Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => 'This sections lets you set text for add to cart button for various products types and various conditions.',
		'id'       => 'wcj_add_to_cart_text_options',
	),
	array(
		'title'    => __( 'Per Product Type Labels', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable Section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_add_to_cart_text_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
) );
$groups_by_product_type = array(
	array(
		'id'       => 'simple',
		'title'    => __( 'Simple product', 'woocommerce-jetpack' ),
		'default'  => __( 'Add to cart', 'woocommerce' ),
	),
	array(
		'id'       => 'variable',
		'title'    => __( 'Variable product', 'woocommerce-jetpack' ),
		'default'  => __( 'Select options', 'woocommerce' ),
	),
	array(
		'id'       => 'external',
		'title'    => __( 'External product', 'woocommerce-jetpack' ),
		'default'  => __( 'Buy product', 'woocommerce' ),
	),
	array(
		'id'       => 'grouped',
		'title'    => __( 'Grouped product', 'woocommerce-jetpack' ),
		'default'  => __( 'View products', 'woocommerce' ),
	),
	array(
		'id'       => 'other',
		'title'    => __( 'Other product', 'woocommerce-jetpack' ),
		'default'  => __( 'Read more', 'woocommerce' ),
	),
);
foreach ( $groups_by_product_type as $group_by_product_type ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => $group_by_product_type['title'],
			'id'       => 'wcj_add_to_cart_text_on_single_' . $group_by_product_type['id'],
			'desc'     => __( 'Single product view.', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave blank to disable.', 'woocommerce-jetpack' ) . ' ' . __( 'Default: ', 'woocommerce-jetpack' ) . $group_by_product_type['default'],
			'default'  => $group_by_product_type['default'],
			'type'     => 'text',
		),
		array(
			'id'       => 'wcj_add_to_cart_text_on_archives_' . $group_by_product_type['id'],
			'desc'     => __( 'Product category (archive) view.', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave blank to disable.', 'woocommerce-jetpack' ) . ' ' . __( 'Default: ', 'woocommerce-jetpack' ) . $group_by_product_type['default'],
			'default'  => $group_by_product_type['default'],
			'type'     => 'text',
		),
	) );
	if ( 'variable' !== $group_by_product_type['id'] )
		$settings = array_merge( $settings, array(
			array(
				'desc'     => __( 'Products not in stock. Product category (archive) view.', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable. Default: Add to cart', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_text_on_archives_not_in_stock_' . $group_by_product_type['id'],
				'default'  => __( 'Read more', 'woocommerce-jetpack' ),
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Products on sale. Single product view.', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable. Default: Add to cart', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_text_on_single_sale_' . $group_by_product_type['id'],
				'default'  => __( 'Add to cart', 'woocommerce-jetpack' ),
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Products on sale. Product category (archive) view.', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable. Default: Add to cart', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_text_on_archives_sale_' . $group_by_product_type['id'],
				'default'  => __( 'Add to cart', 'woocommerce-jetpack' ),
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Products with price set to 0 (i.e. free). Single product view.', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable. Default: Add to cart', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_text_on_single_zero_price_' . $group_by_product_type['id'],
				'default'  => __( 'Add to cart', 'woocommerce-jetpack' ),
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Products with price set to 0 (i.e. free). Product category (archive) view.', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable. Default: Add to cart', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_text_on_archives_zero_price_' . $group_by_product_type['id'],
				'default'  => __( 'Add to cart', 'woocommerce-jetpack' ),
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Products with empty price. Product category (archive) view.', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank to disable. Default: Read More', 'woocommerce-jetpack' ),
				'id'       => 'wcj_add_to_cart_text_on_archives_no_price_' . $group_by_product_type['id'],
				'default'  => __( 'Read more', 'woocommerce-jetpack' ),
				'type'     => 'text',
			),
		) );
	if ( 'external' === $group_by_product_type['id'] ) {
		continue;
	}
	$settings = array_merge( $settings, array(
		array(
			'id'       => 'wcj_add_to_cart_text_on_single_in_cart_' . $group_by_product_type['id'],
			'desc'     => __( 'Already in cart. Single product view.', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave blank to disable.', 'woocommerce-jetpack' ) . ' ' .
				__( 'Try: ', 'woocommerce-jetpack' ) . __( 'Already in cart - Add Again?', 'woocommerce-jetpack' ) . ' ' .
				__( 'Default: ', 'woocommerce-jetpack' ) . __( 'Add to cart', 'woocommerce-jetpack' ),
			'default'  => __( 'Add to cart', 'woocommerce-jetpack' ),
			'type'     => 'text',
		),
		array(
			'id'       => 'wcj_add_to_cart_text_on_archives_in_cart_' . $group_by_product_type['id'],
			'desc'     => __( 'Already in cart. Product category (archive) view.', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave blank to disable.', 'woocommerce-jetpack' ) . ' ' .
				__( 'Try: ', 'woocommerce-jetpack' ) . __( 'Already in cart - Add Again?', 'woocommerce-jetpack' ) . ' ' .
				__( 'Default: ', 'woocommerce-jetpack' ) . __( 'Add to cart', 'woocommerce-jetpack' ),
			'default'  => __( 'Add to cart', 'woocommerce-jetpack' ),
			'type'     => 'text',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_text_options',
	),
) );
return $settings;
