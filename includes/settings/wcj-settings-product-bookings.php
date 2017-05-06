<?php
/**
 * Booster for WooCommerce - Settings - Bookings
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Labels and Messages', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_bookings_labels_and_messages_options',
	),
	array(
		'title'    => __( 'Frontend Label: "Date from"', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_label_date_from',
		'default'  => __( 'Date from', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:250px;',
	),
	array(
		'title'    => __( 'Frontend Label: "Date to"', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_label_date_to',
		'default'  => __( 'Date to', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:250px;',
	),
	array(
		'title'    => __( 'Frontend Label: Period', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_label_period',
		'default'  => __( 'Period', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:250px;',
	),
	array(
		'title'    => __( 'Frontend Label: Price per Day', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_label_per_day',
		'default'  => __( '/ day', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:250px;',
	),
	array(
		'title'    => __( 'Message: "Date from" is missing', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_message_no_date_from',
		'default'  => __( '"Date from" must be set', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:250px;',
	),
	array(
		'title'    => __( 'Message: "Date to" is missing', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_message_no_date_to',
		'default'  => __( '"Date to" must be set', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:250px;',
	),
	array(
		'title'    => __( 'Message: "Date to" is missing', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_message_date_to_before_date_from',
		'default'  => __( '"Date to" must be after "Date from"', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'css'      => 'width:250px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_bookings_labels_and_messages_options',
	),
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_bookings_options',
	),
	array(
		'title'    => __( 'Hide Quantity Selector for Bookings Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bookings_hide_quantity',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_bookings_options',
	),
);
