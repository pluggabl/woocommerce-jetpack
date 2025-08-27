<?php
/**
 * Booster for WooCommerce - Settings - Wishlist
 *
 * @version 7.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$elite_message        	 = apply_filters( 'booster_message', '', 'desc' );
$desc_advanced_message   = apply_filters( 'booster_message', '', 'desc_below' );
$settings                = array();
$single_or_archive_array = array( 'archive', 'single' );

$settings                = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_wishlist_options',
			'type' => 'sectionend',
		),
		array(
			'id'      => 'wcj_wishlist_options',
			'type'    => 'tab_ids',
			'tab_ids' => array(
				'wcj_wishlist_single_tab'         => __( 'Single Product Pages', 'woocommerce-jetpack' ),
				'wcj_wishlist_archive_tab'        => __( 'Archives (Products Loop)', 'woocommerce-jetpack' ),
				'wcj_wishlist_wishlist_page_tab'  => __( 'Wishlist Page', 'woocommerce-jetpack' ),
				'wcj_wishlist_single_general_tab' => __( 'General', 'woocommerce-jetpack' ),
			),
		),
	)
);

foreach ( $single_or_archive_array as $single_or_archive ) {

	$single_or_archive_desc = ( 'archive' === $single_or_archive ? __( 'Archives (Products Loop)', 'woocommerce-jetpack' ) : __( 'Single Product Pages', 'woocommerce-jetpack' ) );

	$is_single = ( 'single' === $single_or_archive );

	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'wcj_wishlist_' . $single_or_archive . '_tab',
				'type' => 'tab_start',
			),
			array(
				'title' => $single_or_archive_desc,
				'type'  => 'title',
				'id'    => 'wcj_wishlist_options_' . $single_or_archive,
				'desc' 	=> __( 'Want to customize button text & style, control positions, and add wishlist buttons to shop/category pages? '.$desc_advanced_message.' ', 'woocommerce-jetpack' ),
			),
			array(
				'title'   => __( 'Enable/Disable', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wishlist_enabled_' . $single_or_archive,
				'default' => $is_single ? 'yes' : 'no',
				'custom_attributes' => $is_single ? '' : apply_filters( 'booster_message', '', 'disabled' ),
				'desc_tip' => $is_single ? '' : __( 'Available in Booster Elite only. '.$elite_message.' ', 'woocommerce-jetpack' ),
				'type'    => 'checkbox',
			),
			array(
				'title'    => __( 'Title', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'If You want a text then you can add the text.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_wishlist_title_' . $single_or_archive,
				'default'  => __( 'Add to wishlist', 'woocommerce-jetpack' ),
				'type'     => 'text',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			),
			array(
				'title'   => __( 'Style', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wishlist_style_' . $single_or_archive,
				'default' => 'button_icon',
				'type'    => 'select',
				'options' => array(
					'button_icon' => __( 'Button with Icon', 'woocommerce-jetpack' ),
					'button'      => __( 'Button', 'woocommerce-jetpack' ),
					'text'        => __( 'Text(link)', 'woocommerce-jetpack' ),
					'icon'        => __( 'Icon', 'woocommerce-jetpack' ),
				),
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			),
			array(
				'title'   => __( 'Position', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wishlist_hook_' . $single_or_archive,
				'default' => ( 'single' === $single_or_archive ) ? 'woocommerce_after_add_to_cart_button' : 'woocommerce_after_shop_loop_item',
				'type'    => 'select',
				'options' => array_merge(
					( 'single' === $single_or_archive ?
					array(
						'woocommerce_after_add_to_cart_button' => __( 'After add to cart button', 'woocommerce-jetpack' ),
						'woocommerce_before_add_to_cart_button' => __( 'Before add to cart button', 'woocommerce-jetpack' ),
						'woocommerce_after_add_to_cart_form' => __( 'After add to cart form', 'woocommerce-jetpack' ),
						'woocommerce_before_add_to_cart_form' => __( 'Before add to cart form', 'woocommerce-jetpack' ),
						'woocommerce_before_single_product_summary' => __( 'Before single product summary', 'woocommerce-jetpack' ),
						'woocommerce_single_product_summary' => __( 'Inside single product summary', 'woocommerce-jetpack' ),
						'woocommerce_after_single_product_summary' => __( 'After single product summary', 'woocommerce-jetpack' ),
						'woocommerce_product_thumbnails' => __( 'Over product Image', 'woocommerce-jetpack' ),
					) :
					array(
						'woocommerce_after_shop_loop_item' => __( 'Before add to cart button', 'woocommerce-jetpack' ),
						'woocommerce_after_shop_loop_item_2' => __( 'After add to cart button', 'woocommerce-jetpack' ),
						'woocommerce_shop_loop_item_title' => __( 'Before product title', 'woocommerce-jetpack' ),
						'woocommerce_after_shop_loop_item_title' => __( 'After product title', 'woocommerce-jetpack' ),
						'woocommerce_before_shop_loop_item' => __( 'Over product Image', 'woocommerce-jetpack' ),
					) )
				),
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			),
			array(
				'title'   => __( 'Position Order (i.e. Priority)', 'woocommerce-jetpack' ),
				'id'      => 'wcj_wishlist_priority_' . $single_or_archive,
				'default' => 15,
				'type'    => 'number',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			),
			array(
				'id'   => 'wcj_wishlist_options_' . $single_or_archive,
				'type' => 'sectionend',
			),
			array(
				'id'   => 'wcj_wishlist_' . $single_or_archive . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}

$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_wishlist_wishlist_page_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => 'Wishlist Page',
			'type'  => 'title',
			'id'    => 'wcj_wishlist_general_options',
		),
		array(
			'desc' => 'Create page and this shortcode [wcj_wishlist] for display wishlist',
			'type' => 'title',
			'id'   => 'wcj_wishlist_shortcode',
		),
		array(
			'title'   => __( 'Enter wishlist page URL', 'woocommerce-jetpack' ),
			'id'      => 'wcj_wishlist_page_url',
			'default' => '',
			'type'    => 'text',
		),
		array(
			'id'   => 'wcj_wishlist_general_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_wishlist_wishlist_page_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_wishlist_single_general_tab',
			'type' => 'tab_start',
		),
	)
);

$settings = array_merge(
	$settings,
	array(
		array(
			'title' => 'General',
			'type'  => 'title',
			'id'    => 'wcj_wishlist_general_options',
		),
		array(
			'title'   => __( 'Add/Remove font awesome icon css', 'woocommerce-jetpack' ),
			'desc'    => __( 'Add/Remove', 'woocommerce-jetpack' ),
			'id'      => 'wcj_wishlist_enabled_font_awesome',
			'default' => 'yes',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Add to wishlist icon color', 'woocommerce-jetpack' ),
			'id'      => 'wcj_add_wishlist_icon_color',
			'default' => '#000000',
			'type'    => 'color',
			'css'     => 'width:6em;',
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		),
		array(
			'title'   => __( 'Added to wishlist icon color', 'woocommerce-jetpack' ),
			'id'      => 'wcj_added_wishlist_icon_color',
			'default' => '#f46c5e',
			'type'    => 'color',
			'css'     => 'width:6em;',
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		),
		array(
			'title'   => __( 'FadeIn/FadeOut add/remove wishlist message', 'woocommerce-jetpack' ),
			'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
			'id'      => 'wcj_wishlist_enabled_msg_fadeinout',
			'default' => 'no',
			'type'    => 'checkbox',
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		),
		array(
			'id'   => 'wcj_wishlist_general_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_wishlist_single_general_tab',
			'type' => 'tab_end',
		),
	)
);

return $settings;
