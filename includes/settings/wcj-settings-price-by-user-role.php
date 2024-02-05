<?php
/**
 * Booster for WooCommerce - Settings - Price based on User Role
 *
 * @version 7.1.6
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$message  = apply_filters( 'booster_message', '', 'desc' );
$settings = array(
	array(
		'id'   => 'price_by_user_role_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'price_by_user_role_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'price_by_user_role_general_options_tab'       => __( 'General', 'woocommerce-jetpack' ),
			'price_by_user_role_advanced_tab'              => __( 'Advanced', 'woocommerce-jetpack' ),
			'price_by_user_role_compatibility_tab'         => __( 'Compatibility', 'woocommerce-jetpack' ),
			'price_by_user_role_roles_multipliers_tab'     => __( 'Roles & Multipliers', 'woocommerce-jetpack' ),
			'price_by_user_role_by_product_categories_tab' => __( 'By Products Categories', 'woocommerce-jetpack' ),
			'price_by_user_role_by_product_tags_tab'       => __( 'By Products Tags', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'price_by_user_role_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'    => __( 'Enable per Product Settings', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled, this will add new "Booster: Price based on User Role" meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_price_by_user_role_per_product_enabled',
		'default'  => 'yes',
	),
	array(
		'title'    => __( 'Enable Price by User role for Order Edit', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When Enabled, It will allow price by user role while creating/editing order from admin. Otherwise admin role will be used for price.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_price_by_user_role_admin_order',
		'default'  => 'no',
	),
	array(
		'title'   => __( 'Per Product Settings Type', 'woocommerce-jetpack' ),
		'type'    => 'select',
		'id'      => 'wcj_price_by_user_role_per_product_type',
		'default' => 'fixed',
		'options' => array(
			'fixed'      => __( 'Fixed', 'woocommerce-jetpack' ),
			'multiplier' => __( 'Multiplier', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Show Roles on per Product Settings', 'woocommerce-jetpack' ),
		'desc'    => __( 'If per product settings are enabled, you can choose which roles to show on product\'s edit page. Leave blank to show all roles.', 'woocommerce-jetpack' ),
		'type'    => 'multiselect',
		'id'      => 'wcj_price_by_user_role_per_product_show_roles',
		'default' => '',
		'class'   => 'chosen_select',
		'options' => wcj_get_user_roles_options(),
	),
	array(
		'title'    => __( 'Shipping', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When enabled, this will apply user role multipliers to shipping calculations.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_price_by_user_role_shipping_enabled',
		'default'  => 'no',
	),
	array(
		'title'    => __( 'Disable Price based on User Role for Regular Price', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Disable price by user role for regular price when using multipliers (global or per product).', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_disable_for_regular_price',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Search Engine Bots', 'woocommerce-jetpack' ),
		'desc'    => __( 'Disable Price based on User Role for Bots', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_user_role_for_bots_disabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'    => __( 'Show Empty Price Variations', 'woocommerce-jetpack' ),
		'desc'     => __( 'Show', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Show "empty price" variations. This will also hide out of stock messages.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_show_empty_price_variations',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Remove Empty Price Variation Callback', 'woocommerce-jetpack' ),
		'desc'     => __( 'Remove', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Remove "woocommerce_single_variation" callback from "woocommerce_single_variation" hook on "empty price" variations.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_remove_single_variation',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Remove Empty Price Add to Cart Button Callback', 'woocommerce-jetpack' ),
		'desc'     => __( 'Remove', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Remove "woocommerce_single_variation_add_to_cart_button" callback from "woocommerce_single_variation" hook on "empty price" variations.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_remove_add_to_cart_btn',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'             => __( 'Check Child Categories', 'woocommerce-jetpack' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		'desc_tip'          => __( 'Enable to also consider the child categories.', 'woocommerce-jetpack' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'id'                => 'wcj_price_by_user_role_check_child_categories',
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'id'   => 'wcj_price_by_user_role_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'price_by_user_role_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'price_by_user_role_advanced_tab',
		'type' => 'tab_start',
	),

	array(
		'title' => __( 'Advanced', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_price_by_user_role_options_adv',
	),
	array(
		'title'    => __( 'Price Filters Priority', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Priority for all module\'s price filters. If you face pricing issues while using another plugin or booster module, You can change the Priority, Greater value for high priority & Lower value for low priority. Set to zero to use default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_advanced_price_hooks_priority',
		'default'  => 0,
		'type'     => 'number',
	),
	array(
		'title'    => __( 'Price Changes', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable price based on user role for products with "Price Changes"', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Try enabling this checkbox, if you are having compatibility issues with other plugins.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_check_for_product_changes_price',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	$this->get_wpml_terms_in_all_languages_setting(),
	array(
		'id'   => 'wcj_price_by_user_role_options_adv',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'price_by_user_role_advanced_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'price_by_user_role_compatibility_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Compatibility', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_price_by_user_role_compatibility',
	),
	array(
		'title'             => __( 'WooCommerce Product Bundles', 'woocommerce-jetpack' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		/* translators: %s: translators Added */
		'desc_tip'          => sprintf( __( 'Enable this option if there is compatibility with <a href="%s" target="_blank">WooCommerce Product Bundles</a> plugin.', 'woocommerce-jetpack' ), 'https://woocommerce.com/products/product-bundles/' ),
		'id'                => 'wcj_price_by_user_role_compatibility_wc_product_bundles',
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'title'    => __( 'Compatibility with Product Addon', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Try enabling this checkbox, if you are having compatibility issues with Booster Product Addon module.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_user_role_compatibility_product_addon',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'id'   => 'wcj_price_by_user_role_compatibility',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'price_by_user_role_compatibility_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'price_by_user_role_roles_multipliers_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Roles & Multipliers', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => sprintf(
		/* translators: %s: translators Added */
			__( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module.', 'woocommerce-jetpack' ),
			admin_url( wcj_admin_tab_url() . '&wcj-cat=emails_and_misc&section=general' )
		),
		'id'    => 'wcj_price_by_user_role_multipliers_options',
	),
	array(
		'title'   => __( 'Disable Price based on User Role for Products on Sale', 'woocommerce-jetpack' ),
		'desc'    => __( 'Disable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_user_role_disable_for_products_on_sale',
		'default' => 'no',
		'type'    => 'checkbox',
	),
);
foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'             => $role_data['name'],
				'id'                => 'wcj_price_by_user_role_' . $role_key,
				'default'           => 1,
				'type'              => 'wcj_number_plus_checkbox_start',
				'custom_attributes' => array(
					'step' => '0.000001',
					'min'  => '0',
				),
			),
			array(
				'desc'    => __( 'Make Empty Price', 'woocommerce-jetpack' ),
				'id'      => 'wcj_price_by_user_role_empty_price_' . $role_key,
				'default' => 'no',
				'type'    => 'wcj_number_plus_checkbox_end',
				'class'   => 'wcj_multiplier_cls',
			),
		)
	);
}
$settings   = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_price_by_user_role_multipliers_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'price_by_user_role_roles_multipliers_tab',
			'type' => 'tab_end',
		),
	)
);
$taxonomies = array(
	array(
		'title'     => __( 'Products Categories', 'woocommerce-jetpack' ),
		'name'      => 'categories',
		'id'        => 'product_cat',
		'option_id' => 'cat',
	),
	array(
		'title'     => __( 'Products Tags', 'woocommerce-jetpack' ),
		'name'      => 'tags',
		'id'        => 'product_tag',
		'option_id' => 'tag',
	),
);

do_action( 'wcj_before_get_terms', $this->id );
foreach ( $taxonomies as $taxonomy_data ) {
	$product_taxonomies_options = array();
	$product_taxonomies         = get_terms( $taxonomy_data['id'], 'orderby=name&hide_empty=0' );
	if ( ! empty( $product_taxonomies ) && ! is_wp_error( $product_taxonomies ) ) {
		foreach ( $product_taxonomies as $product_taxonomy ) {
			$product_taxonomies_options[ $product_taxonomy->term_id ] = $product_taxonomy->name;
		}
	}
	$settings    = array_merge(
		$settings,
		array(
			array(
				'id'   => 'price_by_user_role_by_product_' . $taxonomy_data['name'] . '_tab',
				'type' => 'tab_start',
			),
			array(
				/* translators: %s: translators Added */
				'title' => sprintf( __( 'Price based on User Role by %s', 'woocommerce-jetpack' ), $taxonomy_data['title'] ),
				'type'  => 'title',
				'id'    => 'wcj_price_by_user_role_' . $taxonomy_data['name'] . '_options',
			),
			array(
				'title'             => $taxonomy_data['title'],
				'desc_tip'          => __( 'Save module\'s settings after changing this option to see new settings fields.', 'woocommerce-jetpack' ),
				'id'                => 'wcj_price_by_user_role_' . $taxonomy_data['name'],
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'options'           => $product_taxonomies_options,
				'desc'              => apply_filters( 'booster_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
			),
		)
	);
	$_taxonomies = apply_filters( 'booster_option', '', wcj_get_option( 'wcj_price_by_user_role_' . $taxonomy_data['name'], '' ) );
	if ( ! empty( $_taxonomies ) ) {
		foreach ( $_taxonomies as $_taxonomy ) {
			foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'title'             => $product_taxonomies_options[ $_taxonomy ] . ': ' . $role_data['name'],
							'desc_tip'          => __( 'Multiplier is ignored if set to negative number (e.g.: -1). Global multiplier will be used instead.', 'woocommerce-jetpack' ),
							'id'                => 'wcj_price_by_user_role_' . $taxonomy_data['option_id'] . '_' . $_taxonomy . '_' . $role_key,
							'default'           => -1,
							'type'              => 'wcj_number_plus_checkbox_start',
							'custom_attributes' => array(
								'step' => '0.000001',
								'min'  => -1,
							),
						),
						array(
							'desc'    => __( 'Make Empty Price', 'woocommerce-jetpack' ),
							'id'      => 'wcj_price_by_user_role_' . $taxonomy_data['option_id'] . '_empty_price_' . $_taxonomy . '_' . $role_key,
							'default' => 'no',
							'type'    => 'wcj_number_plus_checkbox_end',
						),
					)
				);
			}
		}
	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'wcj_price_by_user_role_' . $taxonomy_data['name'] . '_options',
				'type' => 'sectionend',
			),
			array(
				'id'   => 'price_by_user_role_by_product_' . $taxonomy_data['name'] . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
do_action( 'wcj_after_get_terms', $this->id );

return $settings;
