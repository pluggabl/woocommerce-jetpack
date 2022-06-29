<?php
/**
 * Booster for WooCommerce - Settings - Bookings
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$days_array = array();
$_timestamp = strtotime( 'next Sunday' );
for ( $i = 0; $i < 7; $i++ ) {
	$index                = ( 0 === $i ? 'S' : $i );
	$days_array[ $index ] = date_i18n( 'l', $_timestamp );
	$_timestamp           = strtotime( '+1 day', $_timestamp );
}
$months_array = array();
$_timestamp   = 1; // January 1 1970.
for ( $i = 1; $i <= 12; $i++ ) {
	$months_array[ $i ] = date_i18n( 'F', $_timestamp );
	$_timestamp         = strtotime( '+1 month', $_timestamp );
}

return array(
	array(
		'title' => __( 'Labels and Messages', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_product_bookings_labels_and_messages_options',
	),
	array(
		'title'   => __( 'Frontend Label: "Date from"', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_bookings_label_date_from',
		'default' => __( 'Date from', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:250px;',
	),
	array(
		'title'   => __( 'Frontend Label: "Date to"', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_bookings_label_date_to',
		'default' => __( 'Date to', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:250px;',
	),
	array(
		'title'   => __( 'Frontend Label: Period', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_bookings_label_period',
		'default' => __( 'Period', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:250px;',
	),
	array(
		'title'   => __( 'Frontend Label: Price per Day', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_bookings_label_per_day',
		'default' => __( '/ day', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:250px;',
	),
	array(
		'title'   => __( 'Message: "Date from" is missing', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_bookings_message_no_date_from',
		'default' => __( '"Date from" must be set', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:250px;',
	),
	array(
		'title'   => __( 'Message: "Date to" is missing', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_bookings_message_no_date_to',
		'default' => __( '"Date to" must be set', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:250px;',
	),
	array(
		'title'   => __( 'Message: "Date to" is missing', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_bookings_message_date_to_before_date_from',
		'default' => __( '"Date to" must be after "Date from"', 'woocommerce-jetpack' ),
		'type'    => 'text',
		'css'     => 'width:250px;',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_product_bookings_labels_and_messages_options',
	),
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_product_bookings_options',
	),
	array(
		'title'    => __( 'Price per Day on Variable Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Calculate Variable Products final price per day, according to calendar', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Disable it will make the Variable Product final price be calculated regardless of the chosen days on the calendar', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_price_per_day_variable_products',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Hide Quantity Selector for Bookings Products', 'woocommerce-jetpack' ),
		'desc'    => __( 'Hide', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_bookings_hide_quantity',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'title'    => __( 'Advanced: Check for Outputted Data', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Ensures that data outputted only once. Enable this if you see data outputted on frontend twice. Disable if you see no data outputted.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_check_for_outputted_data',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_product_bookings_options',
	),
	array(
		'title' => __( 'Datepicker Options', 'woocommerce-jetpack' ),
		'desc'  => __( 'This settings will be applied to all your bookings products.', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_product_bookings_datepicker_options',
	),
	array(
		'title'    => __( 'Date from: Exclude Days', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Leave blank to include all days.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_datepicker_date_from_exclude_days',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $days_array,
	),
	array(
		'title'    => __( 'Date to: Exclude Days', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Leave blank to include all days.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_datepicker_date_to_exclude_days',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $days_array,
	),
	array(
		'title'    => __( 'Date from: Exclude Months', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Leave blank to include all months.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_datepicker_date_from_exclude_months',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $months_array,
	),
	array(
		'title'    => __( 'Date to: Exclude Months', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Leave blank to include all months.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_datepicker_date_to_exclude_months',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $months_array,
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_product_bookings_datepicker_options',
	),
);
