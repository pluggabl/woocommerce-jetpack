<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Visibility by Country
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Visible in Countries', 'woocommerce-jetpack' ),
		'name'     => 'wcj_product_by_country_visible',
		'default'  => '',
		'type'     => 'select',
		'options'  => wcj_get_countries(),
		'multiple' => true,
		'css'      => 'height:300px;',
	),
);
