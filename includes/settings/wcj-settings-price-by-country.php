<?php
/**
 * Booster for WooCommerce - Settings - Prices and Currencies by Country
 *
 * @version 7.0.0
 * @since   2.8.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$message                   = apply_filters( 'booster_message', '', 'desc' );
$autogenerate_buttons      = array();
$autogenerate_buttons_data = array(
	'all'         => __( 'All countries and currencies', 'woocommerce-jetpack' ),
	'paypal_only' => __( 'PayPal supported currencies only', 'woocommerce-jetpack' ),
);
foreach ( $autogenerate_buttons_data as $autogenerate_button_id => $autogenerate_button_desc ) {
	$autogenerate_buttons[] = ( 0 === apply_filters( 'booster_option', 1, '' ) ?
	'<a class="button wcj-btn-sm wcj-autogenerate-button" disabled title="' . __( 'Available in Booster Plus only.', 'woocommerce-jetpack' ) . '">' . $autogenerate_button_desc . '</a>' :
	'<a class="button wcj-btn-sm wcj-autogenerate-button" href="' .
		esc_url(
			add_query_arg(
				array(
					'wcj_generate_country_groups'       => $autogenerate_button_id,
					'wcj_generate_country_groups-nonce' => wp_create_nonce( 'wcj_generate_country_groups' ),
				),
				remove_query_arg( 'recalculate_price_filter_products_prices' )
			)
		) . '"' .
		wcj_get_js_confirmation( __( 'All existing country groups will be deleted and new groups will be created. Are you sure?', 'woocommerce-jetpack' ) ) . '>' .
			$autogenerate_button_desc .
	'</a>' );
}
$autogenerate_buttons = implode( ' ', $autogenerate_buttons );

$settings = array(
	array(
		'id'   => 'price_by_country_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'price_by_country_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'price_by_country_price_by_country_tab' => __( 'Price by Country', 'woocommerce-jetpack' ),
			'price_by_country_compatibiliry_tab'    => __( 'Compatibility', 'woocommerce-jetpack' ),
			'price_by_country_advanced_tab'         => __( 'Advanced', 'woocommerce-jetpack' ),
			'price_by_country_country_groups_tab'   => __( 'Country Groups', 'woocommerce-jetpack' ),
			'price_by_country_exchange_rates_tab'   => __( 'Exchange Rates', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'price_by_country_price_by_country_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Price by Country Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Change product\'s price and currency by customer\'s country. Customer\'s country is detected automatically by IP, or selected by customer manually.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_price_by_country_options',
	),
	array(
		'title'   => __( 'Customer Country Detection Method', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_customer_country_detection_method',
		'desc'    => __( 'If you choose "by user selection", use [wcj_country_select_drop_down_list] shortcode to display country selection list on frontend.', 'woocommerce-jetpack' ),
		'default' => 'by_ip',
		'type'    => 'select',
		'options' => array(
			'by_ip'                        => __( 'by IP', 'woocommerce-jetpack' ),
			'by_ip_then_by_user_selection' => __( 'by IP, then by user selection', 'woocommerce-jetpack' ),
			'by_user_selection'            => __( 'by user selection', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Override Country Options', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_override_on_checkout_with_billing_country',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'no'               => __( 'No Override', 'woocommerce-jetpack' ),
			'yes'              => __( 'Override Country with Customer\'s Checkout Billing Country', 'woocommerce-jetpack' ),
			'shipping_country' => __( 'Override Country with Customer\'s Checkout Shipping Country', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'    => __( 'Override Scope', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_override_scope',
		'default' => 'all',
		'type'    => 'select',
		'options' => array(
			'all'      => __( 'All site', 'woocommerce-jetpack' ),
			'checkout' => __( 'Checkout only', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Revert Currency to Default on Checkout', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_revert',
		'default'  => 'no',
		'type'     => 'checkbox',
		'desc_tip' => __( 'If selected currency is &#8364; and your shop currency is &#36;, So if you want to show &#36; on the checkout page you can enable the option.', 'woocommerce-jetpack' ),
	),
	array(
		'title'   => __( 'Auto set default checkout billing country', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_set_dft_checkout_billing_country',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Price Rounding', 'woocommerce-jetpack' ),
		'desc'    => __( 'If you choose to multiply price, set rounding options here.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_rounding',
		'default' => 'none',
		'type'    => 'select',
		'options' => array(
			'none'  => __( 'No rounding', 'woocommerce-jetpack' ),
			'round' => __( 'Round', 'woocommerce-jetpack' ),
			'floor' => __( 'Round down', 'woocommerce-jetpack' ),
			'ceil'  => __( 'Round up', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Make Pretty Price', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If enabled, this will be applied if exchange rates are used. Final converted price will be rounded, then decreased by smallest possible value. For example: $9,75 -> $10,00 -> $9,99. Please note that as smallest possible value is calculated from shop\'s "Precision" option, this option must be above zero.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_make_pretty',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'desc'              => __( 'Discount Min Amount Multiplier', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'If "Make Pretty Price" is enabled, here you can set by how many smallest possible values (e.g. cents) final price should be decreased.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_price_by_country_make_pretty_min_amount_multiplier',
		'default'           => 1,
		'type'              => 'number',
		'custom_attributes' => array( 'min' => '1' ),
	),
	array(
		'title'    => __( 'Price by Country on per Product Basis', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This will add product data fields in product edit.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_local_enabled',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'desc'    => __( 'Per product options - backend style', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_local_options_style',
		'default' => 'inline',
		'type'    => 'select',
		'options' => array(
			'inline'   => __( 'Inline', 'woocommerce-jetpack' ),
			'meta_box' => __( 'Separate meta box', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'Per product options - backend user role visibility', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Leave empty to show to all user roles.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_backend_user_roles',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => wcj_get_user_roles_options(),
	),
	array(
		'title'    => __( 'Add Countries Flags Images to Select Drop-Down Box', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If you are using [wcj_country_select_drop_down_list] shortcode or "Booster: Country Switcher" widget, this will add country flags to these select boxes.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_jquery_wselect_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Search Engine Bots', 'woocommerce-jetpack' ),
		'desc'    => __( 'Disable Price by Country for Bots', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_for_bots_disabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'id'   => 'wcj_price_by_country_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'price_by_country_price_by_country_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'price_by_country_compatibiliry_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Compatibility', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_price_by_country_compatibility',
	),
	array(
		'title'   => __( 'Active Webtofee subscription Section', 'woocommerce-jetpack' ),
		'desc'    => __( 'Compatibility with Webtofee subscription PLugin', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_Webtofee_subscription_price_group',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Active B2B Plugin Price Section', 'woocommerce-jetpack' ),
		'desc'    => __( 'Active B2B Plugin Price Section', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_b2b_sale_price_group',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Quick Edit Product For Admin Scope', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable For Admin Quick Edit Scope.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Disable module on Edit Product For Admin scope.', 'woocommerce-jetpack' ) . '<br />' . __( 'For example if you use Quick Edit Product  and donot want change the deafult price then the  box ticked', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_price_by_country_admin_quick_edit_product_scope',
		'default'  => 'no',
	),

	array(
		'title'             => __( 'Price Filter Widget and Sorting by Price Support', 'woocommerce-jetpack' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc_tip'          => '<a href="' . esc_url(
			add_query_arg(
				array(
					'recalculate_price_filter_products_prices' => '1',
					'recalculate_price_filter_products_prices-nonce' => wp_create_nonce( 'recalculate_price_filter_products_prices' ),
				),
				remove_query_arg( array( 'wcj_generate_country_groups', 'wcj_generate_country_groups-nonce' ) )
			)
		) . '">' .
							__( 'Recalculate price filter widget and sorting by price product prices.', 'woocommerce-jetpack' ) . '</a>',
		'id'                => 'wcj_price_by_country_price_filter_widget_support_enabled',
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'title'             => __( 'Free Shipping', 'woocommerce-jetpack' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc_tip'          => __( 'Converts minimum amount from WooCommerce Free Shipping native method.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_price_by_country_compatibility_free_shipping',
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'title'    => __( 'WooCommerce Coupons', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'When a fixed coupon is used its value changes according to the current currency.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_compatibility_wc_coupons',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'             => __( 'Woo Discount Rules', 'woocommerce-jetpack' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		/* translators: %s: translators Added */
		'desc_tip'          => sprintf( __( 'Adds compatibility with <a href="%s" target="_blank">Woo Discount Rules</a> plugin.', 'woocommerce-jetpack' ), 'https://www.flycart.org/products/wordpress/woocommerce-discount-rules' ) . '<br />' . sprintf( __( 'If it doesn\'t work properly try to enable <a href="%s">redirect to the cart page after successful addition</a> option.', 'woocommerce-jetpack' ), admin_url( 'admin.php?page=wc-settings&tab=products' ) ),
		'id'                => 'wcj_price_by_country_compatibility_woo_discount_rules',
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'title'             => __( 'WooCommerce Points and Rewards', 'woocommerce-jetpack' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		'desc'              => empty( $message ) ? __( 'Enable', 'woocommerce-jetpack' ) : $message,
		/* translators: %s: translators Added */
		'desc_tip'          => sprintf( __( 'Adds compatibility with <a href="%s" target="_blank">WooCommerce Points and Rewards</a> plugin.', 'woocommerce-jetpack' ), 'https://woocommerce.com/products/woocommerce-points-and-rewards/' ),
		'id'                => 'wcj_price_by_country_comp_woo_points_rewards',
		'default'           => 'no',
		'type'              => 'checkbox',
	),
	array(
		'id'   => 'wcj_price_by_country_compatibility',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'price_by_country_compatibiliry_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'price_by_country_advanced_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Advanced', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_price_by_country_country_advanced',
	),
	array(
		'title'    => __( 'Price Filters Priority', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Priority for all module\'s price filters. If you face pricing issues while using another plugin or booster module, You can change the Priority, Greater value for high priority & Lower value for low priority. Set to zero to use default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_advanced_price_hooks_priority',
		'default'  => 0,
		'type'     => 'number',
	),
	array(
		'title'   => __( 'User IP Detection Method', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_ip_detection_method',
		'default' => 'wc',
		'type'    => 'select',
		'options' => array(
			'wc'      => __( 'WooCommerce', 'woocommerce-jetpack' ),
			'booster' => __( 'Booster', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Price Format Method', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'The moment "Pretty Price" and "Rounding" will be applied' ),
		'id'       => 'wcj_price_by_country_price_format_method',
		'default'  => 'get_price',
		'type'     => 'select',
		'options'  => array(
			'get_price'               => __( 'get_price()', 'woocommerce-jetpack' ),
			'wc_get_price_to_display' => __( 'wc_get_price_to_display()', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Save Calculated Products Prices', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'This may help if you are experiencing compatibility issues with other plugins.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_save_prices',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Save Country Group ID', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Try to disable it if the country detection is not correct, most probably if "Override Country Options" is enabled.', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_save_country_group_id',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'id'   => 'wcj_price_by_country_country_advanced',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'price_by_country_advanced_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'price_by_country_country_groups_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Country Groups', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_price_by_country_country_groups_options',
	),
	array(
		'title'   => __( 'Countries Selection', 'woocommerce-jetpack' ),
		'desc'    => __( 'Choose how do you want to enter countries groups in admin.', 'woocommerce-jetpack' ),
		'id'      => 'wcj_price_by_country_selection',
		'default' => 'chosen_select',
		'type'    => 'select',
		'options' => array(
			'comma_list'    => __( 'Comma separated list', 'woocommerce-jetpack' ),
			'multiselect'   => __( 'Multiselect', 'woocommerce-jetpack' ),
			'chosen_select' => __( 'Chosen select', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title' => __( 'Autogenerate Groups', 'woocommerce-jetpack' ),
		'id'    => 'wcj_' . $this->id . '_module_tools',
		'type'  => 'custom_link',
		'link'  => $autogenerate_buttons,
	),
	array(
		'title'             => __( 'Groups Number', 'woocommerce-jetpack' ),
		'id'                => 'wcj_price_by_country_total_groups_number',
		'default'           => 1,
		'type'              => 'custom_number',
		'desc'              => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array(
				'step' => '1',
				'min'  => '1',
			)
		),
		'css'               => 'width:100px;',
	),
);
$country_total_num = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_by_country_total_groups_number', 1 ) );
for ( $i = 1; $i <= $country_total_num; $i++ ) {
	$admin_title = wcj_get_option( 'wcj_price_by_country_countries_group_admin_title_' . $i, __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i );
	if ( __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i === $admin_title ) {
		$admin_title = '';
	} else {
		$admin_title = ': ' . $admin_title;
	}
	$admin_title = __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i . $admin_title;
	switch ( wcj_get_option( 'wcj_price_by_country_selection', 'comma_list' ) ) {
		case 'comma_list':
			$settings[] = array(
				'title'   => $admin_title . ( '' !== wcj_get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i, '' ) ?
					' (' . count( explode( ',', wcj_get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i, '' ) ) ) . ')' : '' ),
				'desc'    => __( 'Countries. List of comma separated country codes.<br>For country codes and predefined sets visit <a href="https://booster.io/country-codes/" target="_blank">https://booster.io/country-codes/</a>', 'woocommerce-jetpack' ),
				'id'      => 'wcj_price_by_country_exchange_rate_countries_group_' . $i,
				'default' => '',
				'type'    => 'textarea',
				'css'     => 'width:50%;min-width:300px;height:100px;',
			);
			break;
		case 'multiselect':
			$settings[] = array(
				'title'   => $admin_title . ( is_array( wcj_get_option( 'wcj_price_by_country_countries_group_' . $i, '' ) ) ?
					' (' . count( wcj_get_option( 'wcj_price_by_country_countries_group_' . $i, '' ) ) . ')' : '' ),
				'id'      => 'wcj_price_by_country_countries_group_' . $i,
				'default' => '',
				'type'    => 'multiselect',
				'options' => wcj_get_countries(),
				'css'     => 'width:50%;min-width:300px;height:100px;',
			);
			break;
		case 'chosen_select':
			$settings[] = array(
				'title'   => $admin_title . ( is_array( wcj_get_option( 'wcj_price_by_country_countries_group_chosen_select_' . $i, '' ) ) ?
					' (' . count( wcj_get_option( 'wcj_price_by_country_countries_group_chosen_select_' . $i, '' ) ) . ')' : '' ),
				'id'      => 'wcj_price_by_country_countries_group_chosen_select_' . $i,
				'default' => '',
				'type'    => 'multiselect',
				'options' => wcj_get_countries(),
				'class'   => 'chosen_select',
				'css'     => 'width:50%;min-width:300px;',
			);
			break;
	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'desc'    => __( 'Currency', 'woocommerce-jetpack' ),
				'id'      => 'wcj_price_by_country_exchange_rate_currency_group_' . $i,
				'default' => 'EUR',
				'type'    => 'select',
				'class'   => 'wcj_select_search_input',
				'options' => wcj_get_woocommerce_currencies_and_symbols(),
			),
			array(
				'desc'    => __( 'Admin Title', 'woocommerce-jetpack' ),
				'id'      => 'wcj_price_by_country_countries_group_admin_title_' . $i,
				'default' => __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'    => 'text',
			),
		)
	);
}
$settings                   = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_price_by_country_country_groups_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'price_by_country_country_groups_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'price_by_country_exchange_rates_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Exchange Rates', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_price_by_country_exchange_rate_options',
		),
		array(
			'title'             => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
			'id'                => 'wcj_price_by_country_auto_exchange_rates',
			'default'           => 'manual',
			'type'              => 'select',
			'options'           => array(
				'manual' => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
				'auto'   => __( 'Automatically via Currency Exchange Rates module', 'woocommerce-jetpack' ),
			),
			'desc'              => ( '' === apply_filters( 'booster_message', '', 'desc' ) )
				? __( 'Visit', 'woocommerce-jetpack' ) . ' <a href="' . admin_url( wcj_admin_tab_url() . '&wcj-cat=prices_and_currencies&section=currency_exchange_rates' ) . '">' . __( 'Currency Exchange Rates module', 'woocommerce-jetpack' ) . '</a>'
				: apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		),
	)
);
$price_by_country_total_num = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_by_country_total_groups_number', 1 ) );
$currency_from              = apply_filters( 'woocommerce_currency', wcj_get_option( 'woocommerce_currency' ) );
for ( $i = 1; $i <= $price_by_country_total_num;  $i++ ) {
	$currency_to       = wcj_get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
	$custom_attributes = array(
		'currency_from'        => $currency_from,
		'currency_to'          => $currency_to,
		'multiply_by_field_id' => 'wcj_price_by_country_exchange_rate_group_' . $i,
	);
	if ( $currency_from === $currency_to ) {
		$custom_attributes['disabled'] = 'disabled';
	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'                    => __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'                     => __( 'Multiply Price by', 'woocommerce-jetpack' ),
				'id'                       => 'wcj_price_by_country_exchange_rate_group_' . $i,
				'default'                  => 1,
				'type'                     => 'exchange_rate',
				'custom_attributes_button' => $custom_attributes,
				'value'                    => $currency_from . '/' . $currency_to,
			),
			array(
				'desc'    => __( 'Make empty price', 'woocommerce-jetpack' ),
				'id'      => 'wcj_price_by_country_make_empty_price_group_' . $i,
				'default' => 'no',
				'type'    => 'checkbox',
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_price_by_country_exchange_rate_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'price_by_country_exchange_rates_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
