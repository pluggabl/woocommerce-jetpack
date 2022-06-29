<?php
/**
 * Booster for WooCommerce - Settings Meta Box - General
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'title'   => __( 'PayPal Email', 'woocommerce-jetpack' ),
		'name'    => 'wcj_paypal_per_product_email',
		'default' => '',
		'type'    => 'text',
	),
);
