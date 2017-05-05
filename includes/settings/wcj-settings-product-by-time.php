<?php
/**
 * Booster for WooCommerce - Settings - Product Availability by Time
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'desc'     => '<span id="local-time">' . sprintf( __( 'Local time is <code>%s</code>.', 'woocommerce-jetpack' ), date( 'l, H:i:s', $this->time_now ) ) . '</span>',
		'type'     => 'title',
		'id'       => 'wcj_product_by_time_options',
	),
	array(
		'title'    => __( 'Product by Time', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_time_section_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'     => __( 'Time Table', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_time',
		'default'  =>
			'8:00-19:59'            . PHP_EOL .
			'8:00-19:59'            . PHP_EOL .
			'8:00-19:59'            . PHP_EOL .
			'8:00-19:59'            . PHP_EOL .
			'8:00-9:59,12:00-17:59' . PHP_EOL .
			'-'                     . PHP_EOL .
			'-'                     . PHP_EOL,
		'type'     => 'textarea',
		'css'      => 'min-width:300px;height:200px;',
	),
	array(
		'title'    => __( 'Message', 'woocommerce-jetpack' ),
		'desc'     => __( 'Message when product is not available by time.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_time_unavailable_message',
		'default'  => __( '<p style="color:red;">Today the product is available only at %time_today%.</p>', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_by_time_options',
	),
);
