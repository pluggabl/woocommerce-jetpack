<?php
/**
 * Booster for WooCommerce - Settings - Add to Cart Button Visibility
 *
 * @version 3.2.5
 * @since   3.2.5
 * @author  Algoritmika Ltd.
 * @todo    add note about "Modules By User Roles" module
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
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
);
