<?php
/**
 * Booster for WooCommerce - Settings - Product Add To Cart
 *
 * @version 3.1.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Add to Cart Local Redirect', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you set any local URL to redirect to after successfully adding product to cart.', 'woocommerce-jetpack' ) . ' ' .
			sprintf(
				__( 'For archives - "Enable AJAX add to cart buttons on archives" checkbox in <a href="%s">WooCommerce > Settings > Products > Display</a> must be disabled.', 'woocommerce-jetpack' ),
				admin_url( 'admin.php?page=wc-settings&tab=products&section=display' )
			),
		'id'       => 'wcj_add_to_cart_redirect_options',
	),
	array(
		'title'    => __( 'All Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_redirect_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
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
		'title'    => __( 'Per Product', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add meta boxes to each product\'s edit page.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_get_message', '', 'desc' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_redirect_per_product_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_redirect_options',
	),
	array(
		'title'    => __( 'Add to Cart on Visit', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you enable automatically adding product to cart on visiting the product page. Product is only added once, so if it is already in cart - duplicate product is not added. ', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_on_visit_options',
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
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_on_visit_options',
	),
	array(
		'title'    => __( 'Add to Cart Variable Product', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_add_to_cart_variable_options',
	),
	array(
		'title'    => __( 'Display Radio Buttons Instead of Drop Box', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_variable_as_radio_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
	),
	array(
		'title'    => __( 'Variation Label Template', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%variation_title%', '%variation_price%' ) ),
		'id'       => 'wcj_add_to_cart_variable_as_radio_variation_label_template',
		'default'  => '%variation_title% (%variation_price%)',
		'type'     => 'custom_textarea',
		'css'      => 'width:99%;',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc_no_link' ),
	),
	array(
		'title'    => __( 'Variation Description Template', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%variation_description%' ) ),
		'id'       => 'wcj_add_to_cart_variable_as_radio_variation_desc_template',
		'default'  => '<br><small>%variation_description%</small>',
		'type'     => 'custom_textarea',
		'css'      => 'width:99%;',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc_no_link' ),
	),
	array(
		'title'    => __( 'Variation Radio Input td Style', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_variable_as_radio_input_td_style',
		'default'  => 'width:10%;',
		'type'     => 'text',
		'css'      => 'width:99%;',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc_no_link' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_variable_options',
	),
	array(
		'title'    => __( 'Replace Add to Cart Button on Archives with Single', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_add_to_cart_replace_loop_w_single_options',
	),
	array(
		'title'    => __( 'Replace Add to Cart Button on Archives with Button from Single Product Pages', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_replace_loop_w_single_enabled',
		'default'  => 'no',
		'type'     => 'select',
		'options'  => array(
			'no'            => __( 'Disable', 'woocommerce-jetpack' ),
			'yes'           => __( 'Enable', 'woocommerce-jetpack' ),
			'variable_only' => __( 'Variable products only', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_replace_loop_w_single_options',
	),
	array(
		'title'    => __( 'Add to Cart Quantity', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_add_to_cart_quantity_options',
	),
	array(
		'title'    => __( 'Disable Quantity Field for All Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable on Single Product Page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_quantity_disable',
		'default'  => 'no',
		'type'     => 'checkbox',
		'checkboxgroup' => 'start',
	),
	array(
		'desc'     => __( 'Disable on Cart Page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_quantity_disable_cart',
		'default'  => 'no',
		'type'     => 'checkbox',
		'checkboxgroup' => 'end',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_quantity_options',
	),
	array(
		'title'    => __( 'Add to Cart Button Disabling', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_add_to_cart_button_options',
	),
	array(
		'title'    => __( 'Disable Add to Cart Buttons on per Product Basis', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add meta box to each product\'s edit page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_per_product_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Add to Cart Buttons on All Category/Archives Pages', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable Buttons', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_disable_archives',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Add to Cart Buttons on All Single Product Pages', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable Buttons', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_disable_single',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_button_options',
	),
	array(
		'title'    => __( 'Add to Cart Button Custom URL', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_add_to_cart_button_custom_url_options',
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
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_button_custom_url_options',
	),
	array(
		'title'    => __( 'Add to Cart Button AJAX', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_add_to_cart_button_ajax_options',
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
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_button_ajax_options',
	),
	array(
		'title'    => __( 'External Products', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_add_to_cart_button_external_product_options',
	),
	array(
		'title'    => __( 'Open External Products on Add to Cart in New Window', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable on Single Product Pages', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_external_open_new_window_single',
		'default'  => 'no',
		'type'     => 'checkbox',
		'checkboxgroup' => 'start',
	),
	array(
		'desc'     => __( 'Enable on Category/Archive Pages', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_external_open_new_window_loop',
		'default'  => 'no',
		'type'     => 'checkbox',
		'checkboxgroup' => 'end',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_button_external_product_options',
	),
	array(
		'title'    => __( 'Add to Cart Message Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_add_to_cart_message_options',
	),
	array(
		'title'    => __( 'Change "Continue shopping" Text', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_add_to_cart_message_continue_shopping_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
	),
	array(
		'id'       => 'wcj_product_add_to_cart_message_continue_shopping_text',
		'default'  => __( 'Continue shopping', 'woocommerce' ),
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Change "View cart" Text', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_add_to_cart_message_view_cart_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
	),
	array(
		'id'       => 'wcj_product_add_to_cart_message_view_cart_text',
		'default'  => __( 'View cart', 'woocommerce' ),
		'type'     => 'text',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_add_to_cart_message_options',
	),
);
