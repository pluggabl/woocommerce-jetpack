<?php
/**
 * Booster for WooCommerce - Settings - Tax Display
 *
 * @version 5.6.0
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Prepare products.
$products = wcj_get_products();

// Prepare categories.
$product_cats = wcj_get_terms( 'product_cat' );

$settings                         = array(
	array(
		'title' => __( 'TAX Display - Toggle Button', 'woocommerce-jetpack' ),
		'type'  => 'title',
		/* translators: %s: translators Added */
		'desc'  => sprintf( __( 'Use %s shortcode to display the button on frontend.', 'woocommerce-jetpack' ), '<code>[wcj_button_toggle_tax_display]</code>' ),
		'id'    => 'wcj_tax_display_toggle_options',
	),
	array(
		'title'   => __( 'TAX Toggle Button', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_tax_display_toggle_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_tax_display_toggle_options',
	),
	array(
		'title' => __( 'TAX Display by Product', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'If you want to display part of your products including TAX and another part excluding TAX, you can set it here.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_product_listings_display_taxes_options',
	),
	array(
		'title'   => __( 'TAX Display by Product', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_product_listings_display_taxes_by_products_enabled',
		'type'    => 'checkbox',
		'default' => 'no',
	),
	array(
		'title'    => __( 'Products - Including TAX', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_display_taxes_products_incl_tax',
		'desc_tip' => __( 'Select products to display including TAX.', 'woocommerce-jetpack' ),
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $products,
	),
	array(
		'title'    => __( 'Products - Excluding TAX', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_display_taxes_products_excl_tax',
		'desc_tip' => __( 'Select products to display excluding TAX.', 'woocommerce-jetpack' ),
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $products,
	),
	array(
		'title'    => __( 'Product Categories - Including TAX', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_display_taxes_product_cats_incl_tax',
		'desc_tip' => __( 'Select product categories to display including TAX.', 'woocommerce-jetpack' ),
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $product_cats,
	),
	array(
		'title'    => __( 'Product Categories - Excluding TAX', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_listings_display_taxes_product_cats_excl_tax',
		'desc_tip' => __( 'Select product categories to display excluding TAX.', 'woocommerce-jetpack' ),
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'css'      => 'width: 450px;',
		'options'  => $product_cats,
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_product_listings_display_taxes_options',
	),
	array(
		'title' => __( 'TAX Display by User Role', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'If you want to display prices including TAX or excluding TAX for different user roles, you can set it here.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_product_listings_display_taxes_by_user_role_options',
	),
	array(
		'title'   => __( 'TAX Display by User Role', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_product_listings_display_taxes_by_user_role_enabled',
		'type'    => 'checkbox',
		'default' => 'no',
	),
	array(
		'title'    => __( 'User Roles', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Save changes after you change this option and new settings fields will appear.', 'woocommerce-jetpack' ),
		'desc'     => '<br>' . sprintf(
			/* translators: %s: translators Added */
			__( 'Select user roles that you want to change tax display for. For all remaining (i.e. not selected) user roles - default TAX display (set in %s) will be applied.', 'woocommerce-jetpack' ),
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=tax' ) . '">' . __( 'WooCommerce > Settings > Tax', 'woocommerce-jetpack' ) . '</a>'
		),
		'id'       => 'wcj_product_listings_display_taxes_by_user_role_roles',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'default'  => '',
		'options'  => wcj_get_user_roles_options(),
	),
);
$display_taxes_by_user_role_roles = wcj_get_option( 'wcj_product_listings_display_taxes_by_user_role_roles', '' );
if ( '' !== ( $display_taxes_by_user_role_roles ) ) {
	foreach ( $display_taxes_by_user_role_roles as $display_taxes_by_user_role_role ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					/* translators: %s: translators Added */
					'title'    => sprintf( __( 'Role: %s - shop', 'woocommerce-jetpack' ), $display_taxes_by_user_role_role ),
					'id'       => 'wcj_product_listings_display_taxes_by_user_role_' . $display_taxes_by_user_role_role,
					'desc_tip' => __( 'Setup how taxes will be applied during in the shop.', 'woocommerce-jetpack' ),
					'default'  => 'no_changes',
					'type'     => 'select',
					'options'  => array(
						'no_changes' => __( 'Default TAX display (no changes)', 'woocommerce-jetpack' ),
						'incl'       => __( 'Including tax', 'woocommerce-jetpack' ),
						'excl'       => __( 'Excluding tax', 'woocommerce-jetpack' ),
					),
				),
				array(
					/* translators: %s: translators Added */
					'title'    => sprintf( __( 'Role: %s - cart', 'woocommerce-jetpack' ), $display_taxes_by_user_role_role ),
					'id'       => 'wcj_product_listings_display_taxes_on_cart_by_user_role_' . $display_taxes_by_user_role_role,
					'desc_tip' => __( 'Setup how taxes will be applied during cart and checkout.', 'woocommerce-jetpack' ),
					'default'  => 'no_changes',
					'type'     => 'select',
					'options'  => array(
						'no_changes' => __( 'Default TAX display (no changes)', 'woocommerce-jetpack' ),
						'incl'       => __( 'Including tax', 'woocommerce-jetpack' ),
						'excl'       => __( 'Excluding tax', 'woocommerce-jetpack' ),
					),
				),
			)
		);
	}
}
$settings = array_merge(
	$settings,
	array(
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_product_listings_display_taxes_by_user_role_options',
		),
	)
);
return $settings;
