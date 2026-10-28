<?php
/**
 * Booster for WooCommerce - Settings - Old slugs
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
		'id'   => 'wcj_old_slugs_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_old_slugs_general_options',
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
		'<code> <a href=" ' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=old_slugs&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' .
		__( 'Remove Old Slugs', 'woocommerce-jetpack' ) . '</a> </code>' :
			'<code>' . __( 'Remove Old Slugs', 'woocommerce-jetpack' ) . '</code>',
	),
	array(
		'id'   => 'wcj_old_slugs_button_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_best_single_product_tab',
		'type' => 'tab_end',
	),
);
