<?php
/**
 * Booster for WooCommerce - Settings - User Tracking
 *
 * @version 7.1.4
 * @since   3.1.3
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( true === wcj_is_hpos_enabled() ) {
	$order_url = 'admin.php?page=wc-orders';
} else {
	$order_url = 'edit.php?post_type=shop_order';
}
return array(
	array(
		'id'   => 'track_users_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'track_users_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'track_users_general_options_tab' => __( 'General options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'track_users_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_track_users_general_options',
	),
	array(
		'title'   => __( 'Countries by Visits', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable admin dashboard widget', 'woocommerce-jetpack' ),
		'id'      => 'wcj_track_users_by_country_widget_enabled',
		'default' => 'yes',
		'type'    => 'checkbox',
	),
	array(
		'desc'     => __( 'Info', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Select which info to show in admin dashboard widget.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_track_users_by_country_widget_scopes',
		'default'  => array( '1', '28' ),
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $this->track_users_scopes,
	),
	array(
		'desc'              => __( 'Top Countries', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Select how many top countries to show.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_track_users_by_country_widget_top_count',
		'default'           => 10,
		'type'              => 'number',
		'custom_attributes' => ( array( 'min' => 0 ) ),
	),
	array(
		'title'             => __( 'Track Orders', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Save customer\'s acquisition source (i.e. HTTP referer) for orders.', 'woocommerce-jetpack' ) . ' ' .
			__( 'This will add "Booster: Acquisition Source" meta box to each order\'s edit page.', 'woocommerce-jetpack' ) . '<br>' .
			apply_filters( 'booster_message', '', 'desc' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'id'                => 'wcj_track_users_save_order_http_referer_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'desc'              => __( 'Order List Columns: Referer', 'woocommerce-jetpack' ),
		'desc_tip'          => sprintf(
						/* translators: %s: translators Added */
			__( 'This will add "Referer" column to the <a href="%s">orders list</a>.', 'woocommerce-jetpack' ),
			admin_url( $order_url )
		) . '<br>' .
			apply_filters( 'booster_message', '', 'desc' ),
		'id'                => 'wcj_track_users_shop_order_columns_referer',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'desc'              => __( 'Order List Columns: Referer Type', 'woocommerce-jetpack' ),
		'desc_tip'          => sprintf(
						/* translators: %s: translators Added */
			__( 'This will add "Referer Type" column to the <a href="%s">orders list</a>.', 'woocommerce-jetpack' ),
			admin_url( $order_url )
		) . '<br>' .
			apply_filters( 'booster_message', '', 'desc' ),
		'id'                => 'wcj_track_users_shop_order_columns_referer_type',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'   => 'wcj_track_users_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'track_users_general_options_tab',
		'type' => 'tab_end',
	),
);
