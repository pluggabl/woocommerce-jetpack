<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Custom Visibility
 *
 * @version 3.2.4
 * @since   3.2.4
 * @author  Algoritmika Ltd.
 * @todo    (maybe) option to choose between `chosen_select` and "simple"
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'      => __( 'Visible', 'woocommerce-jetpack' ),
		'name'       => 'wcj_product_custom_visibility_visible',
		'default'    => '',
		'type'       => 'select',
		'options'    => wcj_get_select_options( get_option( 'wcj_product_custom_visibility_options_list', '' ) ),
		'multiple'   => true,
		'css'        => 'width:100%;',
		'class'      => 'chosen_select',
	),
);
