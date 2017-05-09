<?php
/**
 * Booster for WooCommerce - Settings - General
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    add link to Booster's shortcodes list
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
$links_html = '';
if ( class_exists( 'RecursiveIteratorIterator' ) && class_exists( 'RecursiveDirectoryIterator' ) ) {
	$dir = untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/../../woocommerce/templates' ) );
	$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );
	foreach ( $rii as $file ) {
		$the_name = str_replace( $dir, '', $file->getPathname() );
		$the_name_link = str_replace( DIRECTORY_SEPARATOR, '%2F', $the_name );
		if ( $file->isDir() ) {
			/* $links_html .= '<strong>' . $the_name . '</strong>' . PHP_EOL; *//*
		} else {
			$links_html .= '<a href="' . get_admin_url( null, 'plugin-editor.php?file=woocommerce' . '%2F' . 'templates' . $the_name_link . '&plugin=woocommerce' ) . '">' .
					'templates' . $the_name . '</a>' . PHP_EOL;
		}
	}
} else {
	$links_html = __( 'PHP 5 is required.', 'woocommerce-jetpack' );
}
*/
$settings = array(
	array(
		'title'    => __( 'Shortcodes Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_general_shortcodes_options',
	),
	array(
		'title'    => __( 'Enable All Shortcodes in WordPress Text Widgets', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will enable all (including non Booster\'s) shortcodes in WordPress text widgets.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_shortcodes_in_text_widgets_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Booster\'s Shortcodes', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Disable all Booster\'s shortcodes (for memory saving).', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_shortcodes_disable_booster_shortcodes',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_general_shortcodes_options',
	),
	array(
		'title'    => __( 'Product Revisions', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_revisions_options',
	),
	array(
		'title'    => __( 'Product Revisions', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_revisions_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_revisions_options',
	),
	array(
		'title'    => __( 'Advanced Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_general_advanced_options',
	),
	array(
		'title'    => __( 'Recalculate Cart Totals on Every Page Load', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_recalculate_cart_totals',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Loading Datepicker/Weekpicker CSS', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_datepicker_css',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Datepicker/Weekpicker CSS', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_datepicker_css',
		'default'  => '//ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css',
		'type'     => 'text',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Disable Loading Datepicker/Weekpicker JavaScript', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_datepicker_js',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Loading Timepicker CSS', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_timepicker_css',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Loading Timepicker JavaScript', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_timepicker_js',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Saving PDFs in PHP directory for temporary files', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_advanced_disable_save_sys_temp_dir',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_general_advanced_options',
	),
	array(
		'title'    => __( 'PayPal Email per Product Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_paypal_email_per_product_options',
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
		'type'     => 'sectionend',
		'id'       => 'wcj_paypal_email_per_product_options',
	),
	array(
		'title'    => __( 'Session Expiration Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_session_expiration_options',
	),
	array(
		'title'    => __( 'Session Expiration', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'       => 'wcj_session_expiration_section_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Session Expiring', 'woocommerce-jetpack' ),
		'desc'     => __( 'In seconds. Default: 47 hours (60 * 60 * 47)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_session_expiring',
		'default'  => 47 * 60 * 60,
		'type'     => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'Session Expiration', 'woocommerce-jetpack' ),
		'desc'     => __( 'In seconds. Default: 48 hours (60 * 60 * 48)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_session_expiration',
		'default'  => 48 * 60 * 60,
		'type'     => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_session_expiration_options',
	),
	array(
		'title'    => __( 'URL Coupons Options', 'woocommerce-jetpack' ),
		'desc'     => sprintf( __( 'Additionally you can hide standard coupon field on cart page in Booster\'s <a href="%s">Cart Customization</a> module.', 'woocommerce-jetpack' ),
			admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=cart_and_checkout&section=cart_customization' ) ),
		'type'     => 'title',
		'id'       => 'wcj_url_coupons_options',
	),
	array(
		'title'    => __( 'URL Coupons', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled, your users can apply shop\'s standard coupons, by visiting URL. E.g.: http://yoursite.com/?wcj_apply_coupon=couponcode.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_url_coupons_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'URL Coupons Key', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'URL key. If you change this, make sure it\'s unique and is not used anywhere on your site (e.g. by another plugin).', 'woocommerce-jetpack' ),
		'id'       => 'wcj_url_coupons_key',
		'default'  => 'wcj_apply_coupon',
		'type'     => 'text',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_url_coupons_options',
	),
	/*
	array(
		'title'    => __( 'WooCommerce Templates Editor Links', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_general_wc_templates_editor_links_options',
	),
	array(
		'title'    => __( 'Templates', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_wc_templates_editor_links',
		'type'     => 'custom_link',
		'link'     => '<pre>' . $links_html . '</pre>',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_general_wc_templates_editor_links_options',
	),
	*/
);
return $settings;
