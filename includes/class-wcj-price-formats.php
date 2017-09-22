<?php
/**
 * Booster for WooCommerce - Module - Price Formats
 *
 * @version 3.1.3
 * @since   2.5.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Price_Formats' ) ) :

class WCJ_Price_Formats extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.1.3
	 * @since   2.5.2
	 */
	function __construct() {

		$this->id         = 'price_formats';
		$this->short_desc = __( 'Price Formats', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set different WooCommerce price formats for different currencies. Set general price format options.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-price-formats';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Trim Zeros
			if ( 'yes' === get_option( 'wcj_price_formats_general_trim_zeros', 'no' ) ) {
				add_filter( 'woocommerce_price_trim_zeros', '__return_true', PHP_INT_MAX );
			}
			// Price Formats by Currency (or WPML)
			if ( 'yes' === get_option( 'wcj_price_formats_by_currency_enabled', 'yes' ) ) {
				add_filter( 'wc_price_args', array( $this, 'price_format' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * price_format.
	 *
	 * @version 2.5.8
	 * @since   2.5.2
	 */
	function price_format( $args ) {
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_price_formats_total_number', 1 ) ); $i++ ) {
			if ( get_woocommerce_currency() === get_option( 'wcj_price_formats_currency_' . $i ) ) {
				if ( defined( 'ICL_LANGUAGE_CODE' ) && '' != ( $wpml_language = get_option( 'wcj_price_formats_wpml_language_' . $i, '' ) ) ) {
					$wpml_language = explode( ',', trim( str_replace( ' ', '', $wpml_language ), ',' ) );
					if ( ! in_array( ICL_LANGUAGE_CODE, $wpml_language ) ) {
						continue;
					}
				}
				$args['price_format']       = $this->get_woocommerce_price_format( get_option( 'wcj_price_formats_currency_position_' . $i ) );
				$args['decimal_separator']  = get_option( 'wcj_price_formats_decimal_separator_'  . $i );
				$args['thousand_separator'] = get_option( 'wcj_price_formats_thousand_separator_' . $i );
				$args['decimals']           = absint( get_option( 'wcj_price_formats_number_of_decimals_' . $i ) );
				break;
			}
		}
		return $args;
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
