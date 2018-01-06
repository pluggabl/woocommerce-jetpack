<?php
/**
 * Booster for WooCommerce - Settings - Product Availability by Date
 *
 * @version 3.0.0
 * @since   2.9.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'All Products Options', 'woocommerce-jetpack' ),
		'desc'     => '<span id="local-date">' . sprintf( __( 'Today is <code>%s</code>.', 'woocommerce-jetpack' ), date( 'F j', $this->time_now ) ) . '</span>',
		'type'     => 'title',
		'id'       => 'wcj_product_by_date_all_products_options',
	),
	array(
		'title'    => __( 'All Products', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
		'desc_tip' => __( 'Date formats:', 'woocommerce-jetpack' ) . ' ' . '<code>DD-DD</code>' . ', ' . '<code>DD-DD,DD-DD</code>' . ', ' . '<code>-</code>' . '.',
		'id'       => 'wcj_product_by_date_section_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
);
$_timestamp = 1; //  January 1 1970
for ( $i = 1; $i <= 12; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => date_i18n( 'F', $_timestamp ),
			'id'       => 'wcj_product_by_date_' . $i,
			'default'  => $this->get_default_date( $i ),
			'type'     => 'text',
			'css'      => 'width:300px;',
		),
	) );
	$_timestamp = strtotime( '+1 month', $_timestamp );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_by_date_all_products_options',
	),
	array(
		'title'    => __( 'Per Product Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_by_date_per_product_options',
	),
	array(
		'title'    => __( 'Per Product', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable', 'woocommerce-jetpack' ) . '</strong>',
		'desc_tip' => __( 'This will add new meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_by_date_per_product_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_by_date_per_product_options',
	),
	array(
		'title'    => __( 'Frontend Messages Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_by_date_messages_options',
	),
	array(
		'title'    => __( 'Message', 'woocommerce-jetpack' ),
		'desc'     => __( 'Message when product is not available by date.', 'woocommerce-jetpack' ) .
			' ' . wcj_message_replaced_values( array( '%product_title%', '%date_this_month%' ) ) . '.' .
			' ' . __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ) .
			' ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_product_by_date_unavailable_message',
		'default'  => __( '<p style="color:red;">%product_title% is available only on %date_this_month% this month.</p>', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Message (Month Off)', 'woocommerce-jetpack' ),
		'desc'     => __( 'Message when product is not available by date (month off).', 'woocommerce-jetpack' ) .
			' ' . wcj_message_replaced_values( array( '%product_title%' ) ) . '.' .
			' ' . __( 'You can also use shortcodes here.', 'woocommerce-jetpack' ) .
			' ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_product_by_date_unavailable_message_month_off',
		'default'  => __( '<p style="color:red;">%product_title% is not available this month.</p>', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_by_date_messages_options',
	),
) );
return $settings;
