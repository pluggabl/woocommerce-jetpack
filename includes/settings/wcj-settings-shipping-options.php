<?php
/**
 * Booster for WooCommerce - Settings - Shipping Options
 *
 * @version 3.2.4
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Hide if Free Shipping is Available', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you hide other shipping options when free shipping is available on shop frontend.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_hide_if_free_available_options',
	),
	array(
		'title'    => __( 'Hide when free is available', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable section', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_hide_if_free_available_all',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'       => 'wcj_shipping_hide_if_free_available_type',
		'desc_tip' => sprintf( __( 'Available options: hide all; hide all except "Local Pickup"; hide "Flat Rate" only.', 'woocommerce-jetpack' ) ),
		'default'  => 'hide_all',
		'type'     => 'select',
		'options'  => array(
			'hide_all'            => __( 'Hide all', 'woocommerce-jetpack' ),
			'except_local_pickup' => __( 'Hide all except "Local Pickup"', 'woocommerce-jetpack' ),
			'flat_rate_only'      => __( 'Hide "Flat Rate" only', 'woocommerce-jetpack' ),
		),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Advanced: Filter Priority', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set to zero to use the default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_options_hide_free_shipping_filter_priority',
		'default'  => 0,
		'type'     => 'number',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_hide_if_free_available_options',
	),
);
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Free Shipping by Product', 'woocommerce-jetpack' ),
		'desc'     => __( 'In this section you can select products which grant free shipping when added to cart.', 'woocommerce-jetpack' ) . '<br>' .
			sprintf( __( 'Similar results can be achieved with %s module.', 'woocommerce-jetpack' ),
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=shipping_and_orders&section=shipping_by_products' ) . '">' .
					__( 'Shipping Methods by Products', 'woocommerce-jetpack' ) . '</a>' ),
		'type'     => 'title',
		'id'       => 'wcj_shipping_free_shipping_by_product_options',
	),
	array(
		'title'    => __( 'Free Shipping by Product', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_free_shipping_by_product_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Products', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_free_shipping_by_product_products',
		'default'  => '',
		'type'     => 'multiselect',
		'options'  => wcj_get_products(),
		'class'    => 'chosen_select',
	),
	array(
		'title'    => __( 'Type', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Select either <strong>all products</strong> or <strong>at least one product</strong> in cart must grant free shipping.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_free_shipping_by_product_type',
		'default'  => 'all',
		'type'     => 'select',
		'options'  => array(
			'all'          => __( 'All products in cart must grant free shipping', 'woocommerce-jetpack' ),
			'at_least_one' => __( 'At least one product in cart must grant free shipping', 'woocommerce-jetpack' ),
		),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_free_shipping_by_product_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Shipping Descriptions', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => sprintf( __( 'This section will allow you to add any text (e.g. description) for shipping method. Text will be visible on cart and checkout pages. You can add HTML tags here, e.g. try "%s"', 'woocommerce-jetpack' ), esc_html( '<br><small>Your shipping description.</small>' ) ),
		'id'       => 'wcj_shipping_description_options',
	),
	array(
		'title'    => __( 'Shipping Descriptions', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_description_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Description Visibility', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_descriptions_visibility',
		'default'  => 'both',
		'type'     => 'select',
		'options'  => array(
			'both'          => __( 'On both cart and checkout pages', 'woocommerce-jetpack' ),
			'cart_only'     => __( 'Only on cart page', 'woocommerce-jetpack' ),
			'checkout_only' => __( 'Only on checkout page', 'woocommerce-jetpack' ),
		),
		'desc_tip' => __( 'Possible values: on both cart and checkout pages; only on cart page; only on checkout page', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
) );
foreach ( WC()->shipping->get_shipping_methods() as $method ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => $method->method_title,
			'id'       => 'wcj_shipping_description_' . $method->id,
			'default'  => '',
			'type'     => 'textarea',
			'css'      => 'width:30%;min-width:300px;',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_description_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Shipping Icons', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section will allow you to add icons for shipping method. Icons will be visible on cart and checkout pages.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_icons_options',
	),
	array(
		'title'    => __( 'Shipping Icons', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_icons_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Icon Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_icons_position',
		'default'  => 'before',
		'type'     => 'select',
		'options'  => array(
			'before' => __( 'Before label', 'woocommerce-jetpack' ),
			'after'  => __( 'After label', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Icon Visibility', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_icons_visibility',
		'default'  => 'both',
		'type'     => 'select',
		'options'  => array(
			'both'          => __( 'On both cart and checkout pages', 'woocommerce-jetpack' ),
			'cart_only'     => __( 'Only on cart page', 'woocommerce-jetpack' ),
			'checkout_only' => __( 'Only on checkout page', 'woocommerce-jetpack' ),
		),
		'desc_tip' => __( 'Possible values: on both cart and checkout pages; only on cart page; only on checkout page', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Icon Style', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can also style icons with CSS class "wcj_shipping_icon", or id "wcj_shipping_icon_method_id"', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_icons_style',
		'default'  => 'display:inline;',
		'type'     => 'text',
		'css'      => 'width:20%;min-width:300px;',
	),
) );
foreach ( WC()->shipping->get_shipping_methods() as $method ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => $method->method_title,
			'desc_tip' => __( 'Image URL', 'woocommerce-jetpack' ),
			'id'       => 'wcj_shipping_icon_' . $method->id,
			'default'  => '',
			'type'     => 'text',
			'css'      => 'width:30%;min-width:300px;',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_icons_options',
	),
) );
return $settings;
