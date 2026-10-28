<?php
/**
 * Booster for WooCommerce - Settings - Order Minimum Amount
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$message  = apply_filters( 'booster_message', '', 'desc' );
$settings = array(
	array(
		'id'   => 'order_min_amount_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'order_min_amount_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'order_min_amount_minimum_amount_tab' => __( 'Minimum Amount', 'woocommerce-jetpack' ),
			'order_min_amount_compatibility_tab'  => __( 'Compatibility', 'woocommerce-jetpack' ),
			'order_min_amount_by_user_role_tab'   => __( 'By User Role', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'order_min_amount_minimum_amount_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Order Minimum Amount', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'This section lets you set minimum order amount.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_order_minimum_amount_options',
	),
	array(
		'title'             => __( 'Amount', 'woocommerce-jetpack' ),
		'desc'              => __( 'Minimum order amount. Set to 0 to disable.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_order_minimum_amount',
		'default'           => 0,
		'type'              => 'number',
		'custom_attributes' => array(
			'step' => '0.0001',
			'min'  => '0',
		),
	),
	array(
		'title'   => __( 'Exclude Shipping from Cart Total', 'woocommerce-jetpack' ),
		'desc'    => __( 'Exclude', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_minimum_amount_exclude_shipping',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Exclude Discounts from Cart Total', 'woocommerce-jetpack' ),
		'desc'    => __( 'Exclude', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_minimum_amount_exclude_discounts',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Exclude Discounts from Yith Gift Cards', 'woocommerce-jetpack' ),
		'desc'              => empty( $message ) ? __( 'Exclude', 'woocommerce-jetpack' ) : $message,
		'id'                => 'wcj_order_minimum_amount_exclude_yith_gift_card_discount',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'title'             => __( 'Error message', 'woocommerce-jetpack' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		/* translators: %s,%s: translators Added */
		'desc_tip'          => __( 'Message to customer if order is below minimum amount. Default: You must have an order with a minimum of %s to place your order, your current order total is %s.', 'woocommerce-jetpack' ),//phpcs:ignore
		'id'                => 'wcj_order_minimum_amount_error_message',
		/* translators: %1$s,2$s: translators Added */
		'default'           => 'You must have an order with a minimum of %s to place your order, your current order total is %s.',
		'type'              => 'textarea',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'css'               => 'width:50%;min-width:300px;',
	),
	array(
		'title'   => __( 'Add notice to cart page also', 'woocommerce-jetpack' ),
		'desc'    => __( 'Add', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_minimum_amount_cart_notice_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Message on cart page', 'woocommerce-jetpack' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		/* translators: %s,%s: translators Added */
		'desc_tip'          => __( 'Message to customer if order is below minimum amount. Default: You must have an order with a minimum of %s to place your order, your current order total is %s.', 'woocommerce-jetpack' ),//phpcs:ignore
		'id'                => 'wcj_order_minimum_amount_cart_notice_message',
		/* translators: %s: translators Added */
		'default'           => 'You must have an order with a minimum of %s to place your order, your current order total is %s.',
		'type'              => 'textarea',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'css'               => 'width:50%;min-width:300px;',
	),
	array(
		'title'   => __( 'Advanced', 'woocommerce-jetpack' ),
		'desc'    => __( 'Cart notice method', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_minimum_amount_cart_notice_function',
		'default' => 'wc_print_notice',
		'type'    => 'select',
		'options' => array(
			'wc_print_notice' => __( 'Print notice', 'woocommerce-jetpack' ),
			'wc_add_notice'   => __( 'Add notice', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'    => __( 'Cart notice type', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_minimum_amount_cart_notice_type',
		'default' => 'notice',
		'type'    => 'select',
		'options' => array(
			'notice' => __( 'Notice', 'woocommerce-jetpack' ),
			'error'  => __( 'Error', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Stop customer from seeing the Checkout page if minimum amount not reached', 'woocommerce-jetpack' ),
		'desc'    => __( 'Redirect back to Cart page', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_minimum_amount_stop_from_seeing_checkout',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'id'   => 'wcj_order_minimum_amount_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'order_min_amount_minimum_amount_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'order_min_amount_compatibility_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Compatibility', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Compatibility with other modules or plugins.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_order_minimum_compatibility',
	),
	array(
		'title'             => __( 'WooCommerce Multilingual', 'woocommerce-jetpack' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		/* translators: %s: translators Added */
		'desc_tip'          => sprintf( __( 'Adds compatibility with <a href="%s" target="_blank">WooCommerce Multilingual</a> plugin.', 'woocommerce-jetpack' ), 'http://wpml.org/documentation/related-projects/woocommerce-multilingual/' ),
		'id'                => 'wcj_order_minimum_compatibility_wpml_multilingual',
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'id'   => 'wcj_order_minimum_compatibility',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'order_min_amount_compatibility_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'order_min_amount_by_user_role_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Order Minimum Amount by User Role', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_order_minimum_amount_by_ser_role_options',
		'desc'  => sprintf(
						/* translators: %s: translators Added */
			__( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module.', 'woocommerce-jetpack' ),
			admin_url( wcj_admin_tab_url() . '&wcj-cat=emails_and_misc&section=general' )
		),
	),
);
$c        = array( 'guest', 'administrator', 'customer' );
$is_r     = apply_filters( 'booster_message', '', 'readonly' );
if ( '' === $is_r ) {
	$is_r = array();
}
foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'             => $role_data['name'],
				'id'                => 'wcj_order_minimum_amount_by_user_role_' . $role_key,
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => ( ! in_array( $role_key, $c, true ) ? array_merge(
					array(
						'step' => '0.0001',
						'min'  => '0',
					),
					$is_r
				) : array(
					'step' => '0.0001',
					'min'  => '0',
				) ),
				'desc_tip'          => ( ! in_array( $role_key, $c, true ) ? apply_filters( 'booster_message', '', 'desc_no_link' ) : '' ),
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_order_minimum_amount_by_ser_role_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'order_min_amount_by_user_role_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
