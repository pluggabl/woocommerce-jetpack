<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Price based on User Role
 *
 * @version 5.6.2
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    (maybe) add option to disable "Copy to ..." buttons
 * @todo    (maybe) "Copy to ..." for "Make Empty Price"
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$main_product_id = get_the_ID();
$_product        = wc_get_product( $main_product_id );
$products        = array();
if ( $_product->is_type( 'variable' ) ) {
	$available_variations = $_product->get_available_variations();
	foreach ( $available_variations as $variation ) {
		$variation_product                      = wc_get_product( $variation['variation_id'] );
		$products[ $variation['variation_id'] ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
	}
} else {
	$products[ $main_product_id ] = '';
}
$options = array(
	array(
		'type'  => 'title',
		'title' => __( 'Per Product Settings (press Update after changing)', 'woocommerce-jetpack' ),
	),
	array(
		'name'    => 'wcj_price_by_user_role_per_product_settings_enabled',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
		'title'   => __( 'Enabled', 'woocommerce-jetpack' ),
	),
);
if ( 'yes' === get_post_meta( wcj_get_product_id( $_product ), '_wcj_price_by_user_role_per_product_settings_enabled', true ) ) {
	$visible_roles = wcj_get_option( 'wcj_price_by_user_role_per_product_show_roles', '' );
	foreach ( $products as $product_id => $desc ) {
		foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
			if ( ! empty( $visible_roles ) ) {
				if ( ! in_array( $role_key, $visible_roles, true ) ) {

					continue;
				}
			}
			// "Copy price to all user roles" buttons.
			$roles_for_copy = ( ! empty( $visible_roles ) ? array_flip( $visible_roles ) : wcj_get_user_roles() );
			unset( $roles_for_copy[ $role_key ] );
			$roles_for_copy = array_keys( $roles_for_copy );
			if ( ! empty( $roles_for_copy ) ) {
				$copy_to_roles_regular = $this->get_admin_settings_copy_link( 'copy_to_roles', 'regular', $product_id, $role_key, $roles_for_copy, array() );
				$copy_to_roles_sale    = $this->get_admin_settings_copy_link( 'copy_to_roles', 'sale', $product_id, $role_key, $roles_for_copy, array() );
			} else {
				$copy_to_roles_regular = '';
				$copy_to_roles_sale    = '';
			}
			// "Copy price to all variations" buttons.
			$products_for_copy = $products;
			unset( $products_for_copy[ $product_id ] );
			$products_for_copy = array_keys( $products_for_copy );
			if ( ! empty( $products_for_copy ) ) {
				$copy_to_variations_regular = $this->get_admin_settings_copy_link( 'copy_to_variations', 'regular', $product_id, $role_key, array(), $products_for_copy );
				$copy_to_variations_sale    = $this->get_admin_settings_copy_link( 'copy_to_variations', 'sale', $product_id, $role_key, array(), $products_for_copy );
			} else {
				$copy_to_variations_regular = '';
				$copy_to_variations_sale    = '';
			}
			// "Copy price to all user roles & variations" buttons.
			if ( ! empty( $roles_for_copy ) && ! empty( $products_for_copy ) ) {
				$copy_to_roles_and_variations_regular = $this->get_admin_settings_copy_link(
					'copy_to_roles_and_variations',
					'regular',
					$product_id,
					$role_key,
					$roles_for_copy,
					$products_for_copy
				);
				$copy_to_roles_and_variations_sale    = $this->get_admin_settings_copy_link(
					'copy_to_roles_and_variations',
					'sale',
					$product_id,
					$role_key,
					$roles_for_copy,
					$products_for_copy
				);
			} else {
				$copy_to_roles_and_variations_regular = '';
				$copy_to_roles_and_variations_sale    = '';
			}
			$copy_buttons_regular = $copy_to_roles_regular . $copy_to_variations_regular . $copy_to_roles_and_variations_regular;
			$copy_buttons_sale    = $copy_to_roles_sale . $copy_to_variations_sale . $copy_to_roles_and_variations_sale;
			// Settings.
			if ( 'fixed' === wcj_get_option( 'wcj_price_by_user_role_per_product_type', 'fixed' ) ) {
				$prices_or_multiplier = array(
					array(
						'name'       => 'wcj_price_by_user_role_regular_price_' . $role_key . '_' . $product_id,
						'default'    => '',
						'type'       => 'price',
						'title'      => __( 'Regular Price', 'woocommerce-jetpack' ) . $copy_buttons_regular,
						'desc'       => $desc,
						'product_id' => $product_id,
						'meta_name'  => '_wcj_price_by_user_role_regular_price_' . $role_key,
					),
					array(
						'name'       => 'wcj_price_by_user_role_sale_price_' . $role_key . '_' . $product_id,
						'default'    => '',
						'type'       => 'price',
						'title'      => __( 'Sale Price', 'woocommerce-jetpack' ) . $copy_buttons_sale,
						'desc'       => $desc,
						'product_id' => $product_id,
						'meta_name'  => '_wcj_price_by_user_role_sale_price_' . $role_key,
					),
				);
			} else {
				$prices_or_multiplier = array(
					array(
						'name'       => 'wcj_price_by_user_role_multiplier_' . $role_key . '_' . $product_id,
						'default'    => '',
						'type'       => 'price',
						'title'      => __( 'Multiplier', 'woocommerce-jetpack' ),
						'desc'       => $desc,
						'product_id' => $product_id,
						'meta_name'  => '_wcj_price_by_user_role_multiplier_' . $role_key,
					),
				);
			}
			$options = array_merge(
				$options,
				array(
					array(
						'type'  => 'title',
						'title' => '<em>' . $role_data['name'] . '</em>',
					),
				),
				$prices_or_multiplier,
				array(
					array(
						'name'       => 'wcj_price_by_user_role_empty_price_' . $role_key . '_' . $product_id,
						'default'    => 'no',
						'type'       => 'select',
						'options'    => array(
							'yes' => __( 'Yes', 'woocommerce-jetpack' ),
							'no'  => __( 'No', 'woocommerce-jetpack' ),
						),
						'title'      => __( 'Make Empty Price', 'woocommerce-jetpack' ),
						'desc'       => $desc,
						'product_id' => $product_id,
						'meta_name'  => '_wcj_price_by_user_role_empty_price_' . $role_key,
					),
				)
			);
		}
	}
}
return $options;
