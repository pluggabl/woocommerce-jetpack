<?php
/**
 * Booster for WooCommerce - Settings - Order Min/Max Quantities
 *
 * @version 7.2.5
 * @since   2.9.0
 * @author  Pluggabl LLC.
 * @todo    (maybe) generate settings in loop ( min / max )
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$is_multiselect_products = ( 'yes' === wcj_get_option( 'wcj_list_for_products', 'yes' ) );
$products                = ( $is_multiselect_products ? wcj_get_products() : false );
do_action( 'wcj_after_get_products', $this->id );

$qty_step_settings = ( 'yes' === wcj_get_option( 'wcj_order_quantities_decimal_qty_enabled', 'no' ) ? '0.000001' : '1' );

return array(
	array(
		'id'   => 'order_quantities_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'order_quantities_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'order_quantities_general_options_tab'  => __( 'General Options', 'woocommerce-jetpack' ),
			'order_quantities_min_qty_tab'          => __( 'Min Qty', 'woocommerce-jetpack' ),
			'order_quantities_max_qty_tab'          => __( 'Max Qty', 'woocommerce-jetpack' ),
			'order_quantities_qty_step_tab'         => __( 'Qty Step', 'woocommerce-jetpack' ),
			'order_quantities_single_item_cart_tab' => __( 'Single Item Cart', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'order_quantities_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_order_quantities_general_options',
	),
	array(
		'title'    => __( 'Decimal Quantities', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Save module\'s settings after enabling this option, so you could enter decimal quantities in step, min and/or max quantity options.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_decimal_qty_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Force Initial Quantity on Single Product Page', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_quantities_force_on_single',
		'default' => 'disabled',
		'type'    => 'select',
		'options' => array(
			'disabled' => __( 'Do not force', 'woocommerce-jetpack' ),
			'min'      => __( 'Force to min quantity', 'woocommerce-jetpack' ),
			'max'      => __( 'Force to max quantity', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Enable Cart Notices', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_quantities_cart_notice_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'    => __( 'Stop Customer from Seeing Checkout on Wrong Quantities', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Will be redirected to cart page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_stop_from_seeing_checkout',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Variable Products', 'woocommerce-jetpack' ),
		'desc'    => '<br>' . __( 'Action on variation change', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_quantities_variable_variation_change',
		'default' => 'do_nothing',
		'type'    => 'select',
		'options' => array(
			'do_nothing'   => __( 'Do nothing', 'woocommerce-jetpack' ),
			'reset_to_min' => __( 'Reset to min quantity', 'woocommerce-jetpack' ),
			'reset_to_max' => __( 'Reset to max quantity', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'Force on add to cart', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Force quantity correction on add to cart button click', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_quantities_variable_force_on_add_to_cart',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'   => 'wcj_order_quantities_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'order_quantities_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'order_quantities_min_qty_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Minimum Quantity Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_order_quantities_min_options',
	),
	array(
		'title'   => __( 'Minimum Quantity', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_order_quantities_min_section_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Cart Total Quantity', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Set to zero to disable.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_order_quantities_min_cart_total_quantity',
		'default'           => 0,
		'type'              => 'number',
		'custom_attributes' => array(
			'min'  => 0,
			'step' => $qty_step_settings,
		),
	),
	wcj_get_settings_as_multiselect_or_text(
		array(
			'title'    => __( 'Exclude Products from Cart Total Quantity count', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Set this field to NOT apply quantity of selected products to cart total quantity count. Leave blank to apply to all products.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_order_quantities_min_cart_total_quantity_exclude_product',
			'default'  => '',
			'class'    => 'widefat',
		),
		$products,
		$is_multiselect_products
	),
	array(
		'title'   => __( 'Message - Cart Total Quantity', 'woocommerce-jetpack' ),
		'desc'    => wcj_message_replaced_values( array( '%min_cart_total_quantity%', '%cart_total_quantity%' ) ),
		'id'      => 'wcj_order_quantities_min_cart_total_message',
		/* translators: %min_cart_total_quantity%: translators Added */
		'default' => __( 'Minimum allowed order quantity is %min_cart_total_quantity%. Your current order quantity is %cart_total_quantity%.', 'woocommerce-jetpack' ),
		'type'    => 'textarea',
		'css'     => 'width:100%;',
	),
	array(
		'title'             => __( 'Per Item Quantity', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Set to zero to disable.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_order_quantities_min_per_item_quantity',
		'default'           => 0,
		'type'              => 'number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => ( '' === apply_filters( 'booster_message', '', 'readonly' ) ? array(
			'min'  => 0,
			'step' => $qty_step_settings,
		) : apply_filters( 'booster_message', '', 'readonly' ) ),
	),
	array(
		'title'    => __( 'Add all quantities if quantity lower than the minimum quantity', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If you want allow product add to cart with remain quantities if product quantities less than minimum quantities', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_quantities_lower_than_min_cart_total_quantity',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'             => __( 'Per Item Quantity on Per Product Basis', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'This will add meta box to each product\'s edit page.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'                => 'wcj_order_quantities_min_per_item_quantity_per_product',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'   => __( 'Message - Per Item Quantity', 'woocommerce-jetpack' ),
		'desc'    => wcj_message_replaced_values( array( '%product_title%', '%min_per_item_quantity%', '%item_quantity%' ) ),
		'id'      => 'wcj_order_quantities_min_per_item_message',
		'default' => __( 'Minimum allowed quantity for %product_title% is %min_per_item_quantity%. Your current item quantity is %item_quantity%.', 'woocommerce-jetpack' ),
		'type'    => 'textarea',
		'css'     => 'width:100%;',
	),
	array(
		'id'   => 'wcj_order_quantities_min_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'order_quantities_min_qty_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'order_quantities_max_qty_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Maximum Quantity Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_order_quantities_max_options',
	),
	array(
		'title'   => __( 'Maximum Quantity', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_order_quantities_max_section_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Cart Total Quantity', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Set to zero to disable.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_order_quantities_max_cart_total_quantity',
		'default'           => 0,
		'type'              => 'number',
		'custom_attributes' => array(
			'min'  => 0,
			'step' => $qty_step_settings,
		),
	),
	array(
		'title'   => __( 'Message - Cart Total Quantity', 'woocommerce-jetpack' ),
		'desc'    => wcj_message_replaced_values( array( '%max_cart_total_quantity%', '%cart_total_quantity%' ) ),
		'id'      => 'wcj_order_quantities_max_cart_total_message',
		/* translators: %max_cart_total_quantity%: translators Added */
		'default' => __( 'Maximum allowed order quantity is %max_cart_total_quantity%. Your current order quantity is %cart_total_quantity%.', 'woocommerce-jetpack' ),
		'type'    => 'textarea',
		'css'     => 'width:100%;',
	),
	array(
		'title'             => __( 'Per Item Quantity', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Set to zero to disable.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_order_quantities_max_per_item_quantity',
		'default'           => 0,
		'type'              => 'number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => ( '' === apply_filters( 'booster_message', '', 'readonly' ) ? array(
			'min'  => 0,
			'step' => $qty_step_settings,
		) : apply_filters( 'booster_message', '', 'readonly' ) ),
	),
	array(
		'title'             => __( 'Check Product Quantity Forcefully', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Enable this option if you want to not allow user to product add to cart if user already reached the Max Quantity limit', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'                => 'wcj_order_quantities_check_product_quantity_forcefully',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Per Item Quantity on Per Product Basis', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'This will add meta box to each product\'s edit page.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'                => 'wcj_order_quantities_max_per_item_quantity_per_product',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'   => __( 'Message - Per Item Quantity', 'woocommerce-jetpack' ),
		'desc'    => wcj_message_replaced_values( array( '%product_title%', '%max_per_item_quantity%', '%item_quantity%' ) ),
		'id'      => 'wcj_order_quantities_max_per_item_message',
		'default' => __( 'Maximum allowed quantity for %product_title% is %max_per_item_quantity%. Your current item quantity is %item_quantity%.', 'woocommerce-jetpack' ),
		'type'    => 'textarea',
		'css'     => 'width:100%;',
	),
	array(
		'id'   => 'wcj_order_quantities_max_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'order_quantities_max_qty_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'order_quantities_qty_step_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Quantity Step Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_order_quantities_step_options',
	),
	array(
		'title'   => __( 'Quantity Step', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_order_quantities_step_section_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Step', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Ignored if set to zero.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_order_quantities_step',
		'default'           => 1,
		'type'              => 'number',
		'custom_attributes' => array(
			'min'  => 0,
			'step' => $qty_step_settings,
		),
	),
	array(
		'title'             => __( 'Per Product', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'This will add meta box to each product\'s edit page.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'                => 'wcj_order_quantities_step_per_product',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'   => __( 'Additional Validation', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_quantities_step_additional_validation_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Message', 'woocommerce-jetpack' ),
		'desc'    => wcj_message_replaced_values( array( '%product_title%', '%required_step%', '%item_quantity%' ) ),
		'id'      => 'wcj_order_quantities_step_message',
		'default' => __( 'Required step for %product_title% is %required_step%. Your current item quantity is %item_quantity%.', 'woocommerce-jetpack' ),
		'type'    => 'textarea',
		'css'     => 'width:100%;',
	),
	array(
		'id'   => 'wcj_order_quantities_step_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'order_quantities_qty_step_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'order_quantities_single_item_cart_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( '"Single Item Cart" Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_order_quantities_single_item_cart_options',
	),
	array(
		'title'             => __( 'Enable "Single Item Cart" Mode', 'woocommerce-jetpack' ),
		'desc'              => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'desc_tip'          => __( 'When enabled, only one item will be allowed to be added to the cart (quantity is not checked).', 'woocommerce-jetpack' ) . ' ' .
			apply_filters( 'booster_message', '', 'desc' ),
		'id'                => 'wcj_order_quantities_single_item_cart_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'   => __( 'Message', 'woocommerce-jetpack' ),
		'id'      => 'wcj_order_quantities_single_item_cart_message',
		'default' => __( 'Only one item can be added to the cart. Clear the cart or finish the order, before adding another item to the cart.', 'woocommerce-jetpack' ),
		'type'    => 'textarea',
		'css'     => 'width:100%;',
	),
	array(
		'id'   => 'wcj_order_quantities_single_item_cart_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'order_quantities_single_item_cart_tab',
		'type' => 'tab_end',
	),
);
