<?php
/**
 * Booster for WooCommerce - Module - currencies
 *
 * @version 7.1.6
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Currencies' ) ) :
	/**
	 * WCJ_Currencies.
	 */
	class WCJ_Currencies extends WCJ_Module {

		/**
		 * The module saved_symbol
		 *
		 * @var varchar $saved_symbol Module.
		 */
		public $saved_symbol;

		/**
		 * Constructor.
		 *
		 * @version 3.2.4
		 * @todo    [dev] (maybe) update description
		 * @todo    [dev] (maybe) "add additional currencies" checkbox
		 * @todo    [dev] (maybe) save settings as array
		 * @todo    [dev] (maybe) fix missing country flags
		 */
		public function __construct() {

			$this->id         = 'currency';
			$this->short_desc = __( 'Currencies', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add all world currencies and cryptocurrencies to your store; change currency symbol (Plus); add custom currencies (1 allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add all world currencies and cryptocurrencies to your store; change currency symbol; add custom currencies.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-all-currencies';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_currencies', array( $this, 'add_all_currencies' ), PHP_INT_MAX );
				add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol' ), PHP_INT_MAX, 2 );
				add_filter( 'woocommerce_general_settings', array( $this, 'add_edit_currency_symbol_field' ), PHP_INT_MAX );
			}
		}

		/**
		 * Get_custom_currencies.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 */
		public function get_custom_currencies() {
			$custom_currencies                         = array();
			$wcj_currency_custom_currency_total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_currency_custom_currency_total_number', 1 ) );
			for ( $i = 1; $i <= $wcj_currency_custom_currency_total_number; $i++ ) {
				$custom_currency_code = wcj_get_option( 'wcj_currency_custom_currency_code_' . $i, '' );
				$custom_currency_name = wcj_get_option( 'wcj_currency_custom_currency_name_' . $i, '' );
				if ( '' !== $custom_currency_code && '' !== $custom_currency_name ) {
					$custom_currencies[ $custom_currency_code ] = $custom_currency_name;
				}
			}
			return $custom_currencies;
		}

		/**
		 * Get_additional_currencies.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 * @todo    [dev] (maybe) add more cryptocurrencies
		 */
		public function get_additional_currencies() {
			return array(
				// Crypto.
				'AUR' => __( 'Auroracoin', 'woocommerce-jetpack' ),
				'BCC' => __( 'BitConnect', 'woocommerce-jetpack' ),
				'BCH' => __( 'Bitcoin Cash', 'woocommerce-jetpack' ),
				'KOI' => __( 'Coinye', 'woocommerce-jetpack' ),
				'XDN' => __( 'DigitalNote', 'woocommerce-jetpack' ),
				'EMC' => __( 'Emercoin', 'woocommerce-jetpack' ),
				'ETC' => __( 'Ethereum Classic', 'woocommerce-jetpack' ),
				'ETH' => __( 'Ethereum', 'woocommerce-jetpack' ),
				'FMC' => __( 'Freemasoncoin', 'woocommerce-jetpack' ),
				'GRC' => __( 'Gridcoin', 'woocommerce-jetpack' ),
				'IOT' => __( 'IOTA', 'woocommerce-jetpack' ),
				'LTC' => __( 'Litecoin', 'woocommerce-jetpack' ),
				'MZC' => __( 'MazaCoin', 'woocommerce-jetpack' ),
				'XMR' => __( 'Monero', 'woocommerce-jetpack' ),
				'NMC' => __( 'Namecoin', 'woocommerce-jetpack' ),
				'XEM' => __( 'NEM', 'woocommerce-jetpack' ),
				'NXT' => __( 'Nxt', 'woocommerce-jetpack' ),
				'MSC' => __( 'Omni', 'woocommerce-jetpack' ),
				'PPC' => __( 'Peercoin', 'woocommerce-jetpack' ),
				'POT' => __( 'PotCoin', 'woocommerce-jetpack' ),
				'XPM' => __( 'Primecoin', 'woocommerce-jetpack' ),
				'XRP' => __( 'Ripple', 'woocommerce-jetpack' ),
				'SIL' => __( 'SixEleven', 'woocommerce-jetpack' ),
				'AMP' => __( 'Synereo AMP', 'woocommerce-jetpack' ),
				'TIT' => __( 'Titcoin', 'woocommerce-jetpack' ),
				'UBQ' => __( 'Ubiq', 'woocommerce-jetpack' ),
				'VTC' => __( 'Vertcoin', 'woocommerce-jetpack' ),
				'ZEC' => __( 'Zcash', 'woocommerce-jetpack' ),
				// Other.
				'XDR' => __( 'Special Drawing Rights', 'woocommerce-jetpack' ),
				// Virtual.
				'MYC' => __( 'myCred', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Get_additional_currency_symbol.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 * @param string | int $currency_code defines the currency_code.
		 */
		public function get_additional_currency_symbol( $currency_code ) {
			return $currency_code;
		}

		/**
		 * Add_all_currencies.
		 *
		 * @version 3.9.0
		 * @param string | int $currencies defines the currencies.
		 */
		public function add_all_currencies( $currencies ) {
			return array_merge( $currencies, $this->get_additional_currencies(), $this->get_custom_currencies() );
		}

		/**
		 * Get_saved_currency_symbol.
		 *
		 * @version 3.9.0
		 * @since   3.9.0
		 * @param string | int $currency defines the currency.
		 * @param string       $default_symbol defines the default_symbol.
		 */
		public function get_saved_currency_symbol( $currency, $default_symbol ) {
			$saved_currency_symbol = wcj_get_option( 'wcj_currency_' . $currency, false );
			if ( false === ( $saved_currency_symbol ) ) {
				return ( in_array( $currency, array_keys( $this->get_additional_currencies() ), true ) ? $this->get_additional_currency_symbol( $currency ) : $default_symbol );
			} else {
				return $saved_currency_symbol;
			}
		}

		/**
		 * Change_currency_symbol.
		 *
		 * @version 3.9.0
		 * @param string       $currency_symbol defines the currency_symbol.
		 * @param string | int $currency defines the currency.
		 */
		public function change_currency_symbol( $currency_symbol, $currency ) {
			// Maybe return saved value.
			if ( isset( $this->saved_symbol[ $currency ] ) ) {
				return $this->saved_symbol[ $currency ];
			}
			// Maybe hide symbol.
			if ( 'yes' === wcj_get_option( 'wcj_currency_hide_symbol', 'no' ) ) {
				return '';
			}
			// Custom currencies.
			$wcj_currency_custom_currency_total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_currency_custom_currency_total_number', 1 ) );
			for ( $i = 1; $i <= $wcj_currency_custom_currency_total_number; $i++ ) {
				$custom_currency_code = wcj_get_option( 'wcj_currency_custom_currency_code_' . $i, '' );
				$custom_currency_name = wcj_get_option( 'wcj_currency_custom_currency_name_' . $i, '' );
				if ( '' !== $custom_currency_code && '' !== $custom_currency_name && $currency === $custom_currency_code ) {
					$this->saved_symbol[ $currency ] = do_shortcode( wcj_get_option( 'wcj_currency_custom_currency_symbol_' . $i, '' ) );
					return $this->saved_symbol[ $currency ];
				}
			}
			// List.
			$this->saved_symbol[ $currency ] = apply_filters( 'booster_option', $currency_symbol, do_shortcode( $this->get_saved_currency_symbol( $currency, $currency_symbol ) ) );
			return $this->saved_symbol[ $currency ];
		}

		/**
		 * Add_edit_currency_symbol_field.
		 *
		 * @version 3.9.0
		 * @todo    [dev] (maybe) remove this
		 * @param array $settings defines the settings.
		 */
		public function add_edit_currency_symbol_field( $settings ) {
			$updated_settings = array();
			foreach ( $settings as $section ) {
				if ( isset( $section['id'] ) && 'woocommerce_currency_pos' === $section['id'] ) {
					$updated_settings[] = array(
						'name'              => __( 'Booster: Currency Symbol', 'woocommerce-jetpack' ),
						'desc_tip'          => __( 'This sets the currency symbol.', 'woocommerce-jetpack' ),
						'id'                => 'wcj_currency_' . get_woocommerce_currency(),
						'type'              => 'text',
						'default'           => get_woocommerce_currency_symbol(),
						'desc'              => apply_filters( 'booster_message', '', 'desc' ),
						'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
					);
				}
				$updated_settings[] = $section;
			}
			return $updated_settings;
		}

	}

endif;

return new WCJ_Currencies();
