<?php
/**
 * Booster for WooCommerce - Settings - WPML
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    automatically regenerate wpml-config.xml file
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Tools', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_' . $this->id . '_tools_options',
	),
	array(
		'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_' . $this->id . '_module_tools',
		'type'     => 'custom_link',
		'link'     => ( $this->is_enabled() ) ?
			'<code>' . '<a href="' . add_query_arg( 'create_wpml_xml_file', '1' ) . '">' . 
				__( 'Regenerate wpml-config.xml file', 'woocommerce-jetpack' ) . '</a>' . '</code>' .
				'<pre>' . $this->notice . '</pre>' :
			'<code>' . __( 'Regenerate wpml-config.xml file', 'woocommerce-jetpack' ) . '</code>',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_' . $this->id . '_tools_options',
	),
);
$this->notice = '';
return $settings;
