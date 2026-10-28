<?php
/**
 * Booster for WooCommerce - Settings - Debug Tools
 *
 * @version 7.0.0
 * @since   4.1.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'id'   => 'debug_tools_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'debug_tools_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'debug_tools_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
			'debug_tools_tools_options_tab'   => __( 'Tools', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'debug_tools_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'   => __( 'Log', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enables logging to Booster log.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_logging_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'WooCommerce Log', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enables logging to WooCommerce log.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_wc_logging_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Debug', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enables debug mode.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_debuging_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'System Info', 'woocommerce-jetpack' ),
		'id'      => 'wcj_debug_tools_system_info',
		'default' => '',
		'type'    => 'custom_link',
		'link'    => '<a href="' . esc_url(
			add_query_arg(
				array(
					'wcj_debug'       => true,
					'wcj_debug-nonce' => wp_create_nonce( 'wcj_debug' ),
				)
			)
		) . '">' . __( 'Show extended info', 'woocommerce-jetpack' ) . '</a>' .
			'<pre style="background-color: white; padding: 5px;">' . wcj_get_table_html(
				$this->get_system_info_table_array(),
				array(
					'table_class'        => 'widefat striped',
					'columns_styles'     => array( 'padding:0;', 'padding:0;' ),
					'table_heading_type' => 'vertical',
				)
			) . '</pre>',
	),
	array(
		'id'   => 'wcj_debug_tools_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'debug_tools_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'debug_tools_tools_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_' . $this->id . '_module_tools',
		'type'     => 'custom_link',
		'link'     => ( $this->is_enabled() ) ?
		'<code> <a href=" ' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=debug_tools&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' .
		__( 'Log', 'woocommerce-jetpack' ) . '</a> </code>' :
			'<code>' . __( 'Log', 'woocommerce-jetpack' ) . '</code>',
	),
	array(
		'id'   => 'debug_tools_tools_options_tab',
		'type' => 'tab_end',
	),
);
