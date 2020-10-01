<?php
/**
 * Booster for WooCommerce - Module - Price Formats
 *
 * @version 5.2.0
 * @since   2.5.2
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_Formats' ) ) :

class WCJ_Price_Formats extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   2.5.2
	 */
	function __construct() {

		$this->id         = 'price_formats';
		$this->short_desc = __( 'Price Formats', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set different price formats for different currencies (1 price format allowed in free version). Set general price format options.', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Set different price formats for different currencies. Set general price format options.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-price-formats';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Trim Zeros
			if ( 'yes' === wcj_get_option( 'wcj_price_formats_general_trim_zeros', 'no' ) ) {
				add_filter( 'woocommerce_price_trim_zeros', '__return_true', PHP_INT_MAX );
			}
			// Price Formats by Currency (or WPML)
			if ( 'yes' === wcj_get_option( 'wcj_price_formats_by_currency_enabled', 'yes' ) ) {
				add_filter( 'wc_price_args',         array( $this, 'price_format' ), PHP_INT_MAX );
				add_action( 'init',                  array( $this, 'add_hooks' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * add_hooks.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 */
	function add_hooks() {
		add_filter( 'wc_get_price_decimals', array( $this, 'price_decimals' ), PHP_INT_MAX );
	}

	/**
	 * price_decimals.
	 *
	 * @version 3.5.0
	 * @since   3.5.0
	 * @todo    code refactoring
	 */
	function price_decimals( $price_decimals_num ) {
		for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_formats_total_number', 1 ) ); $i++ ) {
			if ( get_woocommerce_currency() === wcj_get_option( 'wcj_price_formats_currency_' . $i ) ) {
				if ( defined( 'ICL_LANGUAGE_CODE' ) && '' != ( $wpml_language = wcj_get_option( 'wcj_price_formats_wpml_language_' . $i, '' ) ) ) {
					$wpml_language = explode( ',', trim( str_replace( ' ', '', $wpml_language ), ',' ) );
					if ( ! in_array( ICL_LANGUAGE_CODE, $wpml_language ) ) {
						continue;
					}
				}
				$price_decimals_num = wcj_get_option( 'wcj_price_formats_number_of_decimals_' . $i );
				break;
			}
		}
		return $price_decimals_num;
	}

	/**
	 * price_format.
	 *
	 * @version 4.1.0
	 * @since   2.5.2
	 */
	function price_format( $args ) {
		$current_currency = ( '' !== $args['currency'] ? $args['currency'] : get_woocommerce_currency() );
		for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_formats_total_number', 1 ) ); $i++ ) {
			if ( $current_currency === wcj_get_option( 'wcj_price_formats_currency_' . $i ) ) {
				if ( defined( 'ICL_LANGUAGE_CODE' ) && '' != ( $wpml_language = wcj_get_option( 'wcj_price_formats_wpml_language_' . $i, '' ) ) ) {
					$wpml_language = explode( ',', trim( str_replace( ' ', '', $wpml_language ), ',' ) );
					if ( ! in_array( ICL_LANGUAGE_CODE, $wpml_language ) ) {
						continue;
					}
				}
				$args['price_format']       = $this->get_woocommerce_price_format( wcj_get_option( 'wcj_price_formats_currency_position_' . $i ) );
				$args['price_format']       = $this->get_woocommerce_price_format_currency_code(
					get_option( 'wcj_price_formats_currency_code_position_' . $i, 'none' ), wcj_get_option( 'wcj_price_formats_currency_' . $i ), $args['price_format'] );
				$args['decimal_separator']  = wcj_get_option( 'wcj_price_formats_decimal_separator_'  . $i );
				$args['thousand_separator'] = wcj_get_option( 'wcj_price_formats_thousand_separator_' . $i );
				$args['decimals']           = absint( wcj_get_option( 'wcj_price_formats_number_of_decimals_' . $i ) );
				break;
			}
		}
		return $args;
	}

	/**
	 * get_woocommerce_price_format_currency_code.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function get_woocommerce_price_format_currency_code( $currency_code_pos, $currency, $price_format ) {
		switch ( $currency_code_pos ) {
			case 'left' :
				return $currency . $price_format;
			case 'right' :
				return $price_format . $currency;
			case 'left_space' :
				return $currency . '&nbsp;' . $price_format;
			case 'right_space' :
				return $price_format . '&nbsp;' . $currency;
			default: // 'none'
				return $price_format;
		}
	}

	/**
	 * get_woocommerce_price_format.
	 *
	 * @version 2.5.2
	 * @since   2.5.2
	 */
	function get_woocommerce_price_format( $currency_pos ) {
		$format = '%1$s%2$s';

		switch ( $currency_pos ) {
			case 'left' :
				$format = '%1$s%2$s';
			break;
			case 'right' :
				$format = '%2$s%1$s';
			break;
			case 'left_space' :
				$format = '%1$s&nbsp;%2$s';
			break;
			case 'right_space' :
				$format = '%2$s&nbsp;%1$s';
			break;
		}

		return apply_filters( 'woocommerce_price_format', $format, $currency_pos );
	}

}

endif;

return new WCJ_Price_Formats();
