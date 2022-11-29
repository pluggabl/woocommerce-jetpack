<?php
/**
 * Booster for WooCommerce - Module - Currency Exchange Rates
 *
 * @version 6.0.0
 * @since   2.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Currency_Exchange_Rates' ) ) :
	/**
	 * WCJ_Currency_Exchange_Rates.
	 */
	class WCJ_Currency_Exchange_Rates extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 6.0.0
		 */
		public function __construct() {

			$this->id         = 'currency_exchange_rates';
			$this->short_desc = __( 'Currency Exchange Rates', 'woocommerce-jetpack' );
			$this->desc       = __( 'Automatic currency exchange rates for WooCommerce (1 allowed in free version). If you want to change the currency rate with real conversion then enable it', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Automatic currency exchange rates for WooCommerce. If you want to change the currency rate with real conversion then enable it', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-currency-exchange-rates';
			parent::__construct();

			add_action( 'wp_ajax_wcj_ajax_get_exchange_rates', array( $this, 'wcj_ajax_get_exchange_rates' ) );
			add_action( 'wp_ajax_nopriv_wcj_ajax_get_exchange_rates', array( $this, 'wcj_ajax_get_exchange_rates' ) );

			if ( $this->is_enabled() ) {
				include_once 'exchange-rates/class-wcj-exchange-rates-crons.php';
				add_action( 'admin_init', array( $this, 'maybe_update_all_rates' ) );
				add_filter( 'wcj_currency_exchange_rate', array( $this, 'force_dot_as_exchange_rate_decimal_separator' ) );
				$deprecated_option = wcj_get_option( 'wcj_currency_exchange_rates_always_curl' );
				if ( $deprecated_option && '' !== $deprecated_option ) {
					delete_option( 'wcj_currency_exchange_rates_always_curl' );
				}
			}
			include_once 'exchange-rates/class-wcj-exchange-rates.php';
		}

		/**
		 * Force_dot_as_exchange_rate_decimal_separator.
		 *
		 * @version 5.2.0
		 * @since   5.2.0
		 *
		 * @param string $value defines the value.
		 *
		 * @return mixed
		 */
		public function force_dot_as_exchange_rate_decimal_separator( $value ) {
			if ( 'yes' !== wcj_get_option( 'wcj_currency_exchange_rates_point_decimal_separator', 'no' ) ) {
				return $value;
			}
			$value = str_replace( ',', '.', $value );
			return $value;
		}

		/**
		 * Maybe_update_all_rates.
		 *
		 * @version 5.6.8
		 * @since   3.4.5
		 */
		public function maybe_update_all_rates() {
			$wpnonce = isset( $_REQUEST['wcj-cat-nonce'] ) ? wp_verify_nonce( sanitize_key( $_REQUEST['wcj-cat-nonce'] ), 'wcj-cat-nonce' ) : false;
			if ( $wpnonce && isset( $_GET['wcj_currency_exchange_rates_update_now'] ) ) {
				do_action( 'auto_update_exchange_rates_hook' );
				wp_safe_redirect( remove_query_arg( 'wcj_currency_exchange_rates_update_now' ) );
			}
		}

		/**
		 * Wcj_ajax_get_exchange_rates.
		 *
		 * @version 5.6.8
		 * @since   2.6.0
		 * @todo    (maybe) move this to `class-wcj-exchange-rates.php`
		 */
		public function wcj_ajax_get_exchange_rates() {
			$wpnonce = isset( $_POST['wpnonce'] ) ? wp_verify_nonce( sanitize_key( $_POST['wpnonce'] ), 'ajax-nonce' ) : false;
			if ( ! $wpnonce ) {
				die();
			}
			$wcj_currency_from = isset( $_POST['wcj_currency_from'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_currency_from'] ) ) : '';
			$wcj_currency_to   = isset( $_POST['wcj_currency_to'] ) ? sanitize_text_field( wp_unslash( $_POST['wcj_currency_to'] ) ) : '';
			echo esc_html( wcj_get_exchange_rate( $wcj_currency_from, $wcj_currency_to ) );
			die();
		}

		/**
		 * Add_currency_pair_setting.
		 *
		 * @version 3.2.4
		 * @param string $currency_from defines the currency_from.
		 * @param string $currency_to defines the currency_to.
		 * @param array  $settings defines the settings.
		 */
		public function add_currency_pair_setting( $currency_from, $currency_to, $settings ) {
			if ( $currency_from !== $currency_to ) {
				$field_id = 'wcj_currency_exchange_rates_' . sanitize_title( $currency_from . $currency_to );
				foreach ( $settings as $setting ) {
					if ( $setting['id'] === $field_id ) {
						return $settings;
					}
				}
				$custom_attributes = array(
					'currency_from'        => $currency_from,
					'currency_to'          => $currency_to,
					'multiply_by_field_id' => $field_id,
				);
				$settings[]        = array(
					'title'                    => $currency_from . ' / ' . $currency_to,
					'id'                       => $field_id,
					'default'                  => 0,
					'type'                     => 'exchange_rate',
					'custom_attributes_button' => $custom_attributes,
					'value'                    => $currency_from . '/' . $currency_to,
				);
			}
			return $settings;
		}

		/**
		 * Get_all_currencies_exchange_rates_currencies.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 */
		public function get_all_currencies_exchange_rates_currencies() {
			$currencies = array();
			// Additional currencies (via filter).
			$additional_currencies = apply_filters( 'wcj_currency_exchange_rates_additional_currencies', array() );
			foreach ( $additional_currencies as $additional_currency ) {
				$currencies[] = $additional_currency;
			}
			// Additional currencies (via custom currencies section).
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_currency_exchange_custom_currencies_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				$additional_currency = wcj_get_option( 'wcj_currency_exchange_custom_currencies_' . $i, 'disabled' );
				if ( 'disabled' !== ( $additional_currency ) ) {
					$currencies[] = $additional_currency;
				}
			}
			if ( wcj_is_module_enabled( 'price_by_country' ) ) {
				// Currency Pairs - Price by Country.
				if ( 'manual' !== apply_filters( 'booster_option', 'manual', wcj_get_option( 'wcj_price_by_country_auto_exchange_rates', 'manual' ) ) ) {
					$wcj_price_by_country_total_groups_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_by_country_total_groups_number', 1 ) );
					for ( $i = 1; $i <= $wcj_price_by_country_total_groups_number; $i++ ) {
						$currency_to  = wcj_get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
						$currencies[] = $currency_to;
					}
				}
			}
			if ( wcj_is_module_enabled( 'multicurrency' ) ) {
				// Currency Pairs - Multicurrency.
				$wcj_multicurrency_total_number = apply_filters( 'booster_option', 2, wcj_get_option( 'wcj_multicurrency_total_number', 2 ) );
				if ( 'manual' !== apply_filters( 'booster_option', 'manual', wcj_get_option( 'wcj_multicurrency_exchange_rate_update_auto', 'manual' ) ) ) {
					for ( $i = 1; $i <= $wcj_multicurrency_total_number; $i++ ) {
						$currency_to  = wcj_get_option( 'wcj_multicurrency_currency_' . $i );
						$currencies[] = $currency_to;
					}
				}
			}
			if ( wcj_is_module_enabled( 'multicurrency_base_price' ) ) {
				// Currency Pairs - Multicurrency Product Base Price.
				$wcj_multicurrency_base_price_total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_multicurrency_base_price_total_number', 1 ) );
				if ( 'manual' !== apply_filters( 'booster_option', 'manual', wcj_get_option( 'wcj_multicurrency_base_price_exchange_rate_update', 'manual' ) ) ) {
					for ( $i = 1; $i <= $wcj_multicurrency_base_price_total_number; $i++ ) {
						$currency_to  = wcj_get_option( 'wcj_multicurrency_base_price_currency_' . $i );
						$currencies[] = $currency_to;
					}
				}
			}
			if ( wcj_is_module_enabled( 'currency_per_product' ) ) {
				// Currency Pairs - Currency per Product.
				$wcj_currency_per_product_total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_currency_per_product_total_number', 1 ) );
				if ( 'manual' !== apply_filters( 'booster_option', 'manual', wcj_get_option( 'wcj_currency_per_product_exchange_rate_update', 'manual' ) ) ) {
					for ( $i = 1; $i <= $wcj_currency_per_product_total_number; $i++ ) {
						$currency_to  = wcj_get_option( 'wcj_currency_per_product_currency_' . $i );
						$currencies[] = $currency_to;
					}
				}
			}
			if ( wcj_is_module_enabled( 'payment_gateways_currency' ) ) {
				if ( 'manual' !== apply_filters( 'booster_option', 'manual', wcj_get_option( 'wcj_gateways_currency_exchange_rate_update_auto', 'manual' ) ) ) {
					// Currency Pairs - Gateway Currency.
					global $woocommerce;
					$available_gateways = $woocommerce->payment_gateways->payment_gateways();
					foreach ( $available_gateways as $key => $gateway ) {
						$currency_to = wcj_get_option( 'wcj_gateways_currency_' . $key );
						if ( 'no_changes' !== $currency_to ) {
							$currencies[] = $currency_to;
						}
					}
				}
			}
			return $currencies;
		}

		/**
		 * Add_currency_pair_server_setting.
		 *
		 * @version 3.2.4
		 * @since   3.2.
		 * @param string         $currency_from defines the currency_from.
		 * @param string         $currency_to defines the currency_to.
		 * @param string | array $default_server defines the default_server.
		 * @param array          $settings defines the settings.
		 */
		public function add_currency_pair_server_setting( $currency_from, $currency_to, $default_server, $settings ) {
			if ( $currency_from !== $currency_to ) {
				$field_id = 'wcj_currency_exchange_rates_server_' . sanitize_title( $currency_from . $currency_to );
				foreach ( $settings as $setting ) {
					if ( $setting['id'] === $field_id ) {
						return $settings;
					}
				}
				$settings[] = array(
					'desc'    => __( 'Exchange Rates Server', 'woocommerce-jetpack' ),
					'id'      => $field_id,
					'default' => 'default_server',
					'type'    => 'select',
					'options' => array_merge(
						/* translators: %s: translation added */
						array( 'default_server' => sprintf( __( 'Use default: %s', 'woocommerce-jetpack' ), $default_server ) ),
						wcj_get_currency_exchange_rate_servers()
					),
				);
			}
			return $settings;
		}

		/**
		 * Add_currency_pair_offset_percent_setting.
		 *
		 * @version 3.4.5
		 * @since   3.4.5
		 * @param string         $currency_from defines the currency_from.
		 * @param string         $currency_to defines the currency_to.
		 * @param string | array $default_offset defines the default_offset.
		 * @param array          $settings defines the settings.
		 */
		public function add_currency_pair_offset_percent_setting( $currency_from, $currency_to, $default_offset, $settings ) {
			if ( $currency_from !== $currency_to ) {
				$field_id = 'wcj_currency_exchange_rates_offset_percent_' . sanitize_title( $currency_from . $currency_to );
				foreach ( $settings as $setting ) {
					if ( $setting['id'] === $field_id ) {
						return $settings;
					}
				}
				$settings[] = array(
					'desc'    => __( 'Exchange Rates Offset (Percent)', 'woocommerce-jetpack' ),
					'id'      => $field_id,
					'default' => 'default_offset',
					'type'    => 'select',
					'options' => array(
						/* translators: %s: translation added */
						'default_offset' => sprintf( __( 'Use default: %s%%', 'woocommerce-jetpack' ), $default_offset ),
						'custom_offset'  => __( 'Custom offset', 'woocommerce-jetpack' ),
					),
				);
				$settings[] = array(
					'desc'              => __( 'Custom offset (Percent)', 'woocommerce-jetpack' ),
					'desc_tip'          => __( 'Used when "Custom offset" option is selected above.', 'woocommerce-jetpack' ),
					'id'                => $field_id . '_custom_offset',
					'default'           => 0,
					'type'              => 'number',
					'custom_attributes' => array( 'step' => '0.001' ),
				);
			}
			return $settings;
		}

		/**
		 * Aet_all_currencies_exchange_rates_settings.
		 *
		 * @version 3.4.5
		 * @since   2.9.0
		 * @param bool $add_extra_settings defines the add_extra_settings.
		 */
		public function get_all_currencies_exchange_rates_settings( $add_extra_settings = false ) {
			if ( $add_extra_settings ) {
				// Server.
				$exchange_rate_servers = wcj_get_currency_exchange_rate_servers();
				$exchange_rate_server  = wcj_get_option( 'wcj_currency_exchange_rates_server', 'ecb' );
				$default_server        = ( isset( $exchange_rate_servers[ $exchange_rate_server ] ) ? $exchange_rate_servers[ $exchange_rate_server ] : $exchange_rate_server );
				// Offset.
				$default_offset = wcj_get_option( 'wcj_currency_exchange_rates_offset_percent', 0 );
			}
			$settings      = array();
			$currency_from = wcj_get_option( 'woocommerce_currency' );
			$currencies    = $this->get_all_currencies_exchange_rates_currencies();
			foreach ( $currencies as $currency ) {
				$settings = $this->add_currency_pair_setting( $currency_from, $currency, $settings );
				if ( $add_extra_settings ) {
					$settings = $this->add_currency_pair_server_setting( $currency_from, $currency, $default_server, $settings );
					$settings = $this->add_currency_pair_offset_percent_setting( $currency_from, $currency, $default_offset, $settings );
				}
			}
			return $settings;
		}

	}

endif;

return new WCJ_Currency_Exchange_Rates();
