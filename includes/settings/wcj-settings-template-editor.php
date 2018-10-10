<?php
/**
 * Booster for WooCommerce - Settings - Template Editor
 *
 * @version 4.0.0
 * @since   3.9.0
 * @author  Algoritmika Ltd.
 * @todo    [dev] (maybe) always use `DIRECTORY_SEPARATOR` (instead of '\\' and '/')
 * @todo    [dev] default template: check if `$default_template_path` exists before calling `file_get_contents()`
 * @todo    [feature] (maybe) option to set custom `/woocommerce/` folder
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
		'desc_tip' => __( 'Save changes after you set this option to see new options fields.', 'woocommerce-jetpack' ),
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
				'<details>' .
					'<summary>' . __( 'Info', 'woocommerce-jetpack' ) . '</summary>' .
					wcj_get_table_html( array(
						array( __( 'Default template path', 'woocommerce-jetpack' ),  '<span>' .                $default_template_path  . '</span>' ),
						array( __( 'Replaced template path', 'woocommerce-jetpack' ), '<span ' . $style . '>' . $replaced_template_path . '</span>' ),
					), array( 'table_class' => 'widefat striped', 'table_heading_type' => 'none', 'columns_styles' => array( 'padding:0', 'padding:0' ) ) ) .
				'</details>',
			'id'       => "wcj_template_editor_templates_content[{$template}]",
			'type'     => 'textarea',
			'default'  => file_get_contents( $default_template_path ),
			'css'      => 'width:100%;height:500px;font-family:monospace;',
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
