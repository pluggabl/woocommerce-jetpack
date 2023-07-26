<?php
/**
 * Booster for WooCommerce - Settings - Product bulk_price converter
 *
 * @version 7.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'id'   => 'wcj_bulk_price_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_bulk_price_general_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_best_single_product_tab' => __( 'Tools', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_best_single_product_tab',
		'type' => 'tab_start',
	),
	array(
		'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_' . $this->id . '_module_tools',
		'type'     => 'custom_link',
		'link'     => ( $this->is_enabled() ) ?
		'<code> <a href=" ' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=bulk_price_converter&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' .
		__( 'Bulk Price Converter', 'woocommerce-jetpack' ) . '</a> </code>' :
			'<code>' . __( 'Bulk Price Converter', 'woocommerce-jetpack' ) . '</code>',
	),
	array(
		'id'   => 'wcj_bulk_price_button_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_best_single_product_tab',
		'type' => 'tab_end',
	),
);
