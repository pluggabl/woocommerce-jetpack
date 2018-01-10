<?php
/**
 * Booster for WooCommerce - Settings - Order Numbers
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    (maybe) add `wcj_order_number_counter_previous_order_date` as `hidden` field (for proper module reset)
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Order Numbers', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you enable sequential order numbering, set custom number prefix, suffix and width.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_numbers_options',
	),
	array(
		'title'    => __( 'Number Generation', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_sequential_enabled',
		'default'  => 'yes',
		'type'     => 'select',
		'options'  => array(
			'yes'        => __( 'Sequential', 'woocommerce-jetpack' ),
			'no'         => __( 'Order ID', 'woocommerce-jetpack' ),
			'hash_crc32' => __( 'Pseudorandom - Hash (max 10 digits)', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Sequential: Next Order Number', 'woocommerce-jetpack' ),
		'desc'     => '<br>' . __( 'Next new order will be given this number.', 'woocommerce-jetpack' ) . ' ' . __( 'Use Renumerate Orders tool for existing orders.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will be ignored if sequential order numbering is disabled.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_counter',
		'default'  => 1,
		'type'     => 'number',
	),
	array(
		'title'    => __( 'Sequential: Reset Counter', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will be ignored if sequential order numbering is disabled.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_counter_reset_enabled',
		'default'  => 'no',
		'type'     => 'select',
		'options'  => array(
			'no'      => __( 'Disabled', 'woocommerce-jetpack' ),
			'daily'   => __( 'Daily', 'woocommerce-jetpack' ),
			'monthly' => __( 'Monthly', 'woocommerce-jetpack' ),
			'yearly'  => __( 'Yearly', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Order Number Custom Prefix', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Prefix before order number (optional). This will change the prefixes for all existing orders.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_prefix',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Order Number Date Prefix', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'desc_tip' => __( 'Date prefix before order number (optional). This will change the prefixes for all existing orders. Value is passed directly to PHP `date` function, so most of PHP date formats can be used. The only exception is using `\` symbol in date format, as this symbol will be excluded from date. Try: Y-m-d- or mdy.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_date_prefix',
		'default'  => '',
		'type'     => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Order Number Width', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'desc_tip' => __( 'Minimum width of number without prefix (zeros will be added to the left side). This will change the minimum width of order number for all existing orders. E.g. set to 5 to have order number displayed as 00001 instead of 1. Leave zero to disable.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_min_width',
		'default'  => 0,
		'type'     => 'number',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Order Number Custom Suffix', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'desc_tip' => __( 'Suffix after order number (optional). This will change the suffixes for all existing orders.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_suffix',
		'default'  => '',
		'type'     => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Order Number Date Suffix', 'woocommerce-jetpack' ),
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'desc_tip' => __( 'Date suffix after order number (optional). This will change the suffixes for all existing orders. Value is passed directly to PHP `date` function, so most of PHP date formats can be used. The only exception is using `\` symbol in date format, as this symbol will be excluded from date. Try: Y-m-d- or mdy.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_date_suffix',
		'default'  => '',
		'type'     => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Use MySQL Transaction', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This should be enabled if you have a lot of simultaneous orders in your shop - to prevent duplicate order numbers (sequential).', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_use_mysql_transaction_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Enable Order Tracking by Custom Number', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_order_tracking_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Enable Order Admin Search by Custom Number', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_number_search_by_custom_number_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_numbers_options',
	),
	array(
		'title'    => __( 'Orders Renumerate Tool Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_order_numbers_renumerate_tool_options',
	),
	array(
		'title'    => __( 'Sort by', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_numbers_renumerate_tool_orderby',
		'default'  => 'date',
		'type'     => 'select',
		'options'  => array(
			'ID'       => __( 'ID', 'woocommerce-jetpack' ),
			'date'     => __( 'Date', 'woocommerce-jetpack' ),
			'modified' => __( 'Last modified date', 'woocommerce-jetpack' ),
			'rand'     => __( 'Random', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Sort Ascending or Descending', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_numbers_renumerate_tool_order',
		'default'  => 'ASC',
		'type'     => 'select',
		'options'  => array(
			'ASC'  => __( 'Ascending', 'woocommerce-jetpack' ),
			'DESC' => __( 'Descending', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_numbers_renumerate_tool_options',
	),
);
