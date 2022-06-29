<?php
/**
 * Booster for WooCommerce - Settings - General
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'title' => __( 'Shortcodes Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_general_shortcodes_options',
	),
	array(
		'title'    => __( 'Shortcodes in WordPress Text Widgets', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will enable all (including non Booster\'s) shortcodes in WordPress text widgets.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_shortcodes_in_text_widgets_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Booster\'s Shortcodes', 'woocommerce-jetpack' ),
		'desc_tip' => sprintf(
			/* translators: %s: translators Added */
			__( 'Disable all <a href="%s" target="_blank">Booster\'s shortcodes</a> (for memory saving).', 'woocommerce-jetpack' ),
			'https://booster.io/shortcodes/'
		),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_shortcodes_disable_booster_shortcodes',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_general_shortcodes_options',
	),
	array(
		'title' => __( 'Ip Detection', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_general_ip',
	),
	array(
		'title'    => __( 'Overwrite WooCommerce IP Detection', 'woocommerce-jetpack' ),
		'desc'     => __( 'Try to overwrite WooCommerce IP detection', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'The "HTTP_X_REAL_IP" param on $_SERVER variable will be replaced by IP detected from Booster', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_overwrite_wc_ip',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Detection Methods', 'woocommerce-jetpack' ),
		'desc'     => __( 'IP Detection Methods used by some Booster modules when not using IP detection from WooCommerce. Change order for different results.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Default values are:', 'woocommerce-jetpack' ) . '<br />' . implode( PHP_EOL, array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) ),
		'id'       => 'wcj_general_advanced_ip_detection',
		'default'  => implode( PHP_EOL, array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) ),
		'type'     => 'textarea',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_general_ip',
	),
	array(
		'title' => __( 'Advanced Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_general_advanced_options',
	),
	array(
		'title'    => __( 'Recalculate Cart Totals', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Will recalculate cart totals on every page load.', 'woocommerce-jetpack' ) . ' ' .
			__( 'This may solve multicurrency issues with wrong currency symbol in mini-cart.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_recalculate_cart_totals',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Session Type in Booster', 'woocommerce-jetpack' ),
		'id'      => 'wcj_general_advanced_session_type',
		'default' => 'standard',
		'type'    => 'select',
		'options' => array(
			'standard' => __( 'Standard PHP sessions', 'woocommerce-jetpack' ),
			'wc'       => __( 'WC sessions', 'woocommerce-jetpack' ),
		),
		'desc'    => __( 'If you are having issues with currency related modules, You can change the session type', 'woocommerce-jetpack' ),
	),
	array(
		'title'    => __( 'Read and Close', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable <strong>Read and Close</strong> parameter on <strong>session_start()</strong>.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Requires Session Type option set as Standard PHP Sessions and PHP version >= 7.0', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_session_read_and_close',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Datepicker/Weekpicker CSS Loading', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Disables datepicker/weekpicker CSS loading.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_datepicker_css',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Datepicker/Weekpicker CSS Source', 'woocommerce-jetpack' ),
		'id'      => 'wcj_general_advanced_datepicker_css',
		'default' => wcj_plugin_url() . '/includes/css/jquery-ui.css',
		'type'    => 'text',
		'css'     => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Datepicker/Weekpicker JavaScript Loading', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Disables datepicker/weekpicker JavaScript loading.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_datepicker_js',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Timepicker CSS Loading', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Disables timepicker CSS loading.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_timepicker_css',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Timepicker JavaScript Loading', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Disables timepicker JavaScript loading.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_timepicker_js',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_general_advanced_options',
	),
	array(
		'title' => __( 'PayPal Email per Product Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_paypal_email_per_product_options',
	),
	array(
		'title'    => __( 'PayPal Email per Product', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add new meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_paypal_email_per_product_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_paypal_email_per_product_options',
	),
	array(
		'title' => __( 'Session Expiration Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_session_expiration_options',
	),
	array(
		'title'   => __( 'Session Expiration', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'      => 'wcj_session_expiration_section_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'             => __( 'Session Expiring', 'woocommerce-jetpack' ),
		'desc'              => __( 'In seconds. Default: 47 hours (60 * 60 * 47)', 'woocommerce-jetpack' ),
		'id'                => 'wcj_session_expiring',
		'default'           => 47 * 60 * 60,
		'type'              => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'             => __( 'Session Expiration', 'woocommerce-jetpack' ),
		'desc'              => __( 'In seconds. Default: 48 hours (60 * 60 * 48)', 'woocommerce-jetpack' ),
		'id'                => 'wcj_session_expiration',
		'default'           => 48 * 60 * 60,
		'type'              => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_session_expiration_options',
	),
	array(
		'title' => __( 'Booster User Roles Changer Options', 'woocommerce-jetpack' ),
		'desc'  => __( 'This will add user roles changer tool to admin bar.', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_general_user_role_changer_options',
	),
	array(
		'title'             => __( 'Booster User Roles Changer', 'woocommerce-jetpack' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'id'                => 'wcj_general_user_role_changer_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'   => __( 'Enabled for', 'woocommerce-jetpack' ),
		'id'      => 'wcj_general_user_role_changer_enabled_for',
		'default' => array( 'administrator', 'shop_manager' ),
		'type'    => 'multiselect',
		'class'   => 'chosen_select',
		'options' => wcj_get_user_roles_options(),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_general_user_role_changer_options',
	),
	array(
		'title' => __( 'PHP Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_admin_tools_php_options',
	),
	array(
		'title'             => __( 'PHP Memory Limit', 'woocommerce-jetpack' ),
		'desc'              => __( 'megabytes.', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Set zero to disable.', 'woocommerce-jetpack' ) . $this->current_php_memory_limit,
		'id'                => 'wcj_admin_tools_php_memory_limit',
		'default'           => 0,
		'type'              => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'             => __( 'PHP Time Limit', 'woocommerce-jetpack' ),
		'desc'              => __( 'seconds.', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'Set zero to disable.', 'woocommerce-jetpack' ) . $this->current_php_time_limit,
		'id'                => 'wcj_admin_tools_php_time_limit',
		'default'           => 0,
		'type'              => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_admin_tools_php_options',
	),
);
return $settings;
