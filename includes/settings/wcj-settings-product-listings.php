<?php
/**
 * Booster for WooCommerce - Settings - Admin Tools
 *
 * @version 2.9.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Handle deprecated option types
$options = array(
	'wcj_product_listings_exclude_cats_on_shop',
	'wcj_product_listings_exclude_cats_on_archives',
);
foreach ( $options as $option ) {
	$value = get_option( $option, '' );
	if ( ! is_array( $value ) ) {
		$value = explode( ',', str_replace( ' ', '', $value ) );
		update_option( $option, $value );
	}
}

// Prepare categories
$product_cats = wcj_get_terms( 'product_cat' );

// Prepare products
$products = wcj_get_products();

// Settings
$settings = array(
	array(
		'title'    => __( 'Shop Page Display Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => sprintf(
			__( 'You can control what is shown on the product archive in <a href="%s">WooCommerce > Settings > Products > Display > Shop page display</a>.', 'woocommerce-jetpack' ),
			admin_url( 'admin.php?page=wc-settings&tab=products&section=display' )
		),
		'id'       => 'wcj_product_listings_shop_page_options',
	),
	array(
		'title'    => __( 'Categories Count', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide categories count on shop page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_hide_cats_count_on_shop',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Exclude Categories', 'woocommerce-jetpack' ),
		'desc_tip' => __(' Excludes one or more categories from the shop page. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_exclude_cats_on_shop',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $product_cats,
	),
	array(
		'title'    => __( 'Hide Empty', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide empty categories on shop page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_hide_empty_cats_on_shop',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Show Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Show products if no categories are displayed on shop page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_show_products_if_no_cats_on_shop',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_listings_shop_page_options',
	),
	array(
		'title'    => __( 'Category Display Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => sprintf(
			__( 'You can control what is shown on category archives in <a href="%s">WooCommerce > Settings > Products > Display > Default category display</a>.', 'woocommerce-jetpack' ),
			admin_url( 'admin.php?page=wc-settings&tab=products&section=display' )
		),
		'id'       => 'wcj_product_listings_archive_pages_options',
	),
	array(
		'title'    => __( 'Subcategories Count', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide subcategories count on category pages', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_hide_cats_count_on_archive',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
	),
	array(
		'title'    => __( 'Exclude Subcategories', 'woocommerce-jetpack' ),
		'desc_tip' => __(' Excludes one or more categories from the category (archive) pages. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_exclude_cats_on_archives',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $product_cats,
	),
	array(
		'title'    => __( 'Hide Empty', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide empty subcategories on category pages', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_hide_empty_cats_on_archives',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Show Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Show products if no categories are displayed on category page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_show_products_if_no_cats_on_archives',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_listings_archive_pages_options',
	),
	array(
		'title'    => __( 'TAX Display Prices in the Shop', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'If you want to display part of your products including TAX and another part excluding TAX, you can set it here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_display_taxes_options',
	),
	array(
		'title'    => __( 'Products - Including TAX', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_display_taxes_products_incl_tax',
		'desc_tip' => __( 'Select products to display including TAX.', 'woocommerce-jetpack' ),
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $products,
	),
	array(
		'title'    => __( 'Products - Excluding TAX', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_display_taxes_products_excl_tax',
		'desc_tip' => __( 'Select products to display excluding TAX.', 'woocommerce-jetpack' ),
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $products,
	),
	array(
		'title'    => __( 'Product Categories - Including TAX', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_display_taxes_product_cats_incl_tax',
		'desc_tip' => __( 'Select product categories to display including TAX.', 'woocommerce-jetpack' ),
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $product_cats,
	),
	array(
		'title'    => __( 'Product Categories - Excluding TAX', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_display_taxes_product_cats_excl_tax',
		'desc_tip' => __( 'Select product categories to display excluding TAX.', 'woocommerce-jetpack' ),
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $product_cats,
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_listings_display_taxes_options',
	),
	array(
		'title'    => __( 'Admin Products List - Custom Columns', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_products_admin_list_custom_columns_options',
	),
	array(
		'title'    => __( 'Enable/Disable', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_products_admin_list_custom_columns_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Custom Columns Total Number', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Save module\'s settings after changing this option to see new settings fields.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_products_admin_list_custom_columns_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '0', )
		),
	),
);
$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_products_admin_list_custom_columns_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Custom Column', 'woocommerce-jetpack' ) . ' #' . $i,
			'desc'     => __( 'Enabled', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Key:', 'woocommerce-jetpack' ) . ' <code>' . 'wcj_products_custom_column_' . $i . '</code>',
			'id'       => 'wcj_products_admin_list_custom_columns_enabled_' . $i,
			'default'  => 'no',
			'type'     => 'checkbox',
		),
		array(
			'desc'     => __( 'Label', 'woocommerce-jetpack' ),
			'id'       => 'wcj_products_admin_list_custom_columns_label_' . $i,
			'default'  => '',
			'type'     => 'text',
			'css'      => 'width:300px;',
		),
		array(
			'desc'     => __( 'Value', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'You can use shortcodes and/or HTML here.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_products_admin_list_custom_columns_value_' . $i,
			'default'  => '',
			'type'     => 'custom_textarea',
			'css'      => 'width:300px;',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_products_admin_list_custom_columns_options',
	),
	array(
		'title'    => __( 'Admin Products List - Columns Order', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_products_admin_list_columns_order_options',
	),
	array(
		'title'    => __( 'Enable/Disable', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_products_admin_list_columns_order_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'       => 'wcj_products_admin_list_columns_order',
		'desc_tip' => __( 'Default columns order', 'woocommerce-jetpack' ) . ':<br>' . str_replace( PHP_EOL, '<br>', $this->get_products_default_columns_in_order() ),
		'default'  => $this->get_products_default_columns_in_order(),
		'type'     => 'textarea',
		'css'      => 'height:300px;min-width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_products_admin_list_columns_order_options',
	),
) );
return $settings;
