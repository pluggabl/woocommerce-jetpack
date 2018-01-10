<?php
/**
 * Booster for WooCommerce - Settings - Product Open Pricing
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    set default for "wcj_product_open_price_enable_js_validation" to "yes"
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$default_price_step = 1 / pow( 10, absint( get_option( 'woocommerce_price_num_decimals', 2 ) ) );
return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_open_price_options',
	),
	array(
		'title'    => __( 'Frontend Label', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_label_frontend',
		'default'  => __( 'Name Your Price', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Frontend Template', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Here you can use' ) . ': ' .
			'%frontend_label%, %open_price_input%, %currency_symbol%, %min_price_simple%, %max_price_simple%, %default_price_simple%, %min_price%, %max_price%, %default_price%.',
		'id'       => 'wcj_product_open_price_frontend_template',
		'default'  => '<label for="wcj_open_price">%frontend_label%</label> %open_price_input% %currency_symbol%',
		'type'     => 'textarea',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Frontend Input Style', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_input_style',
		'default'  => 'width:75px;text-align:center;',
		'type'     => 'text',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Frontend Input Placeholder', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_input_placeholder',
		'default'  => '',
		'type'     => 'text',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Frontend Input Price Step', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_price_step',
		'default'  => $default_price_step,
		'type'     => 'number',
		'custom_attributes' => array( 'step' => '0.0001', 'min' => '0.0001' ),
	),
	array(
		'title'    => __( 'Message on Empty Price', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_messages_required',
		'default'  => __( 'Price is required!', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Message on Price too Small', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_messages_to_small',
		'default'  => __( 'Entered price is too small!', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Message on Price too Big', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_messages_to_big',
		'default'  => __( 'Entered price is too big!', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Disable Quantity Input', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_disable_quantity',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Enable JS Min/Max Validation', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_enable_js_validation',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Display Product Price Info in Archives', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_enable_loop_price_info',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'desc'     => __( 'Product price info in archives template. Replaceable values: <code>%default_price%</code>, <code>%min_price%</code>, <code>%max_price%</code>.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_loop_price_info_template',
		'default'  => '<span class="price">%default_price%</span>',
		'type'     => 'custom_textarea',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Add "Open Pricing" Column to Admin Product List', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_open_price_enable_admin_product_list_column',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_open_price_options',
	),
);
