<?php
/**
 * Booster for WooCommerce - Settings - Product Bulk Meta Editor
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'id'   => 'wcj_bulk_meta_editor_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_bulk_meta_editor_general_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_bulk_meta_editor_genral_options_tab' => __( 'Genral Options', 'woocommerce-jetpack' ),
			'wcj_bulk_meta_editor_tool_tab'           => __( 'Tools', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_bulk_meta_editor_genral_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'    => __( 'Check if Meta Exists', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled - meta can be changed only if it already existed for product. If you want to be able to create new meta for products, disable this option.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_bulk_meta_editor_check_if_exists',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'             => __( 'Add Variations to Products List', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'When enabled - variations of variable products will be added to the list. If you want to edit only main product\'s meta, disable this option.', 'woocommerce-jetpack' ) . ' ' . apply_filters( 'booster_message', '', 'desc' ),
		'desc'              => __( 'Enable', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_bulk_meta_editor_add_variations',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'   => __( 'Additional Columns', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_bulk_meta_editor_additional_columns',
		'default' => '',
		'type'    => 'multiselect',
		'class'   => 'chosen_select',
		'options' => array(
			'product_id'            => __( 'Product ID', 'woocommerce-jetpack' ),
			'product_status'        => __( 'Product status', 'woocommerce-jetpack' ),
			'product_all_meta_keys' => __( 'Meta keys', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_product_bulk_meta_editor_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_bulk_meta_editor_genral_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_bulk_meta_editor_tool_tab',
		'type' => 'tab_start',
	),
	array(
		'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_' . $this->id . '_module_tools',
		'type'     => 'custom_link',
		'link'     => ( $this->is_enabled() ) ?
		'<code> <a href=" ' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=product_bulk_meta_editor&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' .
		__( 'Product Bulk Meta Editor', 'woocommerce-jetpack' ) . '</a> </code>' :
			'<code>' . __( 'Product Bulk Meta Editor', 'woocommerce-jetpack' ) . '</code>',
	),
	array(
		'id'   => 'wcj_bulk_meta_editor_tool_tab',
		'type' => 'tab_end',
	),
);
