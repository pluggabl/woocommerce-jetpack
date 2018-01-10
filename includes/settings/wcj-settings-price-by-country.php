<?php
/**
 * Booster for WooCommerce - Settings - Prices and Currencies by Country
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//global $wcj_notice;
$settings = array(
	array(
		'title'    => __( 'Price by Country Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'Change product\'s price and currency by customer\'s country. Customer\'s country is detected automatically by IP, or selected by customer manually.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_options',
	),
	array(
		'title'    => __( 'Customer Country Detection Method', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_customer_country_detection_method',
		'desc'     => __( 'If you choose "by user selection", use [wcj_country_select_drop_down_list] shortcode to display country selection list on frontend.', 'woocommerce-jetpack' ),
		'default'  => 'by_ip',
		'type'     => 'select',
		'options'  => array(
			'by_ip'                        => __( 'by IP', 'woocommerce-jetpack' ),
			'by_ip_then_by_user_selection' => __( 'by IP, then by user selection', 'woocommerce-jetpack' ),
			'by_user_selection'            => __( 'by user selection', 'woocommerce-jetpack' ),
//			'by_wpml'                      => __( 'by WPML', 'woocommerce-jetpack' ),
		),
	),
	/*
	array(
		'title'    => __( 'Countries in [wcj_country_select_drop_down_list] shortcode\'s list', 'woocommerce-jetpack' ),
		'desc'     => __( 'Leave blank to list all countries', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_shortcode_countries',
		'default'  => '',
		'type'     => 'multiselect',
		'options'  => wcj_get_countries(),
		'class'    => 'chosen_select',
//		'css'      => 'width:50%;min-width:300px;height:100px;',
	),
	*/
	array(
		'title'    => __( 'Override Country Options', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_override_on_checkout_with_billing_country',
		'default'  => 'no',
		'type'     => 'select',
		'options'  => array(
			'no'               => __( 'No Override', 'woocommerce-jetpack' ),
			'yes'              => __( 'Override Country with Customer\'s Checkout Billing Country', 'woocommerce-jetpack' ),
			'shipping_country' => __( 'Override Country with Customer\'s Checkout Shipping Country', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'Override Scope', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_override_scope',
		'default'  => 'all',
		'type'     => 'select',
		'options'  => array(
			'all'               => __( 'All site', 'woocommerce-jetpack' ),
//			'cart_and_checkout' => __( 'Cart and checkout only', 'woocommerce-jetpack' ),
			'checkout'          => __( 'Checkout only', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Revert Currency to Default on Checkout', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_revert',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Price Rounding', 'woocommerce-jetpack' ),
		'desc'     => __( 'If you choose to multiply price, set rounding options here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_rounding',
		'default'  => 'none',
		'type'     => 'select',
		'options'  => array(
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
		'desc'     => __( 'Discount Min Amount Multiplier', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If "Make Pretty Price" is enabled, here you can set by how many smallest possible values (e.g. cents) final price should be decreased.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_make_pretty_min_amount_multiplier',
		'default'  => 1,
		'type'     => 'number',
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
		'desc'     => __( 'Per product options style', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_local_options_style',
		'default'  => 'inline',
		'type'     => 'select',
		'options'  => array(
			'inline'   => __( 'Inline', 'woocommerce-jetpack' ),
			'meta_box' => __( 'Separate meta box', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Price Filter Widget and Sorting by Price Support', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => '<a href="' . add_query_arg( 'recalculate_price_filter_products_prices', '1', remove_query_arg( array( 'wcj_generate_country_groups', 'wcj_generate_country_groups_confirm' ) ) ) . '">' .
			__( 'Recalculate price filter widget and sorting by price product prices', 'woocommerce-jetpack' ) . '</a>',
		'id'       => 'wcj_price_by_country_price_filter_widget_support_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
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
		'title'    => __( 'Search Engine Bots', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable Price by Country for Bots', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_for_bots_disabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_price_by_country_options',
	),
	array(
		'title'    => __( 'Country Groups', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_price_by_country_country_groups_options',
	),
	array(
		'title'    => __( 'Countries Selection', 'woocommerce-jetpack' ),
		'desc'     => __( 'Choose how do you want to enter countries groups in admin.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_selection',
		'default'  => 'chosen_select',
		'type'     => 'select',
		'options'  => array(
			'comma_list'    => __( 'Comma separated list', 'woocommerce-jetpack' ),
			'multiselect'   => __( 'Multiselect', 'woocommerce-jetpack' ),
			'chosen_select' => __( 'Chosen select', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Autogenerate Groups', 'woocommerce-jetpack' ),
		'id'       => 'wcj_' . $this->id . '_module_tools',
		'type'     => 'custom_link',
		'link'     => /* '<pre>' . $wcj_notice . '</pre>' . */
			'<pre>' .
				__( 'Currencies supported in both PayPal and Yahoo Exchange Rates:', 'woocommerce-jetpack' ) . ' ' .
				'<a href="' . add_query_arg( 'wcj_generate_country_groups', 'paypal_and_yahoo_exchange_rates_only', remove_query_arg( array( 'wcj_generate_country_groups_confirm', 'recalculate_price_filter_products_prices' ) ) ) . '">' .
				__( 'Generate', 'woocommerce-jetpack' ) . '</a>.' .
			'</pre>' .
			'<pre>' .
				__( 'Currencies supported in Yahoo Exchange Rates:', 'woocommerce-jetpack' ) . ' ' .
				'<a href="' . add_query_arg( 'wcj_generate_country_groups', 'yahoo_exchange_rates_only', remove_query_arg( array( 'wcj_generate_country_groups_confirm', 'recalculate_price_filter_products_prices' ) ) ) . '">' .
				__( 'Generate', 'woocommerce-jetpack' ) . '</a>.' .
			'</pre>' .
			'<pre>' .
				__( 'All Countries and Currencies:', 'woocommerce-jetpack' ) . ' ' .
				'<a href="' . add_query_arg( 'wcj_generate_country_groups', 'all', remove_query_arg( array( 'wcj_generate_country_groups_confirm', 'recalculate_price_filter_products_prices' ) ) ) . '">' .
				__( 'Generate', 'woocommerce-jetpack' ) . '</a>' .
			'</pre>',
	),
	array(
		'title'    => __( 'Groups Number', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_total_groups_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array('step' => '1', 'min' => '1', ) ),
		'css'      => 'width:100px;',
	),
);
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
	$admin_title = get_option( 'wcj_price_by_country_countries_group_admin_title_' . $i, __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i );
	if ( __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i == $admin_title ) {
		$admin_title = '';
	} else {
		$admin_title = ': ' . $admin_title;
	}
	$admin_title = __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i . $admin_title;
	switch ( get_option( 'wcj_price_by_country_selection', 'comma_list' ) ) {
		case 'comma_list':
			$settings[] = array(
				'title'    => $admin_title . ( '' != get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i, '' ) ?
					' (' . count( explode( ',', get_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i, '' ) ) ) . ')' : '' ),
				'desc'     => __( 'Countries. List of comma separated country codes.<br>For country codes and predifined sets visit <a href="http://booster.io/country-codes/" target="_blank">http://booster.io/country-codes/</a>', 'woocommerce-jetpack' ),
				'id'       => 'wcj_price_by_country_exchange_rate_countries_group_' . $i,
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'width:50%;min-width:300px;height:100px;',
			);
			break;
		case 'multiselect':
			$settings[] = array(
				'title'    => $admin_title . ( is_array( get_option( 'wcj_price_by_country_countries_group_' . $i, '' ) ) ?
					' (' . count( get_option( 'wcj_price_by_country_countries_group_' . $i, '' ) ) . ')' : '' ),
				'id'       => 'wcj_price_by_country_countries_group_' . $i,
				'default'  => '',
				'type'     => 'multiselect',
				'options'  => wcj_get_countries(),
				'css'      => 'width:50%;min-width:300px;height:100px;',
			);
			break;
		case 'chosen_select':
			$settings[] = array(
				'title'    => $admin_title . ( is_array( get_option( 'wcj_price_by_country_countries_group_chosen_select_' . $i, '' ) ) ?
					' (' . count( get_option( 'wcj_price_by_country_countries_group_chosen_select_' . $i, '' ) ) . ')' : '' ),
				'id'       => 'wcj_price_by_country_countries_group_chosen_select_' . $i,
				'default'  => '',
				'type'     => 'multiselect',
				'options'  => wcj_get_countries(),
				'class'    => 'chosen_select',
				'css'      => 'width:50%;min-width:300px;',
			);
			break;
	}
	$settings = array_merge( $settings, array(
		array(
			'desc'     => __( 'Currency', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_by_country_exchange_rate_currency_group_' . $i,
			'default'  => 'EUR',
			'type'     => 'select',
			'options'  => wcj_get_currencies_names_and_symbols(),
		),
		array(
			'desc'     => __( 'Admin Title', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_by_country_countries_group_admin_title_' . $i,
			'default'  => __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i,
			'type'     => 'text',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_price_by_country_country_groups_options',
	),
	array(
		'title'    => __( 'Exchange Rates', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_price_by_country_exchange_rate_options',
	),
	array(
		'title'    => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_auto_exchange_rates',
		'default'  => 'manual',
		'type'     => 'select',
		'options'  => array(
			'manual'     => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
			'auto'       => __( 'Automatically via Currency Exchange Rates module', 'woocommerce-jetpack' ),
		),
		'desc'     => ( '' == apply_filters( 'booster_message', '', 'desc' ) )
			? __( 'Visit', 'woocommerce-jetpack' ) . ' <a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_and_currencies&section=currency_exchange_rates' ) . '">' . __( 'Currency Exchange Rates module', 'woocommerce-jetpack' ) . '</a>'
			: apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
) );
$currency_from = apply_filters( 'woocommerce_currency', get_option('woocommerce_currency') );
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
	$currency_to = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
	$custom_attributes = array(
		'currency_from' => $currency_from,
		'currency_to'   => $currency_to,
		'multiply_by_field_id'   => 'wcj_price_by_country_exchange_rate_group_' . $i,
	);
	if ( $currency_from == $currency_to ) {
		$custom_attributes['disabled'] = 'disabled';
	}
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i,
			'desc'     => __( 'Multiply Price by', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_by_country_exchange_rate_group_' . $i,
			'default'  => 1,
			'type'     => 'exchange_rate',
			'custom_attributes_button' => $custom_attributes,
			'value'    => $currency_from . '/' . $currency_to,
		),
		/*
		array(
//			'id'       => 'wcj_price_by_country_exchange_rate_refresh_group_' . $i,
			'class'    => 'exchage_rate_button',
			'type'     => 'custom_number',
			'css'      => 'width:300px;',
			'custom_attributes' => $custom_attributes,
		),
		*/
		array(
			'desc'     => __( 'Make empty price', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_by_country_make_empty_price_group_' . $i,
			'default'  => 'no',
			'type'     => 'checkbox',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_price_by_country_exchange_rate_options',
	),
) );
/*
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Country Select Box Customization', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'Only if "by user selection" method selected in "Customer Country Detection Method"', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_country_selection_box_options',
	),
	array(
		'title'    => __( 'Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_country_selection_box_position',
		'default'  => 'woocommerce_get_price_html',
		'type'     => 'select',
		'options'  => array(
			'woocommerce_get_price_html' => __( 'woocommerce_get_price_html', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Position Priority (Order)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_country_selection_box_priority',
		'default'  => 10,
		'type'     => 'number',
	),
	array(
		'title'    => __( 'Custom Class', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_country_selection_box_class',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Custom Style', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_country_selection_box_style',
		'default'  => '',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Method (GET/POST)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_by_country_country_selection_box_method',
		'default'  => 'get',
		'type'     => 'select',
		'options'  => array(
			'get'  => __( 'GET', 'woocommerce-jetpack' ),
			'post' => __( 'POST', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_price_by_country_country_selection_box_options',
	),
) );
*/
return $settings;
