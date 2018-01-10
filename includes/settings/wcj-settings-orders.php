<?php
/**
 * Booster for WooCommerce Settings - Orders
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$bulk_regenerate_download_permissions_all_orders_cron_desc = '';
if ( $this->is_enabled() && 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_order_bulk_regenerate_download_permissions_enabled', 'no' ) ) ) {
	$bulk_regenerate_download_permissions_all_orders_cron_desc = wcj_crons_get_next_event_time_message( 'wcj_bulk_regenerate_download_permissions_all_orders_cron_time' );
}

$settings = array(
	array(
		'title'    => __( 'Admin Order Currency', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_order_admin_currency_options',
	),
	array(
		'title'    => __( 'Admin Order Currency', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled this will add "Booster: Orders" metabox to each order\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_admin_currency',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Admin Order Currency Method', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Choose if you want changed order currency to be saved directly to DB, or if you want to use filter. When using <em>filter</em> method, changes will be active only when "Admin Order Currency" section is enabled. When using <em>directly to DB</em> method, changes will be permanent, that is even if Booster plugin is removed.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_admin_currency_method',
		'default'  => 'filter',
		'type'     => 'select',
		'options'  => array(
			'filter' => __( 'Filter', 'woocommerce-jetpack' ),
			'db'     => __( 'Directly to DB', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_admin_currency_options',
	),
	array(
		'title'    => __( 'Orders Auto-Complete', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you enable orders auto-complete function.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_auto_complete_options',
	),
	array(
		'title'    => __( 'Auto-complete all WooCommerce orders', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'E.g. if you sell digital products then you are not shipping anything and you may want auto-complete all your orders.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_auto_complete_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_auto_complete_options',
	),
	array(
		'title'    => __( 'Country by IP', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_orders_country_by_ip_options',
	),
	array(
		'title'    => __( 'Add Country by IP Meta Box', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled this will add "Booster: Country by IP" metabox to each order\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_orders_country_by_ip_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_orders_country_by_ip_options',
	),
	array(
		'title'    => __( 'Bulk Regenerate Download Permissions for Orders', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_order_bulk_regenerate_download_permissions_options',
	),
	array(
		'title'    => __( 'Bulk Regenerate Download Permissions', 'woocommerce-jetpack' ),
		'desc_tip' => apply_filters( 'booster_message', '', 'desc' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_order_bulk_regenerate_download_permissions_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'Bulk Actions', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled this will add "Regenerate download permissions" action to "Bulk Actions" select box on admin orders page.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_bulk_regenerate_download_permissions_actions',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'All Orders - Now', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Check this box and press "Save changes" button to start regeneration. Please note that both module and current section must be enabled before that.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'desc'     => __( 'Regenerate now', 'woocommerce-jetpack' ),
		'id'       => 'wcj_order_bulk_regenerate_download_permissions_all_orders',
		'default'  => 'no',
		'type'     => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'    => __( 'All Orders - Periodically', 'woocommerce-jetpack' ),
		'desc'     => $bulk_regenerate_download_permissions_all_orders_cron_desc . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'id'       => 'wcj_order_bulk_regenerate_download_permissions_all_orders_cron',
		'default'  => 'disabled',
		'type'     => 'select',
		'options'  => array_merge( array( 'disabled' => __( 'Disabled', 'woocommerce-jetpack' ) ),
			wcj_crons_get_all_intervals( __( 'Regenerate', 'woocommerce-jetpack' ), array( 'minutely' ) ) ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_order_bulk_regenerate_download_permissions_options',
	),
);
return $settings;
