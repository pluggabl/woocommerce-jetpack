<?php
/**
 * Booster for WooCommerce - Reports - Currency
 *
 * @version  3.9.0
 * @author   Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Currency_Reports' ) ) :

class WCJ_Currency_Reports {

	/**
	 * Constructor.
	 */
	function __construct() {
		add_filter( 'woocommerce_reports_get_order_report_data_args', array( $this, 'filter_reports'),                     PHP_INT_MAX, 1 );
		add_filter( 'woocommerce_currency_symbol',                    array( $this, 'change_currency_symbol_reports'),     PHP_INT_MAX, 2 );
		add_action( 'admin_bar_menu',                                 array( $this, 'add_reports_currency_to_admin_bar' ), PHP_INT_MAX );
	}

	/**
	 * add_reports_currency_to_admin_bar.
	 *
	 * @version 2.5.2
	 */
	function add_reports_currency_to_admin_bar( $wp_admin_bar ) {

		if ( isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'] ) {

			$the_current_code = isset( $_GET['currency'] ) ? $_GET['currency'] : get_woocommerce_currency();
			$parent = 'reports_currency_select';
			$args = array(
				'parent' => false,
				'id'     => $parent,
				'title'  => __( 'Reports currency:', 'woocommerce-jetpack' ) . ' ' . $the_current_code,
				'href'   => false,
				'meta'   => array( 'title' => __( 'Show reports only in', 'woocommerce-jetpack' ) . ' ' . $the_current_code, ),
			);
			$wp_admin_bar->add_node( $args );

			$currency_symbols = array();
			$currency_symbols[ $the_current_code ] = $the_current_code;
			$currency_symbols[ get_woocommerce_currency() ] = get_woocommerce_currency();
			if ( wcj_is_module_enabled( 'price_by_country' ) ) {
				for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
					$the_code = wcj_get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
					$currency_symbols[ $the_code ] = $the_code;
				}
			}
			if ( wcj_is_module_enabled( 'multicurrency' ) ) {
				for ( $i = 1; $i <= apply_filters( 'booster_option', 2, wcj_get_option( 'wcj_multicurrency_total_number', 2 ) ); $i++ ) {
					$the_code = wcj_get_option( 'wcj_multicurrency_currency_' . $i );
					$currency_symbols[ $the_code ] = $the_code;
				}
			}
			if ( wcj_is_module_enabled( 'payment_gateways_currency' ) ) {
				global $woocommerce;
				$available_gateways = $woocommerce->payment_gateways->payment_gateways();
				foreach ( $available_gateways as $key => $gateway ) {
					$the_code = wcj_get_option( 'wcj_gateways_currency_' . $key );
					if ( 'no_changes' != $the_code ) {
						$currency_symbols[ $the_code ] = $the_code;
					}
				}
			}
			sort( $currency_symbols );
			$currency_symbols['merge'] = 'merge';

			foreach ( $currency_symbols as $code ) {
				$args = array(
					'parent' => $parent,
					'id'     => $parent . '_' . $code,
					'title'  => $code,
					'href'   => add_query_arg( 'currency', $code ),
					'meta'   => array( 'title' => __( 'Show reports only in', 'woocommerce-jetpack' ) . ' ' . $code, ),
				);
				$wp_admin_bar->add_node( $args );
			}
		}
	}

	/**
	 * change_currency_symbol_reports.
	 *
	 * @version 3.9.0
	 */
	function change_currency_symbol_reports( $currency_symbol, $currency ) {
		if ( isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'] ) {
			if ( isset( $_GET['currency'] ) ) {
				if ( 'merge' === $_GET['currency'] ) {
					return '';
				} else {
					remove_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol_reports'), PHP_INT_MAX, 2 );
					$return = get_woocommerce_currency_symbol( $_GET['currency'] );
					add_filter(    'woocommerce_currency_symbol', array( $this, 'change_currency_symbol_reports'), PHP_INT_MAX, 2 );
					return $return;
				}
			}
		}
		return $currency_symbol;
	}

	/**
	 * filter_reports.
	 *
	 * @version 2.5.7
	 */
	function filter_reports( $args ) {
		if ( isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'] ) {
			if ( isset( $_GET['currency'] ) && 'merge' === $_GET['currency'] ) {
				return $args;
			}
			$args['where_meta'] = array(
				array(
					'meta_key'   => '_order_currency',
					'meta_value' => isset( $_GET['currency'] ) ? $_GET['currency'] : get_woocommerce_currency(),
					'operator'   => '=',
				),
			);
		}
		return $args;
	}
}

endif;

return new WCJ_Currency_Reports();
