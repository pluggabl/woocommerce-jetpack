<?php
/**
 * Booster for WooCommerce - Settings - Admin Tools
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Handle deprecated option types.
$options = array(
	'wcj_product_listings_exclude_cats_on_shop',
	'wcj_product_listings_exclude_cats_on_archives',
);
foreach ( $options as $option ) {
	$value = wcj_get_option( $option, '' );
	if ( ! is_array( $value ) ) {
		$value = explode( ',', str_replace( ' ', '', $value ) );
		update_option( $option, $value );
	}
}

// Prepare categories.
$product_cats = wcj_get_terms( 'product_cat' );

// Settings.
return array(
	array(
		'id'   => 'wcj_product_listing_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_product_listing_general_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_product_listing_shop_page_tab'           => __( 'Shop Page Display Options', 'woocommerce-jetpack' ),
			'wcj_product_listing_category_display_tab'    => __( 'Category Display Options', 'woocommerce-jetpack' ),
			'wcj_product_listing_visibility_by_price_tab' => __( 'Product Shop Visibility by Price', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_product_listing_shop_page_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Shop Page Display Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => ( WCJ_IS_WC_VERSION_BELOW_3_3_0 ? sprintf(
		/* translators: %s: translators Added */
			__( 'You can control what is shown on the product archive in <a href="%s">WooCommerce > Settings > Products > Display > Shop page display</a>.', 'woocommerce-jetpack' ),
			admin_url( 'admin.php?page=wc-settings&tab=products&section=display' )
		) : '' ),
		'id'    => 'wcj_product_listings_shop_page_options',
	),
	array(
		'title'   => __( 'Categories Count', 'woocommerce-jetpack' ),
		'desc'    => __( 'Hide categories count on shop page', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_listings_hide_cats_count_on_shop',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'    => __( 'Exclude Categories', 'woocommerce-jetpack' ),
		'desc_tip' => __( ' Excludes one or more categories from the shop page. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_exclude_cats_on_shop',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $product_cats,
	),
	array(
		'title'   => __( 'Hide Empty', 'woocommerce-jetpack' ),
		'desc'    => __( 'Hide empty categories on shop page', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_listings_hide_empty_cats_on_shop',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Show Products', 'woocommerce-jetpack' ),
		'desc'    => __( 'Show products if no categories are displayed on shop page', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_listings_show_products_if_no_cats_on_shop',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'title'    => __( 'Exclude Categories Products', 'woocommerce-jetpack' ),
		'desc_tip' => __( ' Excludes one or more categories products from the shop page. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_exclude_cats_products_on_shop',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $product_cats,
	),
	array(
		'id'   => 'wcj_product_listings_shop_page_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_listing_shop_page_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_product_listing_category_display_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Category Display Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => ( WCJ_IS_WC_VERSION_BELOW_3_3_0 ? sprintf(
						/* translators: %s: translators Added */
			__( 'You can control what is shown on category archives in <a href="%s">WooCommerce > Settings > Products > Display > Default category display</a>.', 'woocommerce-jetpack' ),
			admin_url( 'admin.php?page=wc-settings&tab=products&section=display' )
		) : '' ),
		'id'    => 'wcj_product_listings_archive_pages_options',
	),
	array(
		'title'             => __( 'Subcategories Count', 'woocommerce-jetpack' ),
		'desc'              => __( 'Hide subcategories count on category pages', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_listings_hide_cats_count_on_archive',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'    => __( 'Exclude Subcategories', 'woocommerce-jetpack' ),
		'desc_tip' => __( ' Excludes one or more categories from the category (archive) pages. Leave blank to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_exclude_cats_on_archives',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $product_cats,
	),
	array(
		'title'   => __( 'Hide Empty', 'woocommerce-jetpack' ),
		'desc'    => __( 'Hide empty subcategories on category pages', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_listings_hide_empty_cats_on_archives',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Show Products', 'woocommerce-jetpack' ),
		'desc'    => __( 'Show products if no categories are displayed on category page', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_listings_show_products_if_no_cats_on_archives',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'id'   => 'wcj_product_listings_archive_pages_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_listing_category_display_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_product_listing_visibility_by_price_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Product Shop Visibility by Price', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Here you can set to hide products from shop and search results depending on product\'s price. Products will still be accessible via direct link.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_product_listings_product_visibility_by_price_options',
	),
	array(
		'title'   => __( 'Product Shop Visibility by Price', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_product_listings_product_visibility_by_price_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Min Price', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Products with price below this value will be hidden. Ignored if set to zero.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_listings_product_visibility_by_price_min',
		'default'           => 0,
		'type'              => 'number',
		'custom_attributes' => array(
			'min'  => 0,
			'step' => wcj_get_wc_price_step(),
		),
	),
	array(
		'title'             => __( 'Max Price', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Products with price above this value will be hidden. Ignored if set to zero.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_listings_product_visibility_by_price_max',
		'default'           => 0,
		'type'              => 'number',
		'custom_attributes' => array(
			'min'  => 0,
			'step' => wcj_get_wc_price_step(),
		),
	),
	array(
		'id'   => 'wcj_product_listings_product_visibility_by_price_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_listing_visibility_by_price_tab',
		'type' => 'tab_end',
	),
);
