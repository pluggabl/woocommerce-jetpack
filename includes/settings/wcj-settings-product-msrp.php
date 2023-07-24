<?php
/**
 * Booster for WooCommerce - Settings - Product MSRP
 *
 * @version 7.0.0
 * @since   3.6.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$message  = apply_filters( 'booster_message', '', 'desc' );
$sections = array(
	'single'   => __( 'Single Product Page', 'woocommerce-jetpack' ),
	'archives' => __( 'Archives', 'woocommerce-jetpack' ),
);
$settings = array();
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_product_listing_general_options',
			'type' => 'sectionend',
		),
		array(
			'id'      => 'wcj_product_msrp_general_options',
			'type'    => 'tab_ids',
			'tab_ids' => array(
				'wcj_product_listing_single_tab'        => __( 'Single Product Page Display Options', 'woocommerce-jetpack' ),
				'wcj_product_listing_archives_tab'      => __( 'Archives Display Options', 'woocommerce-jetpack' ),
				'wcj_product_listing_admin_options_tab' => __( 'Admin Options', 'woocommerce-jetpack' ),
				'wcj_product_listing_compatibility_tab' => __( 'Compatibility', 'woocommerce-jetpack' ),
				'wcj_product_listing_other_options_tab' => __( 'Other Options', 'woocommerce-jetpack' ),
				'wcj_product_listing_template_variable_formulas_tab' => __( 'Template Variable Formulas', 'woocommerce-jetpack' ),
			),
		),
	)
);
foreach ( $sections as $section_id => $section_title ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'wcj_product_listing_' . $section_id . '_tab',
				'type' => 'tab_start',
			),
			array(
				/* translators: %s: translators Added */
				'title' => sprintf( __( '%s Display Options', 'woocommerce-jetpack' ), $section_title ),
				'type'  => 'title',
				'id'    => 'wcj_product_msrp_display_on_' . $section_id . '_options',
			),
			array(
				'title'   => __( 'Display', 'woocommerce-jetpack' ),
				'type'    => 'select',
				'id'      => 'wcj_product_msrp_display_on_' . $section_id,
				'default' => 'show',
				'options' => array(
					'hide'           => __( 'Do not show', 'woocommerce-jetpack' ),
					'show'           => __( 'Show', 'woocommerce-jetpack' ),
					'show_if_higher' => __( 'Only show if MSRP is higher than the standard price', 'woocommerce-jetpack' ),
					'show_if_diff'   => __( 'Only show if MSRP differs from the standard price', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'   => __( 'Position', 'woocommerce-jetpack' ),
				'type'    => 'select',
				'id'      => 'wcj_product_msrp_display_on_' . $section_id . '_position',
				'default' => 'after_price',
				'options' => array(
					'before_price' => __( 'Before the standard price', 'woocommerce-jetpack' ),
					'after_price'  => __( 'After the standard price', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'   => __( 'Savings', 'woocommerce-jetpack' ),
				/* translators: %s: translators Added */
				'desc'    => sprintf( __( 'Savings amount. To display this, use %s in "Final Template"', 'woocommerce-jetpack' ), '<code> %you_save% </code>' ) . ' ' .
				wcj_message_replaced_values( array( '%you_save_raw%' ) ),
				'type'    => 'textarea',
				'id'      => 'wcj_product_msrp_display_on_' . $section_id . '_you_save',
				'default' => ' (%you_save_raw%)',
			),
			array(
				/* translators: %s: translators Added */
				'desc'    => sprintf( __( 'Savings amount in percent. To display this, use %s in "Final Template"', 'woocommerce-jetpack' ), '<code> %you_save_percent% </code>' ) . ' ' .
				wcj_message_replaced_values( array( '%you_save_percent_raw%' ) ),
				'type'    => 'textarea',
				'id'      => 'wcj_product_msrp_display_on_' . $section_id . '_you_save_percent',
				'default' => ' (%you_save_percent_raw% %)',
			),
			array(
				'desc'              => __( 'Savings amount in percent rounding precision', 'woocommerce-jetpack' ),
				'type'              => 'number',
				'id'                => 'wcj_product_msrp_display_on_' . $section_id . '_you_save_percent_round',
				'default'           => 0,
				'custom_attributes' => array( 'min' => 0 ),
			),
			array(
				'title'             => __( 'Final Template', 'woocommerce-jetpack' ),
				'desc_tip'          => apply_filters( 'booster_message', '', 'desc_no_link' ),
				'desc'              => wcj_message_replaced_values( array( '%msrp%', '%you_save%', '%you_save_percent%' ) ),
				'type'              => 'textarea',
				'id'                => 'wcj_product_msrp_display_on_' . $section_id . '_template',
				'default'           => '<div class="price"><label for="wcj_product_msrp">MSRP</label>: <span id="wcj_product_msrp"><del>%msrp%</del>%you_save%</span></div>',
				'css'               => 'width:100%;',
				'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
			),
			array(
				'id'   => 'wcj_product_msrp_display_' . $section_id . '_options',
				'type' => 'sectionend',
			),
			array(
				'id'   => 'wcj_product_listing_' . $section_id . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_product_listing_admin_options_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Admin Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_product_msrp_admin_options',
		),
		array(
			'title'   => __( 'Admin MSRP Input Display', 'woocommerce-jetpack' ),
			'type'    => 'select',
			'id'      => 'wcj_product_msrp_admin_view',
			'default' => 'inline',
			'options' => array(
				'inline'   => __( 'Inline', 'woocommerce-jetpack' ),
				'meta_box' => __( 'As separate meta box', 'woocommerce-jetpack' ),
			),
		),
		array(
			'id'   => 'wcj_product_msrp_admin_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_product_listing_admin_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_product_listing_compatibility_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Compatibility', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_payment_msrp_comp',
		),
		array(
			'title'   => __( 'Multicurrency', 'woocommerce-jetpack' ),
			'desc'    => __( 'Enable compatibility with Multicurrency module', 'woocommerce-jetpack' ),
			'id'      => 'wcj_payment_msrp_comp_mc',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'id'   => 'wcj_payment_msrp_comp',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_product_listing_compatibility_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_product_listing_other_options_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Other Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_product_msrp_other_options',
		),
		array(
			'title'   => __( 'Treat Variable Products as Simple Products', 'woocommerce-jetpack' ),
			'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
			'id'      => 'wcj_product_msrp_variable_as_simple_enabled',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'title'             => __( 'Archive Field', 'woocommerce-jetpack' ),
			'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			'desc_tip'          => __( 'Adds a MSRP field that will be displayed on the product archive.', 'woocommerce-jetpack' ),
			'id'                => 'wcj_product_msrp_archive_page_field',
			'default'           => 'no',
			'type'              => 'checkbox',
		),
		array(
			'title'    => __( 'Archive Detection Method', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Template strings used to detect the loop.', 'woocommerce-jetpack' ) . '<br />' . __( 'Use 1 string per line.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_product_msrp_archive_detection_method',
			'default'  => 'loop',
			'type'     => 'textarea',
		),
		array(
			'id'   => 'wcj_product_msrp_other_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_product_listing_other_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_product_listing_template_variable_formulas_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Template Variable Formulas', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_product_msrp_template_variables_formulas',
		),
		array(
			'title'             => __( 'You Save', 'woocommerce-jetpack' ),
			'desc'              => empty( $message ) ? __( 'Variable: ', 'woocommerce-jetpack' ) . '<code>%you_save%</code><br />' . wcj_message_replaced_values( array( '%msrp%', '%product_price%' ) ) : $message,
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			'desc_tip'          => __( '%you_save%', 'woocommerce-jetpack' ),
			'id'                => 'wcj_product_msrp_formula_you_save',
			'default'           => '%msrp% - %product_price%',
			'type'              => 'text',
		),
		array(
			'title'             => __( 'You Save Percent', 'woocommerce-jetpack' ),
			'desc'              => empty( $message ) ? __( 'Variable: ', 'woocommerce-jetpack' ) . '<code>%you_save_percent%</code><br />' . wcj_message_replaced_values( array( '%msrp%', '%product_price%' ) ) : $message,
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			'desc_tip'          => __( '%you_save_percent%', 'woocommerce-jetpack' ),
			'id'                => 'wcj_product_msrp_formula_you_save_percent',
			'default'           => '(%msrp% - %product_price%) / %msrp% * 100',
			'type'              => 'text',
		),
		array(
			'id'   => 'wcj_product_msrp_template_variables_formulas',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_product_listing_template_variable_formulas_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
