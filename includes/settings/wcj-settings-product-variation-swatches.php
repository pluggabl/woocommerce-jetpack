<?php
/**
 * Booster for WooCommerce - Settings - Product Variation Swatches
 *
 * @version 7.2.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'id'   => 'wcj_product_variation_swatches_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_product_variation_swatches_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_product_variation_swatches_options_tab' => __( 'Variation Swatches Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_product_variation_swatches_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Variation Swatches', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_product_variation_swatches_general_options',
	),
	array(
		'title'             => __( 'Convert default dropdowns to button', 'woocommerce-jetpack' ),
		'desc'              => __( 'Want to enable swatches for all your attributes, use button/label swatches, or automatically convert all dropdowns? Upgrade to <a href="https://booster.io/buy-booster/" target="_blank"> Booster Elite </a> for advanced swatch control!', 'woocommerce-jetpack' ),
		'type'              => 'checkbox',
		'id'                => 'wcj_product_variation_defualt_to_button',
		'default'           => 'no',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'title'             => __( 'Attribute Display Style', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_pvs_attr_display_style',
		'default'           => ' Squared',
		'type'              => 'select',
		'options'           => array(
			'squared' => __( 'Squared', 'woocommerce-jetpack' ),
			'rounded' => __( 'Rounded', 'woocommerce-jetpack' ),
		),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'             => __( 'Color Variation Item Width', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_color_variation_item_width',
		'default'           => '30px',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'             => __( 'Color Variation Item Height', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_color_variation_item_height',
		'default'           => '30px',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'             => __( 'Image Variation Item Width', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_image_variation_item_width',
		'default'           => '30px',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'             => __( 'Image Variation Item Height', 'woocommerce-jetpack' ),
		'id'                => 'wcj_product_image_variation_item_height',
		'default'           => '30px',
		'type'              => 'text',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
	),
	array(
		'title'   => __( 'Variation label tooltip', 'woocommerce-jetpack' ),
		'id'      => 'wcj_is_show_product_variation_lable',
		'default' => 'yes',
		'type'    => 'select',
		'options' => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Variation Swatches Style In Archive', 'woocommerce-jetpack' ),
		'id'      => 'wcj_is_show_product_variation_archive',
		'default' => 'yes',
		'type'    => 'select',
		'options' => array(
			'yes' => __( 'Booster Swatches', 'woocommerce-jetpack' ),
			'no'  => __( 'WooCommerce Default', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_product_variation_swatches_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_variation_swatches_options_tab',
		'type' => 'tab_end',
	),
);

return $settings;
