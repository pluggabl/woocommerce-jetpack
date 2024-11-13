<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Page Settings
 *
 * @version 7.2.4
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$invoice_types = ( 'yes' === wcj_get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types() );
$tab_ids       = array();
foreach ( $invoice_types as $invoice_type ) {
	$tab_ids[ 'pdf_invoicing_page_' . $invoice_type['id'] . '_tab' ] = $invoice_type['title'];
}

$settings = array(
	array(
		'type'              => 'module_head',
		'title'             => __( 'Page', 'woocommerce-jetpack' ),
		'desc'              => __( 'PDF Invoicing: Page Settings' ),
		'icon'              => 'pr-sm-icn.png',
		'module_reset_link' => '<a style="width:auto;" onclick="return confirm(\'' . __( 'Are you sure? This will reset module to default settings.', 'woocommerce-jetpack' ) . '\')" class="wcj_manage_settting_btn wcj_tab_end_save_btn" href="' . esc_url(
			add_query_arg(
				array(
					'wcj_reset_settings' => $this->id,
					'wcj_reset_settings-' . $this->id . '-nonce' => wp_create_nonce( 'wcj_reset_settings' ),
				)
			)
		) . '">' . __( 'Reset settings', 'woocommerce-jetpack' ) . '</a>',
	),
	array(
		'id'   => 'pdf_invoicing_page_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'pdf_invoicing_page_options',
		'type'    => 'tab_ids',
		'tab_ids' => $tab_ids,
	),
);

foreach ( $invoice_types as $invoice_type ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'pdf_invoicing_page_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_start',
			),
			array(
				'title' => $invoice_type['title'],
				'type'  => 'title',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_page_options',
			),
			array(
				'title'   => __( 'Page Orientation', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_page_orientation',
				'default' => 'P',
				'type'    => 'select',
				'options' => array(
					'P' => __( 'Portrait', 'woocommerce-jetpack' ),
					'L' => __( 'Landscape', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'   => __( 'Page Format', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_page_format',
				'default' => 'A4', // PDF_PAGE_FORMAT.
				'type'    => 'select',
				'options' => array_replace( array( 'custom' => __( 'Custom', 'woocommerce-jetpack' ) ), $this->get_page_formats() ),
			),
			array(
				'desc'              => __( 'Custom: width (millimeters)', 'woocommerce-jetpack' ),
				'id'                => 'wcj_invoicing_' . $invoice_type['id'] . '_page_format_custom_width',
				'default'           => '0',
				'type'              => 'number',
				'custom_attributes' => array( 'min' => 0 ),
			),
			array(
				'desc'              => __( 'Custom: height (millimeters)', 'woocommerce-jetpack' ),
				'id'                => 'wcj_invoicing_' . $invoice_type['id'] . '_page_format_custom_height',
				'default'           => '0',
				'type'              => 'number',
				'custom_attributes' => array( 'min' => 0 ),
			),
			array(
				'title'   => __( 'Margin Left', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_left',
				'default' => 15, // PDF_MARGIN_LEFT.
				'type'    => 'number',
			),
			array(
				'title'   => __( 'Margin Right', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_right',
				'default' => 15, // PDF_MARGIN_RIGHT.
				'type'    => 'number',
			),
			array(
				'title'   => __( 'Margin Top', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_top',
				'default' => 27, // PDF_MARGIN_TOP.
				'type'    => 'number',
			),
			array(
				'title'   => __( 'Margin Bottom', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_margin_bottom',
				'default' => 10, // PDF_MARGIN_BOTTOM.
				'type'    => 'number',
			),
			array(
				'title'    => __( 'Background Image', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_background_image',
				'default'  => '',
				'type'     => 'text',
				'desc'     => sprintf(
									/* translators: %s: translators Added */
					__( 'Enter a local URL to an image. Upload your image using the <a href="%s">media uploader</a>.', 'woocommerce-jetpack' ),
					admin_url( 'media-new.php' )
				) .
					wcj_get_invoicing_current_image_path_desc( 'wcj_invoicing_' . $invoice_type['id'] . '_background_image' ) . '<br>' .
					sprintf(
										/* translators: %s: translators Added */
						__( 'If you are experiencing issues with displaying background image, please try setting different values for the "Advanced: Default Images Directory" option in %s.', 'woocommerce-jetpack' ),
						'<a target="_blank" href="' . admin_url( wcj_admin_tab_url() . '&wcj-cat=pdf_invoicing&section=pdf_invoicing_advanced' ) . '">' .
							__( 'PDF Invoicing & Packing Slips > Advanced', 'woocommerce-jetpack' ) .
						'</a>'
					),
				'desc_tip' => __( 'Leave blank to disable', 'woocommerce-jetpack' ),
				'class'    => 'widefat',
			),
			array(
				'title'    => __( 'Parse Background Image URL', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_background_image_parse',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Converts the Background Image URL to its local path.', 'woocommerce-jetpack' ) . '<br>' . __( 'If you are experiencing issues with displaying background image, please try to disable this option', 'woocommerce-jetpack' ),
			),
			array(
				'title'    => __( 'Background Image for Multiple pages', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_multipages_background',
				'default'  => 'no',
				'type'     => 'checkbox',
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Display Background Image for multiple pages', 'woocommerce-jetpack' ) . '<br>' . __( 'If you are experiencing issues with displaying Header Content, please try to disable this option', 'woocommerce-jetpack' ),

			),
			array(
				'title'             => __( 'Opacity for Background Image', 'woocommerce-jetpack' ),
				'id'                => 'wcj_invoicing_' . $invoice_type['id'] . '_background_opacity',
				'default'           => 0.5,
				'custom_attributes' => array(
					'min'  => 0.1,
					'step' => 0.1,
					'max'  => 1,
				),
				'type'              => 'number',
				'desc'              => __( 'Set Opacity for Background Image.', 'woocommerce-jetpack' ),

			),
			array(
				'title'   => __( 'Margin Top for Background Image', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_background_margin_top',
				'default' => 25, // PDF_MARGIN_LEFT.
				'type'    => 'number',
				'desc'    => __( 'Set 0 for default margin.', 'woocommerce-jetpack' ),
			),
			array(
				'title'   => __( 'Margin Left for Background Image', 'woocommerce-jetpack' ),
				'id'      => 'wcj_invoicing_' . $invoice_type['id'] . '_background_margin_left',
				'default' => 0, // PDF_MARGIN_LEFT.
				'type'    => 'number',
				'desc'    => __( 'Set 0 for default margin.', 'woocommerce-jetpack' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_invoicing_' . $invoice_type['id'] . '_page_options',
			),
			array(
				'id'   => 'pdf_invoicing_page_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
return $settings;
