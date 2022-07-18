<?php
/**
 * Booster for WooCommerce - Settings - Template Editor
 *
 * @version 5.6.2-dev
 * @since   3.9.0
 * @author  Pluggabl LLC.
 * @todo    [dev] (maybe) always use `DIRECTORY_SEPARATOR` (instead of '\\' and '/')
 * @todo    [dev] default template: check if `$default_template_path` exists before calling `file_get_contents()`
 * @todo    [feature] (maybe) option to set custom `/woocommerce/` folder
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$new_paths         = $this->get_paths();
$templates         = array();
$templates_by_path = wcj_get_option( 'wcj_template_editor_templates_by_path', array() );
foreach ( $new_paths as $paths ) {
	$scanned_templates           = $this->scan_templates( $paths, $paths );
	$templates_by_path[ $paths ] = $scanned_templates;
	$templates                   = array_merge( $templates, $scanned_templates );
}
update_option( 'wcj_template_editor_templates_by_path', $templates_by_path );
$settings = array(
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_template_editor_options',
	),
	array(
		'title'    => __( 'Paths', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'default'  => '/woocommerce/templates/',
		'css'      => 'width:100%;',
		'id'       => 'wcj_template_editor_paths',
		/* translators: %s: search term */
		'desc_tip' => sprintf( __( 'One path per line relative to: %s', 'woocommerce-jetpack' ), WP_PLUGIN_DIR ),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_template_editor_options',
	),
	array(
		'title' => __( 'Templates', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_template_editor_templates_options',
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
foreach ( wcj_get_option( 'wcj_template_editor_templates_to_edit', array() ) as $template ) {
	$default_template_path  = wc_locate_template( $template, '', $this->get_path_by_template( $template ) );
	$replaced_template_path = wcj_get_wcj_uploads_dir( 'templates', false ) . DIRECTORY_SEPARATOR . $template;
	$style                  = 'style="color:' . ( file_exists( $replaced_template_path ) ? 'green' : 'red' ) . ';"';
	$settings               = array_merge(
		$settings,
		array(
			array(
				'title'   => $template,
				'desc'    =>
				'<details>' .
					'<summary>' . __( 'Info', 'woocommerce-jetpack' ) . '</summary>' .
					wcj_get_table_html(
						array(
							array( __( 'Default template path', 'woocommerce-jetpack' ), '<span>' . $default_template_path . '</span>' ),
							array( __( 'Replaced template path', 'woocommerce-jetpack' ), '<span ' . $style . '>' . $replaced_template_path . '</span>' ),
						),
						array(
							'table_class'        => 'widefat striped',
							'table_heading_type' => 'none',
							'columns_styles'     => array( 'padding:0', 'padding:0' ),
						)
					) .
				'</details>',
				'id'      => "wcj_template_editor_templates_content[{$template}]",
				'type'    => 'textarea',
				'default' => wp_remote_get( $default_template_path ),
				'css'     => 'width:100%;height:500px;font-family:monospace;',
				'wcj_raw' => true,
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_template_editor_templates_options',
		),
	)
);
return $settings;
