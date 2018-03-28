<?php
/**
 * Booster for WooCommerce - Module - Stock
 *
 * @version 3.5.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Stock' ) ) :

class WCJ_Stock extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.4.0
	 * @since   2.8.0
	 * @todo    (maybe) change `link_slug` to "woocommerce-products-stock" or "woocommerce-product-stock"
	 */
	function __construct() {

		$this->id         = 'stock';
		$this->short_desc = __( 'Stock', 'woocommerce-jetpack' );
		$this->desc       = __( 'WooCommerce products stock management.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-stock';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Custom "In Stock"
			if ( 'yes' === get_option( 'wcj_stock_custom_in_stock_section_enabled', 'no' ) ) {
				if ( 'yes' === get_option( 'wcj_stock_custom_in_stock_enabled', 'no' ) ) {
					add_filter( 'woocommerce_get_availability_text', array( $this, 'custom_in_stock' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_stock_custom_in_stock_class_enabled', 'no' ) ) {
					add_filter( 'woocommerce_get_availability_class', array( $this, 'custom_in_stock_class' ), PHP_INT_MAX, 2 );
				}
			}
			// Custom "Out of Stock"
			if ( 'yes' === get_option( 'wcj_stock_custom_out_of_stock_section_enabled', 'no' ) ) {
				if ( 'yes' === get_option( 'wcj_stock_custom_out_of_stock_enabled', 'no' ) ) {
					add_filter( 'woocommerce_get_availability_text', array( $this, 'custom_out_of_stock' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_stock_custom_out_of_stock_class_enabled', 'no' ) ) {
					add_filter( 'woocommerce_get_availability_class', array( $this, 'custom_out_of_stock_class' ), PHP_INT_MAX, 2 );
				}
			}
			// Custom stock HTML
			if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_stock_custom_stock_html_section_enabled', 'no' ) ) ) {
				if ( WCJ_IS_WC_VERSION_BELOW_3 ) {
					add_filter( 'woocommerce_stock_html',     array( $this, 'custom_stock_html_below_wc_3' ), PHP_INT_MAX, 3 );
				} else {
					add_filter( 'woocommerce_get_stock_html', array( $this, 'custom_stock_html' ), PHP_INT_MAX, 2 );
				}
			}
			// Remove stock display
			if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_stock_remove_frontend_display_enabled', 'no' ) ) ) {
				add_filter( ( WCJ_IS_WC_VERSION_BELOW_3 ? 'woocommerce_stock_html' : 'woocommerce_get_stock_html' ), '__return_empty_string', PHP_INT_MAX );
			}
		}
	}

	/**
	 * custom_stock_html_below_wc_3.
	 *
	 * @version 3.5.0
	 * @since   3.4.0
	 */
	function custom_stock_html_below_wc_3( $html, $availability_availability, $product ) {
		$availability = $product->get_availability();
		$replacements = array(
			'%class%'        => ( ! empty( $availability['class'] )        ? $availability['class']        : '' ),
			'%availability%' => $availability_availability,
		);
		return do_shortcode( str_replace( array_keys( $replacements ), $replacements, apply_filters( 'booster_option', '<p class="stock %class%">%availability%</p>',
			get_option( 'wcj_stock_custom_stock_html', '<p class="stock %class%">%availability%</p>' ) ) ) );
	}

	/**
	 * custom_stock_html.
	 *
	 * @version 3.5.0
	 * @since   3.4.0
	 */
	function custom_stock_html( $html, $product ) {
		$availability = $product->get_availability();
		$replacements = array(
			'%class%'        => ( ! empty( $availability['class'] )        ? $availability['class']        : '' ),
			'%availability%' => ( ! empty( $availability['availability'] ) ? $availability['availability'] : '' ),
		);
		return do_shortcode( str_replace( array_keys( $replacements ), $replacements, apply_filters( 'booster_option', '<p class="stock %class%">%availability%</p>',
			get_option( 'wcj_stock_custom_stock_html', '<p class="stock %class%">%availability%</p>' ) ) ) );
	}

	/**
	 * custom_in_stock_class.
	 *
	 * @version 3.4.0
	 * @since   3.4.0
	 */
	function custom_in_stock_class( $class, $_product ) {
		if ( $_product->is_in_stock() ) {
			return get_option( 'wcj_stock_custom_in_stock_class', '' );
		}
		return $class;
	}

	/**
	 * custom_in_stock.
	 *
	 * @version 3.5.0
	 * @since   3.4.0
	 */
	function custom_in_stock( $availability, $_product ) {
		if ( $_product->is_in_stock() ) {
			return do_shortcode( get_option( 'wcj_stock_custom_in_stock', '' ) );
		}
		return $availability;
	}

	/**
	 * custom_out_of_stock_class.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function custom_out_of_stock_class( $class, $_product ) {
		if ( ! $_product->is_in_stock() ) {
			return get_option( 'wcj_stock_custom_out_of_stock_class', '' );
		}
		return $class;
	}

	/**
	 * custom_out_of_stock.
	 *
	 * @version 3.5.0
	 * @since   2.8.0
	 * @todo    html tags in < WC3
	 */
	function custom_out_of_stock( $availability, $_product ) {
		if ( ! $_product->is_in_stock() ) {
			return do_shortcode( get_option( 'wcj_stock_custom_out_of_stock', '' ) );
		}
		return $availability;
	}

}

endif;

return new WCJ_Stock();
