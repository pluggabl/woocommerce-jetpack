<?php
/**
 * Booster for WooCommerce - Settings - Gateways Fees and Discounts
 *
 * @version 7.0.0
 * @since  1.0.0
 * @author  Pluggabl LLC.
 *  @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$products           = wcj_get_products();
$message            = apply_filters( 'booster_message', '', 'desc' );
$available_gateways = WC()->payment_gateways->payment_gateways();

$tab_ids = array(
	'wcj_gateways_fees_general_options_tab' => __( 'General options', 'woocommerce-jetpack' ),
);

foreach ( $available_gateways as $key => $gateway ) {
	$tab_ids[ 'wcj_gateways_fees_' . $key . '_tab' ] = $gateway->title;
}

$settings = array(
	array(
		'id'   => 'wcj_gateways_fees_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_gateways_fees_options',
		'type'    => 'tab_ids',
		'tab_ids' => $tab_ids,
	),
	array(
		'id'   => 'wcj_gateways_fees_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_gateways_fees',
	),
	array(
		'title'             => __( 'Force Default Payment Gateway', 'woocommerce-jetpack' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		/* translators: %s: translators Added */
		'desc_tip'          => sprintf( __( 'Pre-sets the default available payment gateway on cart and checkout pages.' ) . '<br />' . __( 'The chosen payment will be the first one from the <a href="%s">Payments</a> page', 'woocommerce-jetpack' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ),
		'type'              => 'checkbox',
		'default'           => 'no',
		'id'                => 'wcj_gateways_fees_force_default_payment_gateway',
	),

	array(
		'title'    => __( 'Enable klarna Payment Gateway Charge/Discount', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => sprintf( __( 'If you enable this mode so add Klarna payment gateway charge/discount into your cart total' ) . '<br />' . __( 'If you donot have the Klarna plugin installed first<a href="https://wordpress.org/plugins/klarna-payments-for-woocommerce/">Payments</a>', 'woocommerce-jetpack' ) ),
		'type'     => 'checkbox',
		'default'  => 'no',
		'id'       => 'wcj_enable_payment_gateway_charge_discount',
	),
	array(
		'id'   => 'wcj_gateways_fees',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_gateways_fees_general_options_tab',
		'type' => 'tab_end',
	),
);

foreach ( $available_gateways as $key => $gateway ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'wcj_gateways_fees_' . $key . '_tab',
				'type' => 'tab_start',
			),
			array(
				'title' => $gateway->title . ( $gateway->is_available() ? ' &#10003;' : '' ),
				'type'  => 'title',
				'id'    => "wcj_gateways_fees_options[{$key}]",
			),
			array(
				'title'    => __( 'Fee (or Discount) Title', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Fee (or discount) title to show to customer.', 'woocommerce-jetpack' ) . ' ' . __( 'Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => "wcj_gateways_fees_text[{$key}]",
				'default'  => '',
				'type'     => 'text',

			),
			array(
				'title'    => __( 'Fee (or Discount) Type', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Percent or fixed value.', 'woocommerce-jetpack' ),
				'id'       => "wcj_gateways_fees_type[{$key}]",
				'default'  => 'fixed',
				'type'     => 'select',
				'options'  => array(
					'fixed'   => __( 'Fixed', 'woocommerce-jetpack' ),
					'percent' => __( 'Percent', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'             => __( 'Fee (or Discount) Value', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'The value. For discount enter a negative number.', 'woocommerce-jetpack' ),
				'id'                => "wcj_gateways_fees_value[{$key}]",
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => array( 'step' => '0.01' ),
			),
			array(
				'title'    => __( 'Different Fee/Discount for User Roles', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'If you enable this, it will apply different Fees/Discount based on user role.', 'woocommerce-jetpack' ),
				'type'     => 'checkbox',
				'default'  => 'no',
				'id'       => "wcj_enable_payment_gateway_charge_discount_userwise[{$key}]",
			),
			array(
				'title'             => __( 'Minimum Cart Amount', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Minimum cart amount for adding the fee (or discount).', 'woocommerce-jetpack' ) . ' ' . __( 'Set 0 to disable.', 'woocommerce-jetpack' ),
				'id'                => "wcj_gateways_fees_min_cart_amount[{$key}]",
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '0.01',
					'min'  => '0',
				),
			),
			array(
				'title'             => __( 'Maximum Cart Amount', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Maximum cart amount for adding the fee (or discount).', 'woocommerce-jetpack' ) . ' ' . __( 'Set 0 to disable.', 'woocommerce-jetpack' ),
				'id'                => "wcj_gateways_fees_max_cart_amount[{$key}]",
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '0.01',
					'min'  => '0',
				),
			),
			array(
				'title'    => __( 'Rounding', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Round the fee (or discount) value before adding to the cart.', 'woocommerce-jetpack' ),
				'id'       => "wcj_gateways_fees_round[{$key}]",
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'desc'              => __( 'Number of decimals', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'If rounding is enabled, set precision (i.e. number of decimals) here.', 'woocommerce-jetpack' ),
				'id'                => "wcj_gateways_fees_round_precision[{$key}]",
				'default'           => wcj_get_option( 'woocommerce_price_num_decimals', 2 ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
				),
			),
			array(
				'title'   => __( 'Taxable', 'woocommerce-jetpack' ),
				'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
				'id'      => "wcj_gateways_fees_is_taxable[{$key}]",
				'default' => 'no',
				'type'    => 'checkbox',
			),
			array(
				'desc'     => __( 'Tax class', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'If taxing is enabled, set tax class here.', 'woocommerce-jetpack' ),
				'id'       => "wcj_gateways_fees_tax_class_id[{$key}]",
				'default'  => '',
				'type'     => 'select',
				'options'  => array_merge( array( __( 'Standard Rate', 'woocommerce-jetpack' ) ), WC_Tax::get_tax_classes() ),
			),
			array(
				'title'    => __( 'Exclude Shipping when Calculating Total Cart Amount', 'woocommerce-jetpack' ),
				'desc'     => __( 'Exclude', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'This affects "Percent" type fees and "Minimum/Maximum Cart Amount" options.', 'woocommerce-jetpack' ),
				'id'       => "wcj_gateways_fees_exclude_shipping[{$key}]",
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Include Taxes', 'woocommerce-jetpack' ),
				'desc'     => __( 'Include taxes when calculating Total Cart Amount', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'This affects "Percent" type fees and "Minimum/Maximum Cart Amount" options.', 'woocommerce-jetpack' ),
				'id'       => "wcj_gateways_fees_include_taxes[{$key}]",
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'             => __( 'Require Products', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Require at least one of selected products to be in cart for fee to be applied.', 'woocommerce-jetpack' ) . ' ' .
					__( 'Ignored if empty.', 'woocommerce-jetpack' ),
				'id'                => "wcj_gateways_fees_include_products[{$key}]",
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'options'           => $products,
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
			array(
				'title'             => __( 'Exclude Products', 'woocommerce-jetpack' ),
				'desc_tip'          => __( 'Do not apply fee, if at least one of selected products is in cart.', 'woocommerce-jetpack' ) . ' ' .
					__( 'Ignored if empty.', 'woocommerce-jetpack' ),
				'id'                => "wcj_gateways_fees_exclude_products[{$key}]",
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'options'           => $products,
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
		)
	);
	foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'title'             => wp_kses_post( $role_data['name'], 'woocommerce-jetpack' ),
					'desc_tip'          => __( 'The value. For discount enter a negative number.', 'woocommerce-jetpack' ),
					'id'                => "wcj_gateways_fees_{$role_key}[{$key}]",
					'default'           => 0,
					'type'              => 'number',
					'custom_attributes' => array( 'step' => '0.01' ),
				),
			)
		);
	}
	foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'id'   => "wcj_gateways_fees_options[{$key}]",
					'type' => 'sectionend',
				),
			)
		);

	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'wcj_gateways_fees_' . $key . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
return $settings;
