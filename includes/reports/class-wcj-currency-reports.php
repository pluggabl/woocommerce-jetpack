<?php
/**
 * Booster for WooCommerce - Reports - Currency
 *
 * @version  5.6.8
 * @author   Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Currency_Reports' ) ) :
		/**
		 * WCJ_Currency_Reports.
		 */
	class WCJ_Currency_Reports {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'woocommerce_reports_get_order_report_data_args', array( $this, 'filter_reports' ), PHP_INT_MAX, 1 );
			add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol_reports' ), PHP_INT_MAX, 2 );
			add_action( 'admin_bar_menu', array( $this, 'add_reports_currency_to_admin_bar' ), PHP_INT_MAX );
		}

		/**
		 * Add_reports_currency_to_admin_bar.
		 *
		 * @version 5.6.8
		 * @param int | string $wp_admin_bar Difine admin_bar.
		 */
		public function add_reports_currency_to_admin_bar( $wp_admin_bar ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'] ) {

				$the_current_code = isset( $_GET['currency'] ) ? sanitize_text_field( wp_unslash( $_GET['currency'] ) ) : get_woocommerce_currency();
				$parent           = 'reports_currency_select';
				$args             = array(
					'parent' => false,
					'id'     => $parent,
					'title'  => __( 'Reports currency:', 'woocommerce-jetpack' ) . ' ' . $the_current_code,
					'href'   => false,
					'meta'   => array( 'title' => __( 'Show reports only in', 'woocommerce-jetpack' ) . ' ' . $the_current_code ),
				);
				$wp_admin_bar->add_node( $args );

				$currency_symbols                               = array();
				$currency_symbols[ $the_current_code ]          = $the_current_code;
				$currency_symbols[ get_woocommerce_currency() ] = get_woocommerce_currency();
				if ( wcj_is_module_enabled( 'price_by_country' ) ) {
					$price_by_country_total_groups_num = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_by_country_total_groups_number', 1 ) );
					for ( $i = 1; $i <= $price_by_country_total_groups_num; $i++ ) {
						$the_code                      = wcj_get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
						$currency_symbols[ $the_code ] = $the_code;
					}
				}
				if ( wcj_is_module_enabled( 'multicurrency' ) ) {
					$multicurrency_total_num = apply_filters( 'booster_option', 2, wcj_get_option( 'wcj_multicurrency_total_number', 2 ) );
					for ( $i = 1; $i <= $multicurrency_total_num; $i++ ) {
						$the_code                      = wcj_get_option( 'wcj_multicurrency_currency_' . $i );
						$currency_symbols[ $the_code ] = $the_code;
					}
				}
				if ( wcj_is_module_enabled( 'payment_gateways_currency' ) ) {
					global $woocommerce;
					$available_gateways = $woocommerce->payment_gateways->payment_gateways();
					foreach ( $available_gateways as $key => $gateway ) {
						$the_code = wcj_get_option( 'wcj_gateways_currency_' . $key );
						if ( 'no_changes' !== $the_code ) {
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
						'href'   => esc_url( add_query_arg( 'currency', $code ) ),
						'meta'   => array( 'title' => __( 'Show reports only in', 'woocommerce-jetpack' ) . ' ' . $code ),
					);
					$wp_admin_bar->add_node( $args );
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification
		}

		/**
		 * Change_currency_symbol_reports.
		 *
		 * @version 5.6.8
		 * @param string $currency_symbol Get currency symbol.
		 * @param string $currency Get currency.
		 */
		public function change_currency_symbol_reports( $currency_symbol, $currency ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'] ) {
				if ( isset( $_GET['currency'] ) ) {
					if ( 'merge' === $_GET['currency'] ) {
						return '';
					} else {
						remove_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol_reports' ), PHP_INT_MAX, 2 );
						$return = get_woocommerce_currency_symbol( sanitize_text_field( wp_unslash( $_GET['currency'] ) ) );
						add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol_reports' ), PHP_INT_MAX, 2 );
						return $return;
					}
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification
			return $currency_symbol;
		}

		/**
		 * Filter_reports.
		 *
		 * @version 5.6.8
		 * @param Array $args Get args.
		 */
		public function filter_reports( $args ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( isset( $_GET['page'] ) && 'wc-reports' === $_GET['page'] ) {
				if ( isset( $_GET['currency'] ) && 'merge' === $_GET['currency'] ) {
					return $args;
				}
				$args['where_meta'] = array(
					array(
						'meta_key'   => '_order_currency', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value' => isset( $_GET['currency'] ) ? sanitize_text_field( wp_unslash( $_GET['currency'] ) ) : get_woocommerce_currency(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						'operator'   => '=',
					),
				);
			}
			// phpcs:enable WordPress.Security.NonceVerification
			return $args;
		}
	}

endif;

return new WCJ_Currency_Reports();
