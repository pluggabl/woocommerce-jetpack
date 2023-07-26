<?php
/**
 * Booster for WooCommerce - Settings - Product Add To Cart
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'id'   => 'wcj_add_to_cart_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_add_to_cart_general_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_add_to_cart_local_redirect'    => __( 'Local Redirect', 'woocommerce-jetpack' ),
			'wcj_add_to_cart_on_visit'          => __( 'On Visit', 'woocommerce-jetpack' ),
			'wcj_add_to_cart_variable_product'  => __( 'Variable Product', 'woocommerce-jetpack' ),
			'wcj_add_to_cart_replace_button'    => __( 'Replace Button on Archives with Single', 'woocommerce-jetpack' ),
			'wcj_add_to_cart_quantity'          => __( 'Quantity', 'woocommerce-jetpack' ),
			'wcj_add_to_cart_custom_url'        => __( 'Button Custom URL', 'woocommerce-jetpack' ),
			'wcj_add_to_cart_ajax'              => __( 'Button AJAX', 'woocommerce-jetpack' ),
			'wcj_add_to_cart_external_products' => __( 'External Products', 'woocommerce-jetpack' ),
			'wcj_add_to_cart_message_options'   => __( 'Message Options', 'woocommerce-jetpack' ),
			'wcj_add_to_cart_position_options'  => __( 'Position Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_add_to_cart_local_redirect',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Add to Cart Local Redirect', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'This section lets you set any local URL to redirect to after successfully adding product to cart.', 'woocommerce-jetpack' ) . ' ' .
			sprintf(
				/* translators: %s: translators Added */
				__( 'For archives - "Enable AJAX add to cart buttons on archives" checkbox in <a href="%s">WooCommerce > Settings > Products > Display</a> must be disabled.', 'woocommerce-jetpack' ),
				admin_url( 'admin.php?page=wc-settings&tab=products&section=display' )
			),
		'id'    => 'wcj_add_to_cart_redirect_options',
	),
	array(
		'title'   => __( 'All Products', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_add_to_cart_redirect_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'desc'     => __( 'URL - All Products', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Redirect URL. Leave empty to redirect to checkout page (skipping the cart page).', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_redirect_url',
		'default'  => '',
		'type'     => 'text',
		'css'      => 'width:50%;min-width:300px;',
	),
	array(
		'title'             => __( 'Per Product', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'This will add meta boxes to each product\'s edit page.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'id'                => 'wcj_add_to_cart_redirect_per_product_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'   => 'wcj_add_to_cart_on_visit_enabled',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_add_to_cart_local_redirect',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_add_to_cart_on_visit',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Add to Cart on Visit', 'woocommerce-jetpack' ),
		'desc'  => __( 'This section lets you enable automatically adding product to cart on visiting the product page. Product is only added once, so if it is already in cart – duplicate product is not added.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_add_to_cart_on_visit_enabled_title',
		'type'  => 'title',
	),
	array(
		'title'    => __( 'Add to Cart on Visit', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If "Per Product" is selected - meta box will be added to each product\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_on_visit_enabled',
		'default'  => 'no',
		'type'     => 'select',
		'options'  => array(
			'no'          => __( 'Disabled', 'woocommerce-jetpack' ),
			'yes'         => __( 'All products', 'woocommerce-jetpack' ),
			'per_product' => __( 'Per product', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_add_to_cart_variable_as_radio_enabled',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_add_to_cart_on_visit',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_add_to_cart_variable_product',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Add to Cart Variable Product', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_add_to_cart_variable_options',
	),
	array(
		'title'             => __( 'Display Radio Buttons Instead of Drop Box', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'id'                => 'wcj_add_to_cart_variable_as_radio_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'             => __( 'Variation Label Template', 'woocommerce-jetpack' ),
		'desc'              => wcj_message_replaced_values( array( '%variation_title%', '%variation_price%' ) ),
		'id'                => 'wcj_add_to_cart_variable_as_radio_variation_label_template',
		'default'           => '%variation_title% (%variation_price%)',
		'type'              => 'textarea',
		'css'               => 'width:99%;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc_no_link' ),
	),
	array(
		'title'             => __( 'Variation Description Template', 'woocommerce-jetpack' ),
		'desc'              => wcj_message_replaced_values( array( '%variation_description%' ) ),
		'id'                => 'wcj_add_to_cart_variable_as_radio_variation_desc_template',
		'default'           => '<br><small>%variation_description%</small>',
		'type'              => 'textarea',
		'css'               => 'width:99%;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc_no_link' ),
	),
	array(
		'title'             => __( 'Variation Radio Input td Style', 'woocommerce-jetpack' ),
		'id'                => 'wcj_add_to_cart_variable_as_radio_input_td_style',
		'default'           => 'width:10%;',
		'type'              => 'text',
		'css'               => 'width:99%;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc_no_link' ),
	),
	array(
		'id'   => 'wcj_add_to_cart_replace_loop_w_single_enabled',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_add_to_cart_variable_product',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_add_to_cart_replace_button',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Replace Add to Cart Button on Archives with Single', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_add_to_cart_replace_loop_w_single_options',
	),
	array(
		'title'   => __( 'Replace Add to Cart Button on Archives with Button from Single Product Pages', 'woocommerce-jetpack' ),
		'id'      => 'wcj_add_to_cart_replace_loop_w_single_enabled',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'no'            => __( 'Disable', 'woocommerce-jetpack' ),
			'yes'           => __( 'Enable', 'woocommerce-jetpack' ),
			'variable_only' => __( 'Variable products only', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_add_to_cart_quantity_disable',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_add_to_cart_replace_button',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_add_to_cart_quantity',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Add to Cart Quantity', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_add_to_cart_quantity_options',
	),
	array(
		'title'         => __( 'Disable Quantity Field for All Products', 'woocommerce-jetpack' ),
		'desc'          => __( 'Disable on Single Product Page', 'woocommerce-jetpack' ),
		'id'            => 'wcj_add_to_cart_quantity_disable',
		'default'       => 'no',
		'type'          => 'checkbox',
		'checkboxgroup' => 'start',
	),
	array(
		'desc'          => __( 'Disable on Cart Page', 'woocommerce-jetpack' ),
		'id'            => 'wcj_add_to_cart_quantity_disable_cart',
		'default'       => 'no',
		'type'          => 'checkbox',
		'checkboxgroup' => 'end',
	),
	array(
		'title'   => __( 'Set All Products to "Sold individually"', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_add_to_cart_quantity_sold_individually_all',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'id'   => 'wcj_add_to_cart_button_custom_loop_url_per_product_enabled',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_add_to_cart_quantity',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_add_to_cart_custom_url',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Add to Cart Button Custom URL', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_add_to_cart_button_custom_url_options',
	),
	array(
		'title'    => __( 'Custom Add to Cart Buttons URL on Archives on per Product Basis', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add meta box to each product\'s edit page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_custom_loop_url_per_product_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'   => 'wcj_add_to_cart_button_ajax_per_product_enabled',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_add_to_cart_custom_url',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_add_to_cart_ajax',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Add to Cart Button AJAX', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_add_to_cart_button_ajax_options',
	),
	array(
		'title'    => __( 'Disable/Enable Add to Cart Button AJAX on per Product Basis', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add meta box to each product\'s edit page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_ajax_per_product_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'   => 'wcj_add_to_cart_button_external_open_new_window_loop',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_add_to_cart_ajax',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_add_to_cart_external_products',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'External Products', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_add_to_cart_button_external_product_options',
	),
	array(
		'title'         => __( 'Open External Products on Add to Cart in New Window', 'woocommerce-jetpack' ),
		'desc'          => __( 'Enable on Single Product Pages', 'woocommerce-jetpack' ),
		'id'            => 'wcj_add_to_cart_button_external_open_new_window_single',
		'default'       => 'no',
		'type'          => 'checkbox',
		'checkboxgroup' => 'start',
	),
	array(
		'desc'          => __( 'Enable on Category/Archive Pages', 'woocommerce-jetpack' ),
		'id'            => 'wcj_add_to_cart_button_external_open_new_window_loop',
		'default'       => 'no',
		'type'          => 'checkbox',
		'checkboxgroup' => 'end',
	),
	array(
		'id'   => 'wcj_product_add_to_cart_message_continue_shopping_enabled',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_add_to_cart_external_products',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_add_to_cart_message_options',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Add to Cart Message Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_product_add_to_cart_message_options',
	),
	array(
		'title'             => __( 'Change "Continue shopping" Text', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_add_to_cart_message_continue_shopping_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'id'      => 'wcj_product_add_to_cart_message_continue_shopping_text',
		'default' => __( 'Continue shopping', 'woocommerce' ),
		'type'    => 'text',
	),
	array(
		'title'             => __( 'Change "View cart" Text', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_add_to_cart_message_view_cart_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'id'      => 'wcj_product_add_to_cart_message_view_cart_text',
		'default' => __( 'View cart', 'woocommerce' ),
		'type'    => 'text',
	),
	array(
		'id'   => 'wcj_product_add_to_cart_button_position_enabled',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_add_to_cart_message_options',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_add_to_cart_position_options',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Add to Cart Button Position Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_product_add_to_cart_button_position_options',
	),
	array(
		'title'   => __( 'Add to Cart Button Position', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_product_add_to_cart_button_position_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Reposition Button on Single Product Pages', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_add_to_cart_button_position_single_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Position', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_add_to_cart_button_position_hook_single',
		'default' => 'woocommerce_single_product_summary',
		'type'    => 'select',
		'options' => array(
			'woocommerce_before_single_product_summary' => __( 'Before single product summary', 'woocommerce-jetpack' ),
			'woocommerce_single_product_summary'        => __( 'Inside single product summary', 'woocommerce-jetpack' ),
			'woocommerce_after_single_product_summary'  => __( 'After single product summary', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'Priority', 'woocommerce-jetpack' ),
		'desc_tip' => sprintf(
			/* translators: %s: translators Added */
			__( 'Here are the default WooCommerce priorities for "Inside single product summary" position: %s', 'woocommerce-jetpack' ),
			implode(
				', ',
				array(
					'5 - ' . __( 'Title', 'woocommerce-jetpack' ),
					'10 - ' . __( 'Rating', 'woocommerce-jetpack' ),
					'10 - ' . __( 'Price', 'woocommerce-jetpack' ),
					'20 - ' . __( 'Excerpt', 'woocommerce-jetpack' ),
					'40 - ' . __( 'Meta', 'woocommerce-jetpack' ),
					'50 - ' . __( 'Sharing', 'woocommerce-jetpack' ),
					'30 - ' . __( 'Add to Cart', 'woocommerce-jetpack' ),
				)
			)
		),
		'id'       => 'wcj_product_add_to_cart_button_position_single',
		'default'  => 30,
		'type'     => 'number',
	),
	array(
		'title'   => __( 'Reposition Button on Category/Archive Pages', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_add_to_cart_button_position_loop_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Position', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_add_to_cart_button_position_hook_loop',
		'default' => 'woocommerce_after_shop_loop_item',
		'type'    => 'select',
		'options' => array(
			'woocommerce_before_shop_loop_item'       => __( 'Before product', 'woocommerce-jetpack' ),
			'woocommerce_before_shop_loop_item_title' => __( 'Before product title', 'woocommerce-jetpack' ),
			'woocommerce_after_shop_loop_item'        => __( 'After product', 'woocommerce-jetpack' ),
			'woocommerce_after_shop_loop_item_title'  => __( 'After product title', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'    => __( 'Priority', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_add_to_cart_button_position_loop',
		'default' => 10,
		'type'    => 'number',
	),
	array(
		'id'   => 'wcj_product_add_to_cart_button_position_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_add_to_cart_position_options',
		'type' => 'tab_end',
	),
);
