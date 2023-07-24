<?php
/**
 * Booster for WooCommerce - Settings - Sale Flash
 *
 * @version 7.0.0
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'id'   => 'wcj_product_sale_flash_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_product_sale_flash_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_product_sale_flash_globally_tab'        => __( 'Globally', 'woocommerce-jetpack' ),
			'wcj_product_sale_flash_per_product_tab'     => __( 'Per Product', 'woocommerce-jetpack' ),
			'wcj_product_sale_flash_per_product_cat_tab' => __( 'Per Category', 'woocommerce-jetpack' ),
			'wcj_product_sale_flash_per_product_tag_tab' => __( 'Per Tag', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_product_sale_flash_globally_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Globally', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_sale_flash_global_options',
	),
	array(
		'title'   => __( 'Globally', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_product_images_sale_flash_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'    => __( 'Sale Flash', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_sale_flash_html',
		'default'  => '<span class="onsale">' . __( 'Sale!', 'woocommerce' ) . '</span>',
		'type'     => 'textarea',
		'css'      => 'width:100%',
	),
	array(
		'title'   => __( 'Hide Everywhere', 'woocommerce-jetpack' ),
		'desc'    => __( 'Hide', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_images_sale_flash_hide_everywhere',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Hide on Archives (Categories) Only', 'woocommerce-jetpack' ),
		'desc'    => __( 'Hide', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_images_sale_flash_hide_on_archives',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Hide on Single Page Only', 'woocommerce-jetpack' ),
		'desc'    => __( 'Hide', 'woocommerce-jetpack' ),
		'id'      => 'wcj_product_images_sale_flash_hide_on_single',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'id'   => 'wcj_sale_flash_global_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_sale_flash_globally_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_product_sale_flash_per_product_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Per Product', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_sale_flash_per_product_options',
	),
	array(
		'title'             => __( 'Per Product', 'woocommerce-jetpack' ),
		'desc'              => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'desc_tip'          => __( 'This will add meta box to each product\'s edit page.', 'woocommerce-jetpack' ) . '<br>' . apply_filters( 'booster_message', '', 'desc' ),
		'id'                => 'wcj_sale_flash_per_product_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'id'   => 'wcj_sale_flash_per_product_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_product_sale_flash_per_product_tab',
		'type' => 'tab_end',
	),
);

$product_terms['product_cat'] = wcj_get_terms( 'product_cat' );
$product_terms['product_tag'] = wcj_get_terms( 'product_tag' );
foreach ( $product_terms as $ids => $_product_terms ) {
	$titles   = ( 'product_cat' === $ids ? __( 'Per Category', 'woocommerce-jetpack' ) : __( 'Per Tag', 'woocommerce-jetpack' ) );
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'wcj_product_sale_flash_per_' . $ids . '_tab',
				'type' => 'tab_start',
			),
			array(
				'title' => $titles,
				'type'  => 'title',
				'id'    => 'wcj_sale_flash_per_' . $ids . '_options',
			),
			array(
				'title'             => $titles,
				'desc'              => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
				'desc_tip'          => apply_filters( 'booster_message', '', 'desc' ),
				'id'                => 'wcj_sale_flash_per_' . $ids . '_enabled',
				'default'           => 'no',
				'type'              => 'checkbox',
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
			array(
				'desc_tip' => __( 'Terms to Modify', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Save changes to see new option fields.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_sale_flash_per_' . $ids . '_terms',
				'default'  => array(),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => $_product_terms,
			),
		)
	);
	foreach ( wcj_get_option( 'wcj_sale_flash_per_' . $ids . '_terms', array() ) as $term_id ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					/* translators: %s: translators Added */
					'title'    => ( isset( $_product_terms[ $term_id ] ) ? $_product_terms[ $term_id ] : sprintf( __( 'Term #%s', 'woocommerce-jetpack' ), $term_id ) ),
					'desc_tip' => __( 'You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
					'id'       => "wcj_sale_flash_per_{$ids}[{$term_id}]",
					'default'  => '<span class="onsale">' . __( 'Sale!', 'woocommerce' ) . '</span>',
					'type'     => 'textarea',
					'css'      => 'width:100%;',
				),
			)
		);
	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'wcj_sale_flash_per_' . $ids . '_options',
				'type' => 'sectionend',
			),
			array(
				'id'   => 'wcj_product_sale_flash_per_' . $ids . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}

return $settings;
