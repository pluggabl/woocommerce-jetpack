<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Max Products per User
 *
 * @version 5.6.0
 * @since   3.5.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'title'             => __( 'Max Qty', 'woocommerce-jetpack' ),
		'name'              => 'wcj_max_products_per_user_qty',
		'default'           => 0,
		'type'              => 'number',
		'tooltip'           => __( 'If set to zero, and "All Products" section is enabled - global maximum quantity will be used; in case if "All Products" section is disabled - no maximum quantity will be used.', 'woocommerce-jetpack' ),
		'custom_attributes' => 'min="0"',
	),
);
