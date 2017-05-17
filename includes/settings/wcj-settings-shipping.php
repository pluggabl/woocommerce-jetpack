<?php
/**
 * Booster for WooCommerce - Settings - Shipping
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$wocommerce_shipping_settings_url = admin_url( 'admin.php?page=wc-settings&tab=shipping' );
$wocommerce_shipping_settings_url = '<a href="' . $wocommerce_shipping_settings_url . '">' . __( 'WooCommerce > Settings > Shipping', 'woocommerce-jetpack' ) . '</a>';
$settings = array(
	array(
		'title'    => __( 'Custom Shipping', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_shipping_custom_shipping_w_zones_options',
		'desc'     => __( 'This section lets you add custom shipping method.', 'woocommerce-jetpack' )
			. ' ' . sprintf( __( 'Visit %s to set method\'s options.', 'woocommerce-jetpack' ), $wocommerce_shipping_settings_url ),
	),
	array(
		'title'    => __( 'Custom Shipping', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_custom_shipping_w_zones_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Admin Title', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_custom_shipping_w_zones_admin_title',
		'default'  => __( 'Booster: Custom Shipping', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_custom_shipping_w_zones_options',
	),
);
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Custom Shipping (Legacy - without Shipping Zones)', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_shipping_custom_shipping_options',
		'desc'     => __( 'This section lets you set number of custom shipping methods to add.', 'woocommerce-jetpack' )
			. ' ' . sprintf( __( 'After setting the number, visit %s to set each method options.', 'woocommerce-jetpack' ), $wocommerce_shipping_settings_url ),
	),
	array(
		'title'    => __( 'Custom Shipping Methods Number', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Save module\'s settings after changing this option to see new settings fields.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_custom_shipping_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'custom_attributes' => array( 'step' => '1', 'min' => '0' ),
	),
) );
for ( $i = 1; $i <= get_option( 'wcj_shipping_custom_shipping_total_number', 1 ); $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Admin Title Custom Shipping', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'       => 'wcj_shipping_custom_shipping_admin_title_' . $i,
			'default'  => __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $i,
			'type'     => 'text',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_custom_shipping_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Hide if Free is Available', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you hide other shipping options when free shipping is available on shop frontend.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_hide_if_free_available_options',
	),
	/*
	array(
		'title'    => __( 'Hide shipping', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
		'default'  => 'no',
		'type'     => 'checkbox',
		'checkboxgroup' => 'start',
	),
	*/
	array(
		'title'    => __( 'Hide shipping', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
		'id'       => 'wcj_shipping_hide_if_free_available_all',
		'default'  => 'no',
		'type'     => 'checkbox',
//		'checkboxgroup' => 'end',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_shipping_hide_if_free_available_options',
	),
) );
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Free Shipping by Product', 'woocommerce-jetpack' ),
		'desc'     => __( 'In this section you can select products which grant free shipping when added to cart.', 'woocommerce-jetpack' ),
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
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
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
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
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
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
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
