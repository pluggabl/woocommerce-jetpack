<?php
/**
 * Booster for WooCommerce - Settings - Max Products per User
 *
 * @version 5.6.8
 * @since   3.5.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'title' => __( 'All Products', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_max_products_per_user_global_options',
	),
	array(
		'title'   => __( 'All Products', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_max_products_per_user_global_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Maximum Allowed Each Product\'s Quantity per User', 'woocommerce-jetpack' ),
		'id'                => 'wcj_max_products_per_user_global_max_qty',
		'default'           => 1,
		'type'              => 'number',
		'custom_attributes' => array( 'min' => 1 ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_max_products_per_user_global_options',
	),
	array(
		'title' => __( 'Per Product', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_max_products_per_user_local_options',
	),
	array(
		'title'             => __( 'Per Product', 'woocommerce-jetpack' ),
		'desc'              => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'desc_tip'          => __( 'This will add new meta box to each product\'s edit page.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'                => 'wcj_max_products_per_user_local_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_max_products_per_user_local_options',
	),
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_max_products_per_user_general_options',
	),
	array(
		'title'    => __( 'Order Status', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This sets when (i.e. on which order status) users\' quantities should be updated.', 'woocommerce-jetpack' ) . ' ' .
			__( 'You can select multiple order status here - quantities will be updated only once, on whichever status is triggered first.', 'woocommerce-jetpack' ) . ' ' .
			__( 'If no status are selected - "Completed" order status is used.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_max_products_per_user_order_status',
		'default'  => array( 'wc-completed' ),
		'options'  => wcj_get_order_statuses( false ),
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
	),
	array(
		'title'   => __( 'Customer Message', 'woocommerce-jetpack' ),
		'desc'    => wcj_message_replaced_values( array( '%max_qty%', '%product_title%', '%qty_already_bought%', '%remaining_qty%' ) ),
		'id'      => 'wcj_max_products_per_user_message',
		'default' => __( 'You can only buy maximum %max_qty% pcs. of %product_title% (you already bought %qty_already_bought% pcs.).', 'woocommerce-jetpack' ),
		'type'    => 'custom_textarea',
		'css'     => 'width:100%;height:100px;',
	),
	array(
		'title'    => __( 'Block Add to Cart', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will stop customer from adding product to cart on exceeded quantities.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_max_products_per_user_stop_from_adding_to_cart',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Block Checkout Page', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will stop customer from accessing the checkout page on exceeded quantities. Customer will be redirected to the cart page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_max_products_per_user_stop_from_seeing_checkout',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Calculate Data', 'woocommerce-jetpack' ),
		'id'      => 'wcj_max_products_per_user_calculate_data',
		'default' => '',
		'type'    => 'custom_link',
		'link'    => '<a class="button" href="' .
		esc_url_raw( add_query_arg( 'wcj_max_products_per_user_calculate_data', '1', remove_query_arg( 'wcj_max_products_per_user_calculate_data_finished' ) ) ) . '">' .
				__( 'Calculate Data', 'woocommerce-jetpack' ) .
			'</a>',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_max_products_per_user_general_options',
	),
);
