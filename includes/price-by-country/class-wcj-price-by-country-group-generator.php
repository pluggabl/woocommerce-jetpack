<?php
/**
 * Booster for WooCommerce - Price By Country - Group Generator
 *
 * @version  2.5.0
 * @author   Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_By_Country_Group_Generator' ) ) :

class WCJ_Price_By_Country_Group_Generator {

	/**
	 * Constructor.
	 *
	 * @version 2.3.9
	 */
	function __construct() {
		require_once( 'wcj-country-currency.php' );
		add_action( 'woocommerce_init', array( $this, 'create_all_countries_groups' ) );
	}

	/**
	 * get_currency_countries.
	 *
	 * @version 2.3.9
	 */
	function get_currency_countries( $limit_currencies = '' ) {
		$country_currency = wcj_get_country_currency();
		if ( '' != $limit_currencies ) {
			$default_currency = get_woocommerce_currency();
			if ( 'paypal_only' === $limit_currencies || 'paypal_and_yahoo_exchange_rates_only' === $limit_currencies ) {
				$paypal_supported_currencies = wcj_get_paypal_supported_currencies();
			}
			if ( 'yahoo_exchange_rates_only' === $limit_currencies || 'paypal_and_yahoo_exchange_rates_only' === $limit_currencies ) {
				$yahoo_exchange_rates_supported_currencies = wcj_get_yahoo_exchange_rates_supported_currency();
			}
		}
		$currencies = array();
		foreach ( $country_currency as $country => $currency ) {
			if ( 'paypal_only' === $limit_currencies ) {
				if ( ! isset( $paypal_supported_currencies[ $currency ] ) ) {
					$currency = $default_currency;
				}
			} elseif ( 'yahoo_exchange_rates_only' === $limit_currencies ) {
				if ( ! isset( $yahoo_exchange_rates_supported_currencies[ $currency ] ) ) {
					$currency = $default_currency;
				}
			} elseif ( 'paypal_and_yahoo_exchange_rates_only' === $limit_currencies ) {
				if ( ! isset( $paypal_supported_currencies[ $currency ] ) || ! isset( $yahoo_exchange_rates_supported_currencies[ $currency ] ) ) {
					$currency = $default_currency;
				}
			}
			$currencies[ $currency ][] = $country;
		}
		return $currencies;
	}

	/**
	 * create_all_countries_groups.
	 *
	 * @version 2.5.0
	 */
	function create_all_countries_groups() {
		global $wcj_notice;
		if ( ! isset( $_GET['wcj_generate_country_groups'] ) ) {
			return;
		}
		if ( isset( $_POST['save'] ) ) {
			return;
		}
		if ( /* ! is_admin() || */ ! wcj_is_user_role( 'administrator' ) || 1 === apply_filters( 'booster_option', 1, '' ) ) {
			$wcj_notice = __( 'Create All Country Groups Failed.', 'woocommerce-jetpack' );
			return;
		}

		switch ( $_GET['wcj_generate_country_groups'] ) {
			case 'all':
			case 'paypal_only':
			case 'yahoo_exchange_rates_only':
			case 'paypal_and_yahoo_exchange_rates_only':
				$currencies = $this->get_currency_countries( $_GET['wcj_generate_country_groups'] );
				break;
			default:
				$wcj_notice = __( 'Create All Country Groups Failed. Wrong parameter.', 'woocommerce-jetpack' );
				return;
		}

		$number_of_groups = count( $currencies );
		if ( ! isset( $_GET['wcj_generate_country_groups_confirm'] ) ) {
			$wcj_notice .= sprintf( __( 'All existing country groups will be deleted and %s new groups will be created. Are you sure?', 'woocommerce-jetpack' ), $number_of_groups );
			$wcj_notice .= ' ' . '<a style="color: red !important;" href="' . add_query_arg( 'wcj_generate_country_groups_confirm', 'yes' ) . '">' . __( 'Confirm', 'woocommerce-jetpack' ) . '</a>.';
			//$_GET['wc_message'] = __( 'Are you sure? Confirm.', 'woocommerce-jetpack' );
			/* $wcj_notice .= '<p>';
			$wcj_notice .= __( 'Preview', 'woocommerce-jetpack' ) . '<br>';
			foreach ( $currencies as $group_currency => $countries ) {
				$wcj_notice .= $group_currency . ' - ' . implode( ',', $countries ) . '<br>';
			}
			$wcj_notice .= '</p>'; */
		} else {
			update_option( 'wcj_price_by_country_total_groups_number', $number_of_groups );
			$i = 0;
			foreach ( $currencies as $group_currency => $countries ) {
				$i++;
				switch ( get_option( 'wcj_price_by_country_selection', 'comma_list' ) ) {
					case 'comma_list':
						update_option( 'wcj_price_by_country_exchange_rate_countries_group_' . $i, implode( ',', $countries ) );
						break;
					case 'multiselect':
						update_option( 'wcj_price_by_country_countries_group_' . $i, $countries );
						break;
					case 'chosen_select':
						update_option( 'wcj_price_by_country_countries_group_chosen_select_' . $i, $countries );
						break;
				}
				update_option( 'wcj_price_by_country_exchange_rate_currency_group_'  . $i, $group_currency );
				update_option( 'wcj_price_by_country_exchange_rate_group_'     . $i, 1 );
				update_option( 'wcj_price_by_country_make_empty_price_group_'  . $i, 'no' );
			}
			$wcj_notice = __( 'Country Groups Generated.', 'woocommerce-jetpack' );
		}
	}

}

endif;

return new WCJ_Price_By_Country_Group_Generator();
