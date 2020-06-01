<?php
/**
 * Booster for WooCommerce Exchange Rates
 *
 * @version 3.9.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Exchange_Rates' ) ) :

class WCJ_Exchange_Rates {

	/**
	 * Constructor.
	 *
	 * @version 3.2.4
	 */
	function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_exchange_rates_script' ) );
		add_action( 'admin_init',            array( $this, 'register_script' ) );

		add_action( 'wp_ajax_'        . 'wcj_ajax_get_exchange_rates_average', array( $this, 'wcj_ajax_get_exchange_rates_average' ) );
		add_action( 'wp_ajax_nopriv_' . 'wcj_ajax_get_exchange_rates_average', array( $this, 'wcj_ajax_get_exchange_rates_average' ) );
	}

	/**
	 * wcj_ajax_get_exchange_rates_average.
	 *
	 * @version 3.9.0
	 * @since   3.2.2
	 */
	function wcj_ajax_get_exchange_rates_average() {
		echo wcj_currencyconverterapi_io_get_exchange_rate_average( $_POST['wcj_currency_from'], $_POST['wcj_currency_to'], $_POST['wcj_start_date'], $_POST['wcj_end_date'] );
		die();
	}

	/**
	 * register_script.
	 *
	 * @version 2.9.0
	 */
	function register_script() {
		if (
			isset( $_GET['section'] ) &&
			in_array( $_GET['section'], array(
				'multicurrency',
				'multicurrency_base_price',
				'currency_per_product',
				'price_by_country',
				'payment_gateways_currency',
				'currency_exchange_rates',
			) )
		) {
			wp_register_script( 'wcj-exchange-rates-ajax',  trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-ajax-exchange-rates.js', array( 'jquery' ), WCJ()->version, true );
			wp_localize_script( 'wcj-exchange-rates-ajax', 'ajax_object', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			) );
		}
	}

	/**
	 * enqueue_exchange_rates_script.
	 *
	 * @version 3.2.2
	 */
	function enqueue_exchange_rates_script() {
		if (
			isset( $_GET['section'] ) &&
			in_array( $_GET['section'], array(
				'multicurrency',
				'multicurrency_base_price',
				'currency_per_product',
				'price_by_country',
				'payment_gateways_currency',
				'currency_exchange_rates',
			) )
		) {
			wp_enqueue_script( 'wcj-exchange-rates-ajax' );
		}
		if (
			isset( $_GET['report'] ) &&
			in_array( $_GET['report'], array(
				'booster_monthly_sales',
			) )
		) {
			wp_enqueue_script(  'wcj-exchange-rates-ajax-average',  trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-ajax-exchange-rates-average.js', array( 'jquery' ), WCJ()->version, true );
			wp_localize_script( 'wcj-exchange-rates-ajax-average', 'ajax_object', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			) );
		}
	}

}

endif;

return new WCJ_Exchange_Rates();
