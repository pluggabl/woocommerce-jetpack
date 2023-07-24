<?php
/**
 * Booster for WooCommerce - Settings - WPML
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$all_modules = array();
if ( function_exists( 'w_c_j' ) && ! empty( w_c_j()->modules ) ) {
	foreach ( w_c_j()->modules as $module_key => $module ) {
		$desc_prefix                = ( false !== strpos( $module_key, 'pdf_invoicing_' ) ? __( 'PDF Invoicing', 'woocommerce-jetpack' ) . ': ' : '' );
		$all_modules[ $module_key ] = $desc_prefix . $module->short_desc;
	}
}
$message      = apply_filters( 'booster_message', '', 'desc' );
$settings     = array(
	array(
		'id'   => 'wpml_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wpml_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wpml_general_options_tab' => __( 'General options', 'woocommerce-jetpack' ),
			'wpml_file_options_tab'    => __( 'Language Config File Options', 'woocommerce-jetpack' ),
			'wpml_file_tools_tab'      => __( 'Tools', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wpml_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'   => __( 'Use Translation Product IDs', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'type'    => 'checkbox',
		'id'      => 'wcj_wpml_use_translation_product_id',
		'default' => 'yes',
	),
	array(
		'title'    => __( 'Auto Switch Booster Currency', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( "Try to automatically switch Booster currency according to WPML. It's necessary to enable MultiCurrency module", 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_wpml_switch_booster_currency',
		'default'  => 'no',
	),
	array(
		'title'             => __( 'Synchronize Metas', 'woocommerce-jetpack' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		'desc_tip'          => __( 'Try to automatically synchronize some Booster metas between products on different languages.', 'woocommerce-jetpack' ),
		'type'              => 'checkbox',
		'id'                => 'wcj_wpml_sync_metas',
		'default'           => 'no',
	),
	array(
		'id'   => 'wcj_wpml_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wpml_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wpml_file_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'WPML Language Configuration File Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		/* translators: %s: translators Added */
		'desc'  => sprintf( __( 'Options for regenerating %s file.', 'woocommerce-jetpack' ), '<code>wpml-config.xml</code>' ),
		'id'    => 'wcj_wpml_config_xml_options',
	),
	array(
		'title'    => __( 'Automatically Regenerate', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		/* translators: %s: translators Added */
		'desc_tip' => sprintf( __( 'Automatically regenerate %s file on each Booster version update.', 'woocommerce-jetpack' ), '<code>wpml-config.xml</code>' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_wpml_config_xml_auto_regenerate',
		'default'  => 'no',
	),
	array(
		'title'    => __( 'Modules to Skip', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Select modules, which options you wish to exclude from wpml-config.xml file.', 'woocommerce-jetpack' ),
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'id'       => 'wcj_wpml_config_xml_modules_to_skip',
		'options'  => $all_modules,
		'default'  => $this->get_default_modules_to_skip(),
	),
	array(
		'title'    => __( 'Option IDs to Skip', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Select options, which you wish to exclude from wpml-config.xml file.', 'woocommerce-jetpack' ),
		/* translators: %s: translators Added */
		'desc'     => sprintf( __( 'Full or part of option ID. Separated by vertical bar %s.', 'woocommerce-jetpack' ), '( | )' ),
		'type'     => 'textarea',
		'id'       => 'wcj_wpml_config_xml_values_to_skip',
		'default'  => $this->get_default_values_to_skip(),
		'css'      => 'width:100%;',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_wpml_config_xml_options',
	),
	array(
		'title' => __( 'Tools', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_' . $this->id . '_tools_options',
	),
	array(
		'id'   => 'wcj_' . $this->id . '_tools_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wpml_file_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wpml_file_tools_tab',
		'type' => 'tab_start',
	),
	array(
		'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_' . $this->id . '_module_tools',
		'type'     => 'custom_link',
		'link'     => ( $this->is_enabled() ) ?
		'<code> <a href="' . esc_url( add_query_arg( 'create_wpml_xml_file', '1' ) ) . '">' .
		__( 'Regenerate wpml-config.xml file', 'woocommerce-jetpack' ) . '</a> </code>' .
				'<pre>' . $this->notice . '</pre>' :
			'<code>' . __( 'Regenerate wpml-config.xml file', 'woocommerce-jetpack' ) . '</code>',
	),
	array(
		'id'   => 'wpml_file_tools_tab',
		'type' => 'tab_end',
	),
);
$this->notice = '';
return $settings;
