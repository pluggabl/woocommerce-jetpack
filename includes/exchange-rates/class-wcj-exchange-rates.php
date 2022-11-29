<?php
/**
 * Booster for WooCommerce Exchange Rates
 *
 * @version 6.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Exchange_Rates' ) ) :

		/**
		 * WCJ_Exchange_Rates.
		 *
		 * @version 3.2.4
		 */
	class WCJ_Exchange_Rates {

		/**
		 * Constructor.
		 *
		 * @version 3.2.4
		 */
		public function __construct() {

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_exchange_rates_script' ) );
			add_action( 'admin_init', array( $this, 'register_script' ) );

			add_action( 'wp_ajax_wcj_ajax_get_exchange_rates_average', array( $this, 'wcj_ajax_get_exchange_rates_average' ) );
			add_action( 'wp_ajax_nopriv_wcj_ajax_get_exchange_rates_average', array( $this, 'wcj_ajax_get_exchange_rates_average' ) );
		}

		/**
		 * Wcj_ajax_get_exchange_rates_average.
		 *
		 * @version 6.0.0
		 * @since   3.2.2
		 */
		public function wcj_ajax_get_exchange_rates_average() {
			$wpnonce = isset( $_POST['wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_POST['wpnonce'] ), 'ajax-nonce' ) : false;
			if ( ! $wpnonce ) {
				die();
			}
			$currency_from = isset( $_POST['wcj_currency_from'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_currency_from'] ) ) : '';
			$currency_to   = isset( $_POST['wcj_currency_to'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_currency_to'] ) ) : '';
			$start_date    = isset( $_POST['wcj_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_start_date'] ) ) : '';
			$end_date      = isset( $_POST['wcj_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_end_date'] ) ) : '';
			echo esc_html( wcj_currencyconverterapi_io_get_exchange_rate_average( $currency_from, $currency_to, $start_date, $end_date ) );
			die();
		}

		/**
		 * Register_script.
		 *
		 * @version 6.0.0
		 */
		public function register_script() {
			$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			if (
			$wpnonce &&
			isset( $_GET['section'] )
			&&
			in_array(
				$_GET['section'],
				array(
					'multicurrency',
					'multicurrency_base_price',
					'currency_per_product',
					'price_by_country',
					'payment_gateways_currency',
					'currency_exchange_rates',
				),
				true
			)
			) {
				wp_register_script( 'wcj-exchange-rates-ajax', trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-ajax-exchange-rates.js', array( 'jquery' ), w_c_j()->version, true );
				wp_localize_script(
					'wcj-exchange-rates-ajax',
					'ajax_object',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'wpnonce'  => wp_create_nonce( 'ajax-nonce' ),
					)
				);
			}
		}

		/**
		 * Enqueue_exchange_rates_script.
		 *
		 * @version 6.0.0
		 */
		public function enqueue_exchange_rates_script() {
			$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			if (
			$wpnonce &&
			isset( $_GET['section'] ) &&
			in_array(
				$_GET['section'],
				array(
					'multicurrency',
					'multicurrency_base_price',
					'currency_per_product',
					'price_by_country',
					'payment_gateways_currency',
					'currency_exchange_rates',
				),
				true
			)
			) {
				wp_enqueue_script( 'wcj-exchange-rates-ajax' );
			}
			if (
			isset( $_GET['report'] ) &&
			in_array(
				$_GET['report'],
				array(
					'booster_monthly_sales',
				),
				true
			)
			) {
				wp_enqueue_script( 'wcj-exchange-rates-ajax-average', trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-ajax-exchange-rates-average.js', array( 'jquery' ), w_c_j()->version, true );
				wp_localize_script(
					'wcj-exchange-rates-ajax-average',
					'ajax_object',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'wpnonce'  => wp_create_nonce( 'ajax-nonce' ),
					)
				);
			}
		}

	}

endif;

return new WCJ_Exchange_Rates();
