<?php
/**
 * Booster for WooCommerce - Settings - Template Editor
 *
 * @version 3.8.1
 * @since   3.8.1
 * @author  Algoritmika Ltd.
 * @todo    [dev] (maybe) always use `DIRECTORY_SEPARATOR` (instead of '\\' and '/')
 * @todo    [dev] default template: check if `$default_template_path` exists before calling `file_get_contents()`
 * @todo    [feature] (maybe) option to set custom `/woocommerce/templates/` folder
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$this->templates_path = str_replace( '\\', '/', WP_PLUGIN_DIR . '/woocommerce/templates/' );
$templates = $this->scan_templates( $this->templates_path );

$settings = array(
	array(
		'title'    => __( 'Templates', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_template_editor_templates_options',
	),
	array(
		'title'    => __( 'Templates to Edit', 'woocommerce-jetpack' ),
		'id'       => 'wcj_template_editor_templates_to_edit',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'default'  => array(),
		'options'  => $templates,
	),
);
foreach ( get_option( 'wcj_template_editor_templates_to_edit', array() ) as $template ) {
	$default_template_path  = wc_locate_template( $template );
	$replaced_template_path = wcj_get_wcj_uploads_dir( 'templates', false ) . DIRECTORY_SEPARATOR . $template;
	$style                  = 'style="color:' . ( file_exists( $replaced_template_path ) ? 'green' : 'red' ) . ';"';
	$settings = array_merge( $settings, array(
		array(
			'title'    => $template,
			'desc'     =>
				sprintf( __( 'Default template path: %s', 'woocommerce-jetpack' ),  '<code>' .                $default_template_path  . '</code>' ) . '<br>' .
				sprintf( __( 'Replaced template path: %s', 'woocommerce-jetpack' ), '<code ' . $style . '>' . $replaced_template_path . '</code>' ),
			'id'       => "wcj_template_editor_templates_content[{$template}]",
			'type'     => 'textarea',
			'default'  => file_get_contents( $default_template_path ),
			'css'      => 'width:100%;height:500px;',
			'wcj_raw'  => true,
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_template_editor_templates_options',
	),
) );
return $settings;
