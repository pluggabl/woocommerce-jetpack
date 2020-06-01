<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Crowdfunding
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'name'    => 'wcj_crowdfunding_goal_sum',
		'default' => 0,
		'type'    => 'price',
		'title'   => __( 'Goal', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
	),
	array(
		'name'    => 'wcj_crowdfunding_startdate',
		'default' => '',
		'type'    => 'date',
		'title'   => __( 'Start Date', 'woocommerce-jetpack' )
	),
	array(
		'name'    => 'wcj_crowdfunding_deadline',
		'default' => '',
		'type'    => 'date',
		'title'   => __( 'Deadline', 'woocommerce-jetpack' )
	),
);
