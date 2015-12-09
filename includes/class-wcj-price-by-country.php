<?php
/**
 * WooCommerce Jetpack Price by Country
 *
 * The WooCommerce Jetpack Price by Country class.
 *
 * @version 2.3.9
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_By_Country' ) ) :

class WCJ_Price_By_Country extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.9
	 */
	public function __construct() {

		$this->id         = 'price_by_country';
		$this->short_desc = __( 'Prices and Currencies by Country', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change WooCommerce product price and currency automatically by customer\'s country.', 'woocommerce-jetpack' );
		parent::__construct();

		global $wcj_notice;
		$wcj_notice = '';

		if ( $this->is_enabled() ) {

			if ( ! is_admin() ) { // || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				// Frontend
				include_once( 'price-by-country/class-wcj-price-by-country-core.php' );
			}
			if ( is_admin() ) {
				// Backend
				include_once( 'price-by-country/class-wcj-price-by-country-reports.php' );
				if ( 'yes' === get_option( 'wcj_price_by_country_local_enabled' ) ) {
					include_once( 'price-by-country/class-wcj-price-by-country-local.php' );
				}
			}
		}

		if ( is_admin() ) {
			include_once( 'price-by-country/class-wcj-price-by-country-group-generator.php' );
		}
	}

	/**
	 * get_settings.
	 *
	 * @version 2.3.9
	 */
	function get_settings() {

		global $wcj_notice;

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
					'by_ip'             => __( 'by IP', 'woocommerce-jetpack' ),
					'by_user_selection' => __( 'by user selection', 'woocommerce-jetpack' ),
//					'by_wpml'           => __( 'by WPML', 'woocommerce-jetpack' ),
				),
			),

			/* array(
				'title'    => __( 'Countries in [wcj_country_select_drop_down_list] shortcode\'s list', 'woocommerce-jetpack' ),
				'desc'     => __( 'Leave blank to list all countries', 'woocommerce-jetpack' ),
				'id'       => 'wcj_price_by_country_shortcode_countries',
				'default'  => '',
				'type'     => 'multiselect',
				'options'  => wcj_get_countries(),
				'class'    => 'chosen_select',
//				'css'      => 'width:50%;min-width:300px;height:100px;',
			), */

			array(
				'title'    => __( 'Override Country on Checkout with Billing Country', 'woocommerce-jetpack' ),
				'id'       => 'wcj_price_by_country_override_on_checkout_with_billing_country',
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
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
				'title'    => __( 'Price by Country on per Product Basis', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'This will add meta boxes in product edit.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_price_by_country_local_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),

			array( 'type'  => 'sectionend', 'id' => 'wcj_price_by_country_options' ),

			array( 'title' => __( 'Country Groups', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => '', 'id' => 'wcj_price_by_country_country_groups_options' ),

			array(
				'title'    => __( 'Autogenerate Groups', 'woocommerce-jetpack' ),
				'id'       => 'wcj_' . $this->id . '_module_tools',
				'type'     => 'custom_link',
				'link'     => /* '<pre>' . $wcj_notice . '</pre>' . */
					'<pre>' .
						__( 'Currencies supported in both PayPal and Yahoo Exchange Rates:', 'woocommerce-jetpack' ) . ' ' .
						'<a href="' . add_query_arg( 'wcj_generate_country_groups', 'paypal_and_yahoo_exchange_rates_only', remove_query_arg( 'wcj_generate_country_groups_confirm' ) ) . '">' .
						__( 'Generate', 'woocommerce-jetpack' ) . '</a>.' .
					'</pre>' .
					'<pre>' .
						__( 'Currencies supported in Yahoo Exchange Rates:', 'woocommerce-jetpack' ) . ' ' .
						'<a href="' . add_query_arg( 'wcj_generate_country_groups', 'yahoo_exchange_rates_only', remove_query_arg( 'wcj_generate_country_groups_confirm' ) ) . '">' .
						__( 'Generate', 'woocommerce-jetpack' ) . '</a>.' .
					'</pre>' .
					'<pre>' .
						__( 'All Countries and Currencies:', 'woocommerce-jetpack' ) . ' ' .
						'<a href="' . add_query_arg( 'wcj_generate_country_groups', 'all', remove_query_arg( 'wcj_generate_country_groups_confirm' ) ) . '">' .
						__( 'Generate', 'woocommerce-jetpack' ) . '</a>' .
					'</pre>',
					/* '<pre><a href="' . add_query_arg( 'wcj_generate_country_groups', 'paypal_only', remove_query_arg( 'wcj_generate_country_groups_confirm' ) ) . '">' .
						__( 'Create only PayPal country groups', 'woocommerce-jetpack' ) . '</a></pre>' . */
			),

			array(
				'title'    => __( 'Groups Number', 'woocommerce-jetpack' ),
				'id'       => 'wcj_price_by_country_total_groups_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array('step' => '1', 'min' => '1', ) ),
				'css'      => 'width:100px;',
			),
		);

		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {

			$settings[] = array(
				'title'    => __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'     => __( 'Countries. List of comma separated country codes.<br>For country codes and predifined sets visit <a href="http://booster.io/features/prices-and-currencies-by-customers-country" target="_blank">http://booster.io</a>', 'woocommerce-jetpack' ),
				'id'       => 'wcj_price_by_country_exchange_rate_countries_group_' . $i,
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'width:50%;min-width:300px;height:100px;',
			);

			/* TODO: Multiselect instead of comma separated list.
			$settings[] = array(
				'title'    => __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'       => 'wcj_price_by_country_countries_group_' . $i,
				'default'  => '',
				'type'     => 'multiselect',
				'options'  => wcj_get_countries(),
				//'class'    => 'chosen_select',
				'css'      => 'width:50%;min-width:300px;height:100px;',
			);
			*/

			$settings[] = array(
				'title'    => '',
				'desc'     => __( 'Currency', 'woocommerce-jetpack' ),
				'id'       => 'wcj_price_by_country_exchange_rate_currency_group_' . $i,
				'default'  => 'EUR',
				'type'     => 'select',
				'options'  => wcj_get_currencies_names_and_symbols(),
			);
		}

		$settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_price_by_country_country_groups_options' );

		$settings[] = array( 'title' => __( 'Exchange Rates', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => '', 'id' => 'wcj_price_by_country_exchange_rate_options' );

		$settings[] = array(
			'title'    => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_by_country_auto_exchange_rates',
			'default'  => 'manual',
			'type'     => 'select',
			'options'  => array(
				'manual'     => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
				'auto'       => __( 'Automatically via Currency Exchange Rates module', 'woocommerce-jetpack' ),
				/* 'hourly'     => __( 'Automatically: Update Hourly', 'woocommerce-jetpack' ),
				'twicedaily' => __( 'Automatically: Update Twice Daily', 'woocommerce-jetpack' ),
				'daily'      => __( 'Automatically: Update Daily', 'woocommerce-jetpack' ),
				'weekly'     => __( 'Automatically: Update Weekly', 'woocommerce-jetpack' ),
				'minutely'   => __( 'Automatically: Update Every Minute', 'woocommerce-jetpack' ), */
			),
			'desc'     => ( '' == apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ) ) ?
				__( 'Visit', 'woocommerce-jetpack' ) . ' <a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_and_currencies&section=currency_exchange_rates' ) . '">' . __( 'Currency Exchange Rates module', 'woocommerce-jetpack' ) . '</a>'
				:
	            apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
			'custom_attributes'
			           => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
		);

		$currency_from = apply_filters( 'woocommerce_currency', get_option('woocommerce_currency') );
		for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {

			$currency_to = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );

			$custom_attributes = array(
				'currency_from' => $currency_from,
				'currency_to'   => $currency_to,
				'multiply_by_field_id'   => 'wcj_price_by_country_exchange_rate_group_' . $i,
			);
			if ( $currency_from == $currency_to )
				$custom_attributes['disabled'] = 'disabled';

			$settings[] = array(
				'title'    => __( 'Group', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'     => __( 'Multiply Price by', 'woocommerce-jetpack' ),
				'id'       => 'wcj_price_by_country_exchange_rate_group_' . $i,
				'default'  => 1,
				'type'     => 'exchange_rate',
				'css'      => 'width:100px;',
				'custom_attributes' => array( 'step' => '0.000001', 'min'  => '0', ),
				'custom_attributes_button' => $custom_attributes,
				'value'    => $currency_from . '/' . $currency_to,
				'value_title' => sprintf( __( 'Grab %s rate from Yahoo.com', 'woocommerce-jetpack' ),  $currency_from . '/' . $currency_to ),
			);

			/* $settings[] = array(
				'title'    => '',
				//'id'       => 'wcj_price_by_country_exchange_rate_refresh_group_' . $i,
				'class'    => 'exchage_rate_button',
				'type'     => 'custom_number',
				'css'      => 'width:300px;',
				'value'    => sprintf( __( '%s rate from Yahoo.com', 'woocommerce-jetpack' ),  $currency_from . '/' . $currency_to ),
				'custom_attributes'	=> $custom_attributes,
			); */

			$settings[] = array(
				'title'    => '',
				'desc'     => __( 'Make empty price', 'woocommerce-jetpack' ),
				'id'       => 'wcj_price_by_country_make_empty_price_group_' . $i,
				'default'  => 'no',
				'type'     => 'checkbox',
			);
		}

		$settings[] = array( 'type'  => 'sectionend', 'id' => 'wcj_price_by_country_exchange_rate_options' );

		/* $settings = array_merge( $settings, array(

			array(
				'title' => __( 'Country Select Box Customization', 'woocommerce-jetpack' ),
				'type'  => 'title',
				'desc'  => __( 'Only if "by user selection" method selected in "Customer Country Detection Method"', 'woocommerce-jetpack' ),
				'id'    => 'wcj_price_by_country_country_selection_box_options',
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
				'type' => 'sectionend',
				'id'   => 'wcj_price_by_country_country_selection_box_options'
			),
		) ); */

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Price_By_Country();
