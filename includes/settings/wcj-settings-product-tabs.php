<?php
/**
 * Booster for WooCommerce - Settings - Product Tabs
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'id'   => 'wcj_product_tab_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_product_tab_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_product_tab_genral_options_tab'   => __( 'General Options', 'woocommerce-jetpack' ),
			'wcj_product_tab_for_all_product_tab'  => __( 'Tabs - All Products', 'woocommerce-jetpack' ),
			'wcj_product_tab_for_per_product_tab'  => __( 'Tabs - Per Product', 'woocommerce-jetpack' ),
			'wcj_product_tab_standard_options_tab' => __( 'Standard Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_product_tab_genral_options_tab',
		'type' => 'tab_start',
	),
	// General settings.
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_custom_product_tabs_general_options',
	),
	array(
		'title'   => __( 'Content Processing', 'woocommerce-jetpack' ),
		'type'    => 'select',
		'id'      => 'wcj_custom_product_tabs_general_content_processing',
		'default' => 'the_content',
		'options' => array(
			/* translators: %s: translators Added */
			'the_content'  => sprintf( __( 'Apply %s filter', 'woocommerce-jetpack' ), 'the_content' ),
			'do_shortcode' => __( 'Only process shortcodes', 'woocommerce-jetpack' ),
			'disabled'     => __( 'Do nothing', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_custom_product_tabs_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_tab_genral_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_product_tab_for_all_product_tab',
		'type' => 'tab_start',
	),
	// Global Custom Tabs.
	array(
		'title' => __( 'Custom Product Tabs - All Products', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'This section lets you add custom single product tabs.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_custom_product_tabs_options',
	),
	array(
		'title'   => __( 'Custom Product Tabs - All Products', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'type'    => 'checkbox',
		'id'      => 'wcj_custom_product_tabs_global_enabled',
		'default' => 'yes',
	),
	array(
		'title'             => __( 'Custom Product Tabs Number', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Click "Save changes" after you change this number.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_custom_product_tabs_global_total_number',
		'default'           => 1,
		'type'              => 'number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array(
				'step' => '1',
				'min'  => '0',
			)
		),
	),
);
$product_tags_options          = wcj_get_terms( 'product_tag' );
$product_cats_options          = wcj_get_terms( 'product_cat' );
$products_options              = wcj_get_products();
$product_tabs_global_total_num = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_custom_product_tabs_global_total_number', 1 ) );
for ( $i = 1; $i <= $product_tabs_global_total_num; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => __( 'Custom Product Tab', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'    => __( 'Title', 'woocommerce-jetpack' ),
				'id'      => 'wcj_custom_product_tabs_title_global_' . $i,
				'default' => '',
				'type'    => 'text',
				'css'     => 'width:30%;min-width:300px;',
			),
			array(
				'desc'     => __( 'Key', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'The unique key for each product tab.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_key_global_' . $i,
				'default'  => 'global_' . $i,
				'type'     => 'text',
			),
			array(
				'desc'     => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Change the Priority to sequence of your product tabs, Greater value for high priority & Lower value for low priority.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_priority_global_' . $i,
				'default'  => ( 40 + $i - 1 ),
				'type'     => 'number',
			),
			array(
				'desc'     => __( 'Content', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'You can use shortcodes here...', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_content_global_' . $i,
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'width:50%;min-width:300px;height:200px;',
			),
			array(
				'desc'     => __( 'Link', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'If you wish to forward tab to new link, enter it here. In this case content is ignored. Leave blank to show content.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_link_global_' . $i,
				'default'  => '',
				'type'     => 'text',
				'css'      => 'width:30%;min-width:300px;',
			),
			array(
				'desc'    => __( 'Link - Open in New Window', 'woocommerce-jetpack' ),
				'id'      => 'wcj_custom_product_tabs_link_new_tab_global_' . $i,
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'desc'     => __( 'PRODUCTS to HIDE this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To hide this tab from some products, enter products here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_global_hide_in_products_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $products_options,
			),
			array(
				'desc'     => __( 'PRODUCTS to SHOW this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To show this tab only for some products, enter products here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_global_show_in_products_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $products_options,
			),
			array(
				'desc'     => __( 'CATEGORIES to HIDE this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To hide this tab from some categories, enter categories here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_global_hide_in_cats_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_cats_options,
			),
			array(
				'desc'     => __( 'CATEGORIES to SHOW this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To show this tab only for some categories, enter categories here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_global_show_in_cats_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_cats_options,
			),
			array(
				'desc'     => __( 'TAGS to HIDE this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To hide this tab from some tags, enter tags here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_global_hide_in_tags_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_tags_options,
			),
			array(
				'desc'     => __( 'TAGS to SHOW this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To show this tab only for some tags, enter tags here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_global_show_in_tags_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_tags_options,
			),
		)
	);
	if ( '' !== wcj_get_option( 'wcj_custom_product_tabs_title_global_hide_in_product_ids_' . $i, '' ) ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'desc'     => __( 'Comma separated PRODUCT IDs to HIDE this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Deprecated', 'woocommerce-jetpack' ) . '!</em></strong>',
					'desc_tip' => __( 'To hide this tab from some products, enter product IDs here.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_custom_product_tabs_title_global_hide_in_product_ids_' . $i,
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:30%;min-width:300px;',
				),
			)
		);
	}
	if ( '' !== wcj_get_option( 'wcj_custom_product_tabs_title_global_show_in_product_ids_' . $i, '' ) ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'desc'     => __( 'Comma separated PRODUCT IDs to SHOW this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Deprecated', 'woocommerce-jetpack' ) . '!</em></strong>',
					'desc_tip' => __( 'To show this tab only for some products, enter product IDs here.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_custom_product_tabs_title_global_show_in_product_ids_' . $i,
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:30%;min-width:300px;',
				),
			)
		);
	}
	if ( '' !== wcj_get_option( 'wcj_custom_product_tabs_title_global_hide_in_cats_ids_' . $i, '' ) ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'desc'     => __( 'Comma separated CATEGORY IDs to HIDE this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Deprecated', 'woocommerce-jetpack' ) . '!</em></strong>',
					'desc_tip' => __( 'To hide this tab from some categories, enter category IDs here.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_custom_product_tabs_title_global_hide_in_cats_ids_' . $i,
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:30%;min-width:300px;',
				),
			)
		);
	}
	if ( '' !== wcj_get_option( 'wcj_custom_product_tabs_title_global_show_in_cats_ids_' . $i, '' ) ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'desc'     => __( 'Comma separated CATEGORY IDs to SHOW this tab', 'woocommerce-jetpack' ) . '. <strong><em>' . __( 'Deprecated', 'woocommerce-jetpack' ) . '!</em></strong>',
					'desc_tip' => __( 'To show this tab only for some categories, enter category IDs here.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_custom_product_tabs_title_global_show_in_cats_ids_' . $i,
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:30%;min-width:300px;',
				),
			)
		);
	}
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_custom_product_tabs_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_product_tab_for_all_product_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_product_tab_for_per_product_tab',
			'type' => 'tab_start',
		),
		// Local Custom Tabs.
		array(
			'title' => __( 'Custom Product Tabs - Per Product', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'This section lets you set defaults for per product custom tabs.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_custom_product_tabs_options_local',
		),
		array(
			'title'    => __( 'Enable Per Product Custom Product Tabs', 'woocommerce-jetpack' ),
			'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'This will add meta boxes to each product\'s edit page.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_custom_product_tabs_local_enabled',
			'default'  => 'yes',
			'type'     => 'checkbox',
		),
		array(
			'title'   => __( 'Use Visual Editor', 'woocommerce-jetpack' ),
			'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
			'id'      => 'wcj_custom_product_tabs_local_wp_editor_enabled',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Add Per Product Tabs Content to "Yoast SEO" plugin analysis', 'woocommerce-jetpack' ),
			'desc'    => __( 'Add', 'woocommerce-jetpack' ),
			'id'      => 'wcj_custom_product_tabs_yoast_seo_enabled',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'title'             => __( 'Default Per Product Custom Product Tabs Number', 'woocommerce-jetpack' ),
			'id'                => 'wcj_custom_product_tabs_local_total_number_default',
			'default'           => 1,
			'type'              => 'number',
			'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => array_merge(
				is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
				array(
					'step' => '1',
					'min'  => '0',
				)
			),
		),
	)
);
$product_tabs_local_total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_custom_product_tabs_local_total_number_default', 1 ) );
for ( $i = 1; $i <= $product_tabs_local_total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => __( 'Custom Product Tab', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'    => __( 'Default Title', 'woocommerce-jetpack' ),
				'id'      => 'wcj_custom_product_tabs_title_local_default_' . $i,
				'default' => '',
				'type'    => 'text',
				'css'     => 'width:30%;min-width:300px;',
			),
			array(
				'desc'     => __( 'Default Key', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'The Default unique key for each product tab.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_key_local_default_' . $i,
				'default'  => 'local_' . $i,
				'type'     => 'text',
			),
			array(
				'desc'    => __( 'Default Priority (i.e. Order)', 'woocommerce-jetpack' ),
				'id'      => 'wcj_custom_product_tabs_priority_local_default_' . $i,
				'default' => ( 50 + $i - 1 ),
				'type'    => 'number',
			),
			array(
				'desc'     => __( 'Default Content', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'You can use shortcodes here...', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_content_local_default_' . $i,
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'width:50%;min-width:300px;height:200px;',
			),
			array(
				'desc'     => __( 'Default Link', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Leave blank for default behaviour.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_link_local_default_' . $i,
				'default'  => '',
				'type'     => 'text',
				'css'      => 'width:30%;min-width:300px;',
			),
			array(
				'desc'    => __( 'Default "Link - Open in New Window"', 'woocommerce-jetpack' ),
				'id'      => 'wcj_custom_product_tabs_link_new_tab_local_default_' . $i,
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'desc'     => __( 'PRODUCTS to HIDE this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To hide this tab from some products, enter products here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_local_hide_in_products_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $products_options,
			),
			array(
				'desc'     => __( 'PRODUCTS to SHOW this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To show this tab only for some products, enter products here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_local_show_in_products_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $products_options,
			),
			array(
				'desc'     => __( 'CATEGORIES to HIDE this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To hide this tab from some categories, enter categories here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_local_hide_in_cats_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_cats_options,
			),
			array(
				'desc'     => __( 'CATEGORIES to SHOW this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To show this tab only for some categories, enter categories here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_local_show_in_cats_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_cats_options,
			),
			array(
				'desc'     => __( 'TAGS to HIDE this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To hide this tab from some tags, enter tags here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_local_hide_in_tags_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_tags_options,
			),
			array(
				'desc'     => __( 'TAGS to SHOW this tab', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'To show this tab only for some tags, enter tags here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_custom_product_tabs_local_show_in_tags_' . $i,
				'default'  => '',
				'class'    => 'chosen_select',
				'type'     => 'multiselect',
				'options'  => $product_tags_options,
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_custom_product_tabs_options_local',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_product_tab_for_per_product_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_product_tab_standard_options_tab',
			'type' => 'tab_start',
		),
		// Standard WooCommerce Tabs.
		array(
			'title' => __( 'WooCommerce Standard Product Tabs Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'This section lets you customize single product tabs.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_product_info_product_tabs_options',
		),
		array(
			'title'   => __( 'Description Tab', 'woocommerce-jetpack' ),
			'desc'    => __( 'Remove tab from product page', 'woocommerce-jetpack' ),
			'id'      => 'wcj_product_info_product_tabs_description_disable',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'desc'     => __( 'Title', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave blank for WooCommerce defaults', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_info_product_tabs_description_title',
			'default'  => '',
			'type'     => 'text',
		),
		array(
			'id'                => 'wcj_product_info_product_tabs_description_priority',
			'default'           => 10,
			'type'              => 'number',
			'desc'              => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		),
		array(
			'title'   => __( 'Additional Information Tab', 'woocommerce-jetpack' ),
			'desc'    => __( 'Remove tab from product page', 'woocommerce-jetpack' ),
			'id'      => 'wcj_product_info_product_tabs_additional_information_disable',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'desc'     => __( 'Title', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave blank for WooCommerce defaults', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_info_product_tabs_additional_information_title',
			'default'  => '',
			'type'     => 'text',
		),
		array(
			'id'                => 'wcj_product_info_product_tabs_additional_information_priority',
			'default'           => 20,
			'type'              => 'number',
			'desc'              => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		),
		array(
			'title'   => __( 'Reviews Tab', 'woocommerce-jetpack' ),
			'desc'    => __( 'Remove tab from product page', 'woocommerce-jetpack' ),
			'id'      => 'wcj_product_info_product_tabs_reviews_disable',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'desc'     => __( 'Title', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Leave blank for WooCommerce defaults', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_info_product_tabs_reviews_title',
			'default'  => '',
			'type'     => 'text',
		),
		array(
			'id'                => 'wcj_product_info_product_tabs_reviews_priority',
			'default'           => 30,
			'type'              => 'number',
			'desc'              => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		),
		array(
			'id'   => 'wcj_product_info_product_tabs_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_product_tab_standard_options_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
