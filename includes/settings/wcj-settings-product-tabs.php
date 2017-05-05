<?php
/**
 * WooCommerce Jetpack Settings - Product Tabs
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	// Global Custom Tabs
	array(
		'title'     => __( 'Custom Product Tabs Options', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'desc'      => __( 'This section lets you add custom single product tabs.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_custom_product_tabs_options',
	),
	array(
		'title'     => __( 'Custom Product Tabs Number', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Click "Save changes" after you change this number.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_custom_product_tabs_global_total_number',
		'default'   => 1,
		'type'      => 'number',
		'desc'      => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
			array(
				'step' => '1',
				'min'  => '0',
			)
		),
	),
);
$product_tags_options = array();
$product_tags = get_terms( 'product_tag', 'orderby=name&hide_empty=0' );
if ( ! empty( $product_tags ) && ! is_wp_error( $product_tags ) ){
	foreach ( $product_tags as $product_tag ) {
		$product_tags_options[ $product_tag->term_id ] = $product_tag->name;
	}
}
$product_cats_options = array();
$product_cats = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
if ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ){
	foreach ( $product_cats as $product_cat ) {
		$product_cats_options[ $product_cat->term_id ] = $product_cat->name;
	}
}
$products_options = apply_filters( 'wcj_get_products_filter', array() );
for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_custom_product_tabs_global_total_number', 1 ) ); $i++ ) {
	$settings = array_merge( $settings,
		array(
			array(
				'title'     => __( 'Custom Product Tab', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'      => __( 'Title', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_title_global_' . $i,
				'default'   => '',
				'type'      => 'text',
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'title'     => '',
				'desc'      => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_priority_global_' . $i,
				'default'   => (40 + $i - 1),
				'type'      => 'number',
			),
			array(
				'title'     => '',
				'desc'      => __( 'Content', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'You can use shortcodes here...', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_content_global_' . $i,
				'default'   => '',
				'type'      => 'textarea',
				'css'       => 'width:50%;min-width:300px;height:200px;',
			),
			array(
				'desc'      => __( 'Link', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'If you wish to forward tab to new link, enter it here. In this case content is ignored. Leave blank to show content.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_link_global_' . $i,
				'default'   => '',
				'type'      => 'text',
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'desc'      => __( 'Link - Open in New Window', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_link_new_tab_global_' . $i,
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'title'     => '',
				'desc'      => __( 'PRODUCTS to HIDE this tab', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'To hide this tab from some products, enter products here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_global_hide_in_products_' . $i,
				'default'   => '',
				'class'     => 'chosen_select',
				'type'      => 'multiselect',
				'options'   => $products_options,
			),
			array(
				'title'     => '',
				'desc'      => __( 'PRODUCTS to SHOW this tab', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'To show this tab only for some products, enter products here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_global_show_in_products_' . $i,
				'default'   => '',
				'class'     => 'chosen_select',
				'type'      => 'multiselect',
				'options'   => $products_options,
			),
			array(
				'title'     => '',
				'desc'      => __( 'CATEGORIES to HIDE this tab', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'To hide this tab from some categories, enter categories here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_global_hide_in_cats_' . $i,
				'default'   => '',
				'class'     => 'chosen_select',
				'type'      => 'multiselect',
				'options'   => $product_cats_options,
			),
			array(
				'title'     => '',
				'desc'      => __( 'CATEGORIES to SHOW this tab', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'To show this tab only for some categories, enter categories here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_global_show_in_cats_' . $i,
				'default'   => '',
				'class'     => 'chosen_select',
				'type'      => 'multiselect',
				'options'   => $product_cats_options,
			),
			array(
				'title'     => '',
				'desc'      => __( 'TAGS to HIDE this tab', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'To hide this tab from some tags, enter tags here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_global_hide_in_tags_' . $i,
				'default'   => '',
				'class'     => 'chosen_select',
				'type'      => 'multiselect',
				'options'   => $product_tags_options,
			),
			array(
				'title'     => '',
				'desc'      => __( 'TAGS to SHOW this tab', 'woocommerce-jetpack' ),
				'desc_tip'  => __( 'To show this tab only for some tags, enter tags here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_global_show_in_tags_' . $i,
				'default'   => '',
				'class'     => 'chosen_select',
				'type'      => 'multiselect',
				'options'   => $product_tags_options,
			),
			array(
				'title'     => '',
				'desc'      => __( 'Comma separated PRODUCT IDs to HIDE this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Deprecated', 'woocommerce-jetpack' ) . '!</em></strong>',
				'desc_tip'  => __( 'To hide this tab from some products, enter product IDs here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_title_global_hide_in_product_ids_' . $i,
				'default'   => '',
				'type'      => 'text',
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'title'     => '',
				'desc'      => __( 'Comma separated PRODUCT IDs to SHOW this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Deprecated', 'woocommerce-jetpack' ) . '!</em></strong>',
				'desc_tip'  => __( 'To show this tab only for some products, enter product IDs here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_title_global_show_in_product_ids_' . $i,
				'default'   => '',
				'type'      => 'text',
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'title'     => '',
				'desc'      => __( 'Comma separated CATEGORY IDs to HIDE this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Deprecated', 'woocommerce-jetpack' ) . '!</em></strong>',
				'desc_tip'  => __( 'To hide this tab from some categories, enter category IDs here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_title_global_hide_in_cats_ids_' . $i,
				'default'   => '',
				'type'      => 'text',
				'css'       => 'width:30%;min-width:300px;',
			),
			array(
				'title'     => '',
				'desc'      => __( 'Comma separated CATEGORY IDs to SHOW this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Deprecated', 'woocommerce-jetpack' ) . '!</em></strong>',
				'desc_tip'  => __( 'To show this tab only for some categories, enter category IDs here.', 'woocommerce-jetpack' ),
				'id'        => 'wcj_custom_product_tabs_title_global_show_in_cats_ids_' . $i,
				'default'   => '',
				'type'      => 'text',
				'css'       => 'width:30%;min-width:300px;',
			),
		)
	);
}
$settings = array_merge( $settings, array(
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_custom_product_tabs_options',
	),
	// Local Custom Tabs
	array(
		'title'     => __( 'Custom Product Tabs - Per Product', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'desc'      => __( 'This section lets you set defaults for per product custom tabs.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_custom_product_tabs_options_local',
	),
	array(
		'title'     => __( 'Enable Per Product Custom Product Tabs', 'woocommerce-jetpack' ),
		'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'This will add meta boxes to each product\'s edit page.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_custom_product_tabs_local_enabled',
		'default'   => 'yes',
		'type'      => 'checkbox',
	),
	array(
		'title'     => __( 'Use Visual Editor', 'woocommerce-jetpack' ),
		'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
		'id'        => 'wcj_custom_product_tabs_local_wp_editor_enabled',
		'default'   => 'yes',
		'type'      => 'checkbox',
	),
	array(
		'title'     => __( 'Default Per Product Custom Product Tabs Number', 'woocommerce-jetpack' ),
		'id'        => 'wcj_custom_product_tabs_local_total_number_default',
		'default'   => 1,
		'type'      => 'number',
		'desc'      => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
			array(
				'step' => '1',
				'min'  => '0',
			)
		),
	),
) );
for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) ); $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'     => __( 'Custom Product Tab', 'woocommerce-jetpack' ) . ' #' . $i,
			'desc'      => __( 'Default Title', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_title_local_default_' . $i,
			'default'   => '',
			'type'      => 'text',
			'css'       => 'width:30%;min-width:300px;',
		),
		array(
			'title'     => '',
			'desc'      => __( 'Default Priority (i.e. Order)', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_priority_local_default_' . $i,
			'default'   => 50,
			'type'      => 'number',
		),
		array(
			'title'     => '',
			'desc'      => __( 'Default Content', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'You can use shortcodes here...', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_content_local_default_' . $i,
			'default'   => '',
			'type'      => 'textarea',
			'css'       => 'width:50%;min-width:300px;height:200px;',
		),
		array(
			'desc'      => __( 'Default Link', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'Leave blank for default behaviour.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_link_local_default_' . $i,
			'default'   => '',
			'type'      => 'text',
			'css'       => 'width:30%;min-width:300px;',
		),
		array(
			'desc'      => __( 'Default "Link - Open in New Window"', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_link_new_tab_local_default_' . $i,
			'default'   => 'no',
			'type'      => 'checkbox',
		),
		array(
			'title'     => '',
			'desc'      => __( 'PRODUCTS to HIDE this tab', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'To hide this tab from some products, enter products here.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_local_hide_in_products_' . $i,
			'default'   => '',
			'class'     => 'chosen_select',
			'type'      => 'multiselect',
			'options'   => $products_options,
		),
		array(
			'title'     => '',
			'desc'      => __( 'PRODUCTS to SHOW this tab', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'To show this tab only for some products, enter products here.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_local_show_in_products_' . $i,
			'default'   => '',
			'class'     => 'chosen_select',
			'type'      => 'multiselect',
			'options'   => $products_options,
		),
		array(
			'title'     => '',
			'desc'      => __( 'CATEGORIES to HIDE this tab', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'To hide this tab from some categories, enter categories here.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_local_hide_in_cats_' . $i,
			'default'   => '',
			'class'     => 'chosen_select',
			'type'      => 'multiselect',
			'options'   => $product_cats_options,
		),
		array(
			'title'     => '',
			'desc'      => __( 'CATEGORIES to SHOW this tab', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'To show this tab only for some categories, enter categories here.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_local_show_in_cats_' . $i,
			'default'   => '',
			'class'     => 'chosen_select',
			'type'      => 'multiselect',
			'options'   => $product_cats_options,
		),
		array(
			'title'     => '',
			'desc'      => __( 'TAGS to HIDE this tab', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'To hide this tab from some tags, enter tags here.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_local_hide_in_tags_' . $i,
			'default'   => '',
			'class'     => 'chosen_select',
			'type'      => 'multiselect',
			'options'   => $product_tags_options,
		),
		array(
			'title'     => '',
			'desc'      => __( 'TAGS to SHOW this tab', 'woocommerce-jetpack' ),
			'desc_tip'  => __( 'To show this tab only for some tags, enter tags here.', 'woocommerce-jetpack' ),
			'id'        => 'wcj_custom_product_tabs_local_show_in_tags_' . $i,
			'default'   => '',
			'class'     => 'chosen_select',
			'type'      => 'multiselect',
			'options'   => $product_tags_options,
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_custom_product_tabs_options_local',
	),
	// Standard WooCommerce Tabs
	array(
		'title'     => __( 'WooCommerce Standard Product Tabs Options', 'woocommerce-jetpack' ),
		'type'      => 'title',
		'desc'      => __( 'This section lets you customize single product tabs.', 'woocommerce-jetpack' ),
		'id'        => 'wcj_product_info_product_tabs_options',
	),
	array(
		'title'     => __( 'Description Tab', 'woocommerce-jetpack' ),
		'desc'      => __( 'Remove tab from product page', 'woocommerce-jetpack' ),
		'id'        => 'wcj_product_info_product_tabs_description_disable',
		'default'   => 'no',
		'type'      => 'checkbox',
	),
	array(
		'title'     => '',
		'desc'      => __( 'Title.', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Leave blank for WooCommerce defaults', 'woocommerce-jetpack' ),
		'id'        => 'wcj_product_info_product_tabs_description_title',
		'default'   => '',
		'type'      => 'text',
	),
	array(
		'title'     => '',
		'id'        => 'wcj_product_info_product_tabs_description_priority',
		'default'   => 10,
		'type'      => 'number',
		'desc'      => __( 'Priority (i.e. Order).', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
	),
	array(
		'title'     => __( 'Additional Information Tab', 'woocommerce-jetpack' ),
		'desc'      => __( 'Remove tab from product page', 'woocommerce-jetpack' ),
		'id'        => 'wcj_product_info_product_tabs_additional_information_disable',
		'default'   => 'no',
		'type'      => 'checkbox',
	),
	array(
		'title'     => '',
		'desc'      => __( 'Title.', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Leave blank for WooCommerce defaults', 'woocommerce-jetpack' ),
		'id'        => 'wcj_product_info_product_tabs_additional_information_title',
		'default'   => '',
		'type'      => 'text',
	),
	array(
		'title'     => '',
		'id'        => 'wcj_product_info_product_tabs_additional_information_priority',
		'default'   => 20,
		'type'      => 'number',
		'desc'      => __( 'Priority (i.e. Order).', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
	),
	array(
		'title'     => __( 'Reviews Tab', 'woocommerce-jetpack' ),
		'desc'      => __( 'Remove tab from product page', 'woocommerce-jetpack' ),
		'id'        => 'wcj_product_info_product_tabs_reviews_disable',
		'default'   => 'no',
		'type'      => 'checkbox',
	),
	array(
		'title'     => '',
		'desc'      => __( 'Title.', 'woocommerce-jetpack' ),
		'desc_tip'  => __( 'Leave blank for WooCommerce defaults', 'woocommerce-jetpack' ),
		'id'        => 'wcj_product_info_product_tabs_reviews_title',
		'default'   => '',
		'type'      => 'text',
	),
	array(
		'title'     => '',
		'id'        => 'wcj_product_info_product_tabs_reviews_priority',
		'default'   => 30,
		'type'      => 'number',
		'desc'      => __( 'Priority (i.e. Order).', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
	),
	array(
		'type'      => 'sectionend',
		'id'        => 'wcj_product_info_product_tabs_options',
	),
) );
return $settings;